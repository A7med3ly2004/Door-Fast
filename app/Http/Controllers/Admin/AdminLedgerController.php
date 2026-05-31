<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * AdminLedgerController
 *
 * كشف الحساب الخاص بالمدير — يتتبع:
 *  - ايصال دفع  (admin_pay)     : المدير يدفع لموظف CC أو مندوب
 *  - ايصال استلام (admin_receive): المدير يستلم من موظف
 *  - صرف مصروف (admin_expense) : ينقص من رصيد المدير فقط
 *
 * الـ Running Balance:
 *  credit (دائن/دخول) → يُزيد الرصيد
 *  debit  (مدين/خروج) → يُنقص الرصيد
 */
class AdminLedgerController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // index — عرض الصفحة الرئيسية
    // ──────────────────────────────────────────────────────────────

    public function index(Request $request): View|JsonResponse
    {
        $admin = auth()->user();

        $callcenters = User::callcenters()->active()->with('wallet')->get(['id', 'name']);
        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])->active()->with('wallet')->get(['id', 'name']);

        // جلب بيانات أولية
        $initialData = $this->buildStatementData($request, $admin);

        $data = [
            'callcenters' => $callcenters,
            'deliveries' => $deliveries,
            'initialData' => $initialData,
            'filters' => [
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'callcenter_id' => $request->input('callcenter_id'),
                'delivery_id' => $request->input('delivery_id'),
            ],
        ];

        if ($request->header('X-SPA-Navigation')) {
            return response()->json([
                'html' => view('admin.admin-ledger.partials.content', $data)->render(),
                'title' => 'كشف حساب خاص',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('admin.admin-ledger.index', $data);
    }

    // ──────────────────────────────────────────────────────────────
    // statement — AJAX: جلب بيانات الجدول مع الرصيد التراكمي
    // ──────────────────────────────────────────────────────────────

    public function statement(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'callcenter_id' => ['nullable', 'exists:users,id'],
            'delivery_id' => ['nullable', 'exists:users,id'],
        ]);

        $admin = auth()->user();
        $data = $this->buildStatementData($request, $admin);

        return response()->json($data);
    }

    // ──────────────────────────────────────────────────────────────
    // payToEmployee — ايصال دفع: المدير يدفع لموظف (CC أو مندوب)
    // ──────────────────────────────────────────────────────────────

    public function payToEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ], $this->messages());

        $admin = auth()->user();
        $employee = User::findOrFail($validated['employee_id']);

        if (!in_array($employee->role, ['callcenter', 'delivery', 'reserve_delivery'])) {
            return response()->json([
                'success' => false,
                'message' => 'يجب اختيار موظف كول سنتر أو مندوب.',
                'errors' => ['employee_id' => ['الموظف المختار ليس كول سنتر أو مندوباً.']],
            ], 422);
        }

        $walletService = app(WalletService::class);
        $date = $validated['date'] ?? now()->toDateString();
        $note = $validated['note'] ?? ('ايصال دفع إلى ' . $employee->name);
        $amount = (float) $validated['amount'];

        try {
            $txIds = DB::transaction(function () use ($admin, $employee, $walletService, $amount, $date, $note) {
                // ✅ lockForUpdate يضمن قراءة الرصيد الحقيقي ويمنع Race Condition
                $adminWallet = Wallet::where('user_id', $admin->id)->lockForUpdate()->firstOrFail();
                $employeeWallet = $employee->getOrCreateWallet();

                if ($amount > (float) $adminWallet->balance) {
                    throw new \Exception(
                        'رصيدك غير كافٍ. الرصيد الحالي: ' . number_format($adminWallet->balance, 2) . ' ج'
                    );
                }

                $adminTx = $walletService->debit(
                    wallet: $adminWallet,
                    amount: $amount,
                    type: 'admin_pay',
                    description: 'ايصال دفع إلى ' . $employee->name . ($note ? ' — ' . $note : ''),
                    createdBy: $admin->id,
                    relatedWalletId: $employeeWallet->id,
                    date: $date
                );

                $empTx = $walletService->credit(
                    wallet: $employeeWallet,
                    amount: $amount,
                    type: 'cash_received',
                    description: 'استلام نقدي من الإدارة — ' . ($note ?: ''),
                    createdBy: $admin->id,
                    relatedWalletId: $adminWallet->id,
                    date: $date
                );

                return [$adminTx->id, $empTx->id];
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['amount' => [$e->getMessage()]],
            ], 422);
        }

        $log = ActivityLog::log(
            event: 'admin_ledger.pay',
            description: 'ايصال دفع إلى ' . $employee->name . ' — ' . number_format($amount, 2) . ' ج',
            subjectType: 'admin_ledger',
            subjectId: $admin->id,
            subjectLabel: $admin->name,
            properties: ['employee_id' => $employee->id, 'amount' => $amount, 'note' => $note]
        );

        \App\Models\WalletTransaction::whereIn('id', $txIds)->update(['log_id' => $log->id]);

        return response()->json([
            'success' => true,
            'message' => 'تم دفع ' . number_format($amount, 2) . ' ج إلى ' . $employee->name . ' بنجاح.',
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // receiveFromEmployee — ايصال استلام: المدير يستلم من موظف
    // ──────────────────────────────────────────────────────────────

    public function receiveFromEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required'], // Can be ID or 'revenue'
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => [
                Rule::requiredIf(fn() => $request->input('employee_id') === 'revenue'),
                'nullable',
                'string',
                'max:500'
            ],
        ], array_merge($this->messages(), [
                'employee_id.required' => 'يجب اختيار موظف أو نوع العملية.',
                'note.required' => 'الملاحظة إلزامية عند اختيار إيراد.',
            ]));

        $admin = auth()->user();
        $walletService = app(WalletService::class);
        $date = $validated['date'] ?? now()->toDateString();
        $amount = (float) $validated['amount'];
        $note = $validated['note'] ?? null;

        try {
            $txIds = DB::transaction(function () use ($admin, $validated, $walletService, $amount, $date, $note) {
                $adminWallet = $admin->getOrCreateWallet();
                $results = ['ids' => [], 'event' => '', 'description' => '', 'properties' => []];

                if ($validated['employee_id'] === 'revenue') {
                    // ── إيراد: فقط زيادة حساب الأدمن — لا يوجد موظف ──────────────
                    $tx = $walletService->credit(
                        wallet: $adminWallet,
                        amount: $amount,
                        type: 'admin_receive',
                        description: 'إيراد — ' . $note,
                        createdBy: $admin->id,
                        date: $date
                    );

                    $results['ids'] = [$tx->id];
                    $results['event'] = 'admin_ledger.revenue';
                    $results['description'] = 'إيراد في حساب ' . $admin->name . ' — ' . number_format($amount, 2) . ' ج';
                    $results['properties'] = ['amount' => $amount, 'note' => $note];

                } else {
                    // ── استلام عادي من موظف ─────────────────────────────────────────
                    $employee = User::findOrFail((int) $validated['employee_id']);
                    $employeeWallet = \App\Models\Wallet::where('user_id', $employee->id)->lockForUpdate()->firstOrFail();

                    // ✅ التحقق من رصيد الموظف
                    if ($amount > (float) $employeeWallet->balance) {
                        throw new \Exception(
                            'رصيد ' . $employee->name . ' غير كافٍ. الرصيد الحالي: ' . number_format($employeeWallet->balance, 2) . ' ج'
                        );
                    }

                    // إضافة لرصيد المدير (credit = دخول)
                    $adminTx = $walletService->credit(
                        wallet: $adminWallet,
                        amount: $amount,
                        type: 'admin_receive',
                        description: 'ايصال استلام من ' . $employee->name . ($note ? ' — ' . $note : ''),
                        createdBy: $admin->id,
                        relatedWalletId: $employeeWallet->id,
                        date: $date
                    );

                    // خصم من رصيد الموظف (debit = خروج)
                    $debitType = $employee->role === 'admin' ? 'admin_pay' : 'cash_paid';
                    $empTx = $walletService->debit(
                        wallet: $employeeWallet,
                        amount: $amount,
                        type: $debitType,
                        description: 'دفع نقدي للإدارة — ' . ($note ?: ''),
                        createdBy: $admin->id,
                        relatedWalletId: $adminWallet->id,
                        date: $date
                    );

                    $results['ids'] = [$adminTx->id, $empTx->id];
                    $results['event'] = 'admin_ledger.receive';
                    $results['description'] = 'ايصال استلام من ' . $employee->name . ' — ' . number_format($amount, 2) . ' ج';
                    $results['properties'] = ['employee_id' => $employee->id, 'amount' => $amount, 'note' => $note];
                }

                return $results;
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['amount' => [$e->getMessage()]],
            ], 422);
        }

        $log = ActivityLog::log(
            event: $txIds['event'],
            description: $txIds['description'],
            subjectType: 'admin_ledger',
            subjectId: $admin->id,
            subjectLabel: $admin->name,
            properties: $txIds['properties']
        );

        \App\Models\WalletTransaction::whereIn('id', $txIds['ids'])->update(['log_id' => $log->id]);

        return response()->json([
            'success' => true,
            'message' => $validated['employee_id'] === 'revenue' ? 'تم تسجيل الإيراد بنجاح.' : 'تم تسجيل الاستلام بنجاح.',
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // storeExpense — صرف مصروف من حساب المدير فقط
    // ──────────────────────────────────────────────────────────────

    public function storeExpense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ], $this->messages());

        $admin = auth()->user();
        $walletService = app(WalletService::class);
        $date = $validated['date'] ?? now()->toDateString();
        $amount = (float) $validated['amount'];
        $fullDesc = $validated['description'] . ($validated['note'] ? ' — ' . $validated['note'] : '');

        try {
            $txId = DB::transaction(function () use ($admin, $walletService, $amount, $date, $fullDesc) {
                // ✅ lockForUpdate يضمن قراءة الرصيد الحقيقي
                $adminWallet = Wallet::where('user_id', $admin->id)->lockForUpdate()->firstOrFail();

                if ($amount > (float) $adminWallet->balance) {
                    throw new \Exception(
                        'رصيدك غير كافٍ. الرصيد الحالي: ' . number_format($adminWallet->balance, 2) . ' ج'
                    );
                }

                $tx = $walletService->debit(
                    wallet: $adminWallet,
                    amount: $amount,
                    type: 'admin_expense',
                    description: $fullDesc,
                    createdBy: $admin->id,
                    date: $date
                );

                return $tx->id;
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['amount' => [$e->getMessage()]],
            ], 422);
        }

        $log = ActivityLog::log(
            event: 'admin_ledger.expense',
            description: 'صرف مصروف — ' . $validated['description'] . ' — ' . number_format($amount, 2) . ' ج',
            subjectType: 'admin_ledger',
            subjectId: $admin->id,
            subjectLabel: $admin->name,
            properties: ['description' => $validated['description'], 'amount' => $amount, 'note' => $validated['note'] ?? null]
        );

        \App\Models\WalletTransaction::where('id', $txId)->update(['log_id' => $log->id]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل المصروف بمبلغ ' . number_format($amount, 2) . ' ج بنجاح.',
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // show — تفاصيل عملية واحدة
    // ──────────────────────────────────────────────────────────────

    public function show($id): JsonResponse
    {
        $admin = auth()->user();
        $adminWallet = $admin->getOrCreateWallet();

        $tx = WalletTransaction::where('id', $id)
            ->where('wallet_id', $adminWallet->id)
            ->whereIn('type', ['admin_pay', 'admin_receive', 'admin_expense'])
            ->with(['relatedWallet.user:id,name', 'createdBy:id,name', 'activityLog'])
            ->firstOrFail();

        return response()->json([
            'id' => $tx->id,
            'log_id' => $tx->activityLog?->id ?? '-',
            'date' => $tx->transaction_date->format('d/m/Y'),
            'type' => $tx->type,
            'type_label' => $this->typeLabel($tx->type),
            'can_edit' => in_array($tx->type, ['admin_pay', 'admin_receive', 'admin_expense']),
            'direction' => $tx->direction,
            'amount' => number_format((float) $tx->amount, 2),
            'description' => $tx->description ?? '—',
            'balance_after' => number_format((float) $tx->balance_after, 2),
            'related_user' => $tx->relatedWallet?->user?->name ?? '—',
            'created_by' => $tx->createdBy?->name ?? '—',
            'created_at' => $tx->created_at->format('d/m/Y H:i'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // update — تعديل عملية (بدون عكس الأرصدة — تعديل الوصف والتاريخ فقط)
    // ──────────────────────────────────────────────────────────────

    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ], $this->messages());

        $admin = auth()->user();
        $adminWallet = $admin->getOrCreateWallet();

        $tx = WalletTransaction::where('id', $id)
            ->where('wallet_id', $adminWallet->id)
            ->whereIn('type', ['admin_pay', 'admin_receive', 'admin_expense'])
            ->firstOrFail();

        $oldAmount = (float) $tx->amount;
        $newAmount = (float) $validated['amount'];
        $diff = $newAmount - $oldAmount;

        DB::transaction(function () use ($tx, $validated, $diff, $adminWallet, $admin) {
            // تحديث رصيد الـ wallet بالفرق
            if ($diff != 0) {
                $wallet = Wallet::lockForUpdate()->find($adminWallet->id);
                if ($tx->direction === 'credit') {
                    // كانت تُضيف → الفرق يُضاف أو يُطرح
                    $wallet->update(['balance' => $wallet->balance + $diff]);
                } else {
                    // كانت تُخصم → الفرق يُطرح أو يُضاف
                    $wallet->update(['balance' => $wallet->balance - $diff]);
                }
            }

            $newDesc = isset($validated['description'])
                ? ($validated['description'] . ($validated['note'] ? ' — ' . $validated['note'] : ''))
                : $tx->description;

            $tx->update([
                'amount' => $validated['amount'],
                'description' => $newDesc,
                'transaction_date' => $validated['date'] ?? $tx->transaction_date,
                'balance_after' => $tx->balance_after + $diff,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'تم تعديل العملية بنجاح.']);
    }

    // ──────────────────────────────────────────────────────────────
    // exportExcel — تصدير Excel
    // ──────────────────────────────────────────────────────────────

    public function exportExcel(Request $request)
    {
        $admin = auth()->user();
        $data = $this->buildStatementData($request, $admin);
        $rows = $data['rows'];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('كشف الحساب');

        // Headers
        $headers = ['#', 'التاريخ', 'التعريف / الملاحظة', 'مدين', 'دائن', 'الرصيد'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }

        // Data rows
        foreach ($rows as $i => $row) {
            $sheet->setCellValue([1, $i + 2], $row['log_id']);
            $sheet->setCellValue([2, $i + 2], $row['date']);
            $sheet->setCellValue([3, $i + 2], $row['description']);
            $sheet->setCellValue([4, $i + 2], $row['debit']);
            $sheet->setCellValue([5, $i + 2], $row['credit']);
            $sheet->setCellValue([6, $i + 2], $row['running_balance']);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'admin-ledger-' . now()->format('Y-m-d') . '.xlsx';
        $path = storage_path('app/temp/' . $filename);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    // ──────────────────────────────────────────────────────────────
    // downloadPdf — طباعة PDF لعملية واحدة
    // ──────────────────────────────────────────────────────────────

    public function downloadPdf($id)
    {
        $admin = auth()->user();
        $adminWallet = $admin->getOrCreateWallet();

        $tx = WalletTransaction::where('id', $id)
            ->where('wallet_id', $adminWallet->id)
            ->whereIn('type', ['admin_pay', 'admin_receive', 'admin_expense'])
            ->with(['relatedWallet.user:id,name', 'createdBy:id,name'])
            ->firstOrFail();

        $typeLabel = $this->typeLabel($tx->type);
        $relatedUser = $tx->relatedWallet?->user?->name ?? null;

        $html = view('admin.admin-ledger.pdf', compact('tx', 'typeLabel', 'relatedUser', 'admin'))->render();

        $Arabic = new \ArPHP\I18N\Arabic();
        $p = $Arabic->arIdentify($html);
        for ($i = count($p) - 1; $i >= 0; $i -= 2) {
            $utf8ar = $Arabic->utf8Glyphs(substr($html, $p[$i - 1], $p[$i] - $p[$i - 1]));
            $html = substr_replace($html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a5', 'portrait');
        $logId = $tx->activityLog?->id ?? $tx->id;
        return $pdf->download('ledger-tx-' . $logId . '.pdf');
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * بناء بيانات الكشف: rows + KPIs + running balance
     * يُستخدم في index() و statement() و exportExcel()
     */
    private function buildStatementData(Request $request, $admin): array
    {
        $adminWallet = $admin->getOrCreateWallet();

        $query = WalletTransaction::where('wallet_id', $adminWallet->id)
            ->whereIn('type', ['admin_pay', 'admin_receive', 'admin_expense'])
            ->with(['relatedWallet.user:id,name', 'activityLog']);

        // فلتر التاريخ
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->input('date_to'));
        }

        // فلتر الكول سنتر
        if ($request->filled('callcenter_id')) {
            $ccId = (int) $request->input('callcenter_id');
            $ccWallet = Wallet::where('user_id', $ccId)->first();
            if ($ccWallet) {
                $query->where('related_wallet_id', $ccWallet->id);
            }
        }

        // فلتر المندوب
        if ($request->filled('delivery_id')) {
            $dId = (int) $request->input('delivery_id');
            $dWallet = Wallet::where('user_id', $dId)->first();
            if ($dWallet) {
                $query->where('related_wallet_id', $dWallet->id);
            }
        }

        // فلتر رقم العملية
        if ($request->filled('log_id')) {
            $query->where('log_id', (int) $request->input('log_id'));
        }

        // ترتيب تصاعدي لحساب الرصيد التراكمي
        $transactions = $query->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // حساب KPIs
        $totalDebit = $transactions->where('direction', 'credit')->sum('amount'); // credit (خصم) = مدين
        $totalCredit = $transactions->where('direction', 'debit')->sum('amount');  // debit (إضافة) = دائن

        // ملاحظة: WalletService::credit() يُسجّل direction='debit'  (إضافة للرصيد)
        //         WalletService::debit()  يُسجّل direction='credit' (خصم من الرصيد)
        // لذا:
        //   direction='debit'  = دخول للحساب = دائن في كشف الحساب
        //   direction='credit' = خروج من الحساب = مدين في كشف الحساب

        $balance = $totalCredit - $totalDebit;

        // Running balance وبناء rows
        $running = 0;
        $rows = $transactions->map(function ($tx) use (&$running) {
            // credit (دائن/دخول) → يُزيد الرصيد
            // debit  (مدين/خروج) → يُنقص الرصيد
            $running += ($tx->direction === 'debit' ? $tx->amount : -$tx->amount);

            return [
                'id' => $tx->id,
                'log_id' => $tx->activityLog?->id ?? '-',
                'date' => $tx->transaction_date->format('d/m/Y'),
                'description' => $tx->description ?? '—',
                'type' => $tx->type,
                'type_label' => $this->typeLabel($tx->type),
                'direction' => $tx->direction,
                'debit' => $tx->direction === 'credit' ? number_format((float) $tx->amount, 2) : '—',
                'credit' => $tx->direction === 'debit' ? number_format((float) $tx->amount, 2) : '—',
                'running_balance' => number_format($running, 2),
                'related_user' => $tx->relatedWallet?->user?->name ?? null,
            ];
        })->values()->toArray();

        return [
            'rows' => $rows,
            'total_debit' => number_format($totalDebit, 2),
            'total_credit' => number_format($totalCredit, 2),
            'balance' => number_format($balance, 2),
        ];
    }

    /**
     * تسمية العملية بالعربي
     */
    private function typeLabel(string $type): string
    {
        return match ($type) {
            'admin_pay' => 'ايصال دفع (خاص)',
            'admin_receive' => 'ايصال استلام (خاص)',
            'admin_expense' => 'صرف مصروف',
            'cash_paid' => 'دفع نقدي',
            'cash_received' => 'استلام نقدي',
            'debt_received' => 'تحصيل مديونية',
            'debt_paid' => 'سداد مديونية',
            'delivery_fee_received' => 'أرباح توصيل',
            'discount' => 'خصم',
            'company_revenue' => 'إيرادات شركة',
            default => $type,
        };
    }

    /**
     * رسائل validation بالعربي
     */
    private function messages(): array
    {
        return [
            'employee_id.required' => 'يجب اختيار الموظف.',
            'employee_id.exists' => 'الموظف المختار غير موجود.',
            'amount.required' => 'حقل المبلغ مطلوب.',
            'amount.numeric' => 'يجب أن يكون المبلغ رقماً.',
            'amount.gt' => 'يجب أن يكون المبلغ أكبر من صفر.',
            'amount.max' => 'المبلغ كبير جداً.',
            'date.date_format' => 'صيغة التاريخ غير صحيحة.',
            'date.before_or_equal' => 'لا يمكن إدخال تاريخ مستقبلي.',
            'description.required' => 'حقل التعريف مطلوب.',
            'description.max' => 'يجب ألا يتجاوز التعريف 200 حرف.',
            'note.max' => 'يجب ألا تتجاوز الملاحظة 500 حرف.',
        ];
    }
}
