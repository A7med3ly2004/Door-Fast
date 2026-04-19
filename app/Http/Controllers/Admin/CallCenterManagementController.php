<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use App\Models\CallcenterSettlement;
use App\Models\TreasuryTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallCenterManagementController extends Controller
{
    public function index()
    {
        if (request()->header('X-SPA-Navigation')) {
            $today = today();
            $agents = User::where('role', 'callcenter')
                ->with(['createdOrders' => fn($q) => $q->whereDate('created_at', $today)])
                ->orderBy('name')
                ->get()
                ->map(fn($cc) => [
                    'id' => $cc->id,
                    'name' => $cc->name,
                    'username' => $cc->username,
                    'phone' => $cc->phone,
                    'is_active' => $cc->is_active,
                    'created' => $cc->createdOrders->count(),
                    'revenue' => $cc->createdOrders->where('status', 'delivered')->sum('total'),
                    'code' => $cc->code,
                ]);
            return response()->json([
                'html' => view('admin.callcenter.partials.content', compact('agents'))->render(),
                'title' => 'كول سنتر',
                'csrf_token' => csrf_token(),
            ]);
        }

        $today = today();
        $agents = User::where('role', 'callcenter')
            ->with(['createdOrders' => fn($q) => $q->whereDate('created_at', $today)])
            ->orderBy('name')
            ->get()
            ->map(fn($cc) => [
                'id' => $cc->id,
                'name' => $cc->name,
                'username' => $cc->username,
                'phone' => $cc->phone,
                'is_active' => $cc->is_active,
                'created' => $cc->createdOrders->count(),
                'revenue' => $cc->createdOrders->where('status', 'delivered')->sum('total'),
                'code' => $cc->code,
            ]);

        return view('admin.callcenter.index', compact('agents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:50',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:30',
            'code' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'code' => $data['code'] ?? null,
            'role' => 'callcenter',
            'is_active' => true,
        ]);

        ActivityLog::log(
            event: 'user.callcenter_created',
            description: 'تم إضافة كول سنتر جديد — ' . $user->name,
            subjectType: 'user',
            subjectId: $user->id,
            subjectLabel: $user->name,
            properties: ['username' => $user->username]
        );

        return response()->json(['success' => true, 'message' => 'تم إضافة موظف الكول سنتر', 'user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::where('role', 'callcenter')->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6',
            'code' => 'nullable|string|max:50',
        ]);

        $updateData = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? $user->phone,
            'code' => $data['code'] ?? $user->code,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $wasActive = $user->is_active;
        $user->update($updateData);

        if (isset($data['is_active']) && $data['is_active'] != $wasActive) {
            $evt  = $data['is_active'] ? 'user.callcenter_activated' : 'user.callcenter_deactivated';
            $desc = $data['is_active']
                ? 'تم تنشيط كول سنتر من الأدمن — ' . $user->name
                : 'تم إلغاء تنشيط كول سنتر من الأدمن — ' . $user->name;
            ActivityLog::log(
                event: $evt,
                description: $desc,
                subjectType: 'user',
                subjectId: $user->id,
                subjectLabel: $user->name
            );
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث الموظف']);
    }

    public function performance(Request $request, $id)
    {
        $user = User::where('role', 'callcenter')->findOrFail($id);

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : Carbon::now()->endOfDay();

        $orders = Order::where('callcenter_id', $id)->whereBetween('created_at', [$from, $to])->get();
        $total = $orders->count();
        $cancelled = $orders->where('status', 'cancelled')->count();
        $revenue = $orders->where('status', 'delivered')->sum('total');

        return response()->json([
            'name' => $user->name,
            'total' => $total,
            'cancelled' => $cancelled,
            'revenue' => $revenue,
        ]);
    }

    public function settle(Request $request, User $user): JsonResponse
    {
        // ── Guard: target must be an active call center agent ─────
        // We use the route-model binding ($user) but add a role check
        // so the endpoint cannot be misused against other role types.
        if ($user->role !== 'callcenter' || !$user->is_active) {
            return response()->json([
                'message' => 'المستخدم المحدد ليس موظف كول سنتر نشطًا.',
            ], 404);
        }

        // ── Validate ──────────────────────────────────────────────
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'note' => ['nullable', 'string', 'max:500'],
        ], $this->settlementValidationMessages());

        // ── Atomic double-write ───────────────────────────────────
        try {
            $settlement = DB::transaction(function () use ($validated, $user): CallcenterSettlement {

                // 1. Source-of-truth row for the CC Management page
                $settlement = CallcenterSettlement::create([
                    'callcenter_id' => $user->id,
                    'settled_by' => auth()->id(),
                    'amount' => (float) $validated['amount'],
                    'note' => $validated['note'] ?? null,
                    'settled_at' => now(),
                ]);

                $settlement->load('callcenter:id,name,phone');
                TreasuryTransaction::createFromSettlement($settlement);

                ActivityLog::log(
                    event: 'settlement.callcenter_admin',
                    description: "تسوية كول سنتر مع الأدمن — {$user->name}",
                    subjectType: 'settlement',
                    subjectId: $settlement->id,
                    subjectLabel: $user->name,
                    properties: [
                        'callcenter_name' => $user->name,
                        'amount' => (float) $validated['amount'],
                        'note'   => $validated['note'] ?? null,
                    ]
                );

                return $settlement;
            });

        } catch (\Throwable $e) {
            // Log for debugging; never expose raw exception to the client
            \Log::error('CallCenter settlement failed', [
                'callcenter_id' => $user->id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'حدث خطأ أثناء تسجيل التسوية. يرجى المحاولة مرة أخرى.',
            ], 500);
        }

        // ── Success response ──────────────────────────────────────
        return response()->json([
            'success' => true,
            'message' => "تم تسجيل تسوية {$user->name} بنجاح وإضافتها للخزينة.",
            'settlement' => [
                'id' => $settlement->id,
                'callcenter_id' => $settlement->callcenter_id,
                'agent_name' => $user->name,
                'amount' => number_format((float) $settlement->amount, 2),
                'note' => $settlement->note ?? '—',
                'settled_at' => $settlement->settled_at->format('d/m/Y H:i'),
            ],
        ], 201);
    }


    // ──────────────────────────────────────────────────────────────
    // Private helper — Arabic validation messages for settle()
    // ──────────────────────────────────────────────────────────────

    private function settlementValidationMessages(): array
    {
        return [
            'amount.required' => 'حقل "المبلغ" مطلوب.',
            'amount.numeric' => 'يجب أن يكون المبلغ رقمًا.',
            'amount.gt' => 'يجب أن يكون المبلغ أكبر من صفر.',
            'amount.max' => 'المبلغ المدخل كبير جدًا.',
            'note.max' => 'يجب ألا تتجاوز الملاحظة 500 حرف.',
        ];
    }
}
