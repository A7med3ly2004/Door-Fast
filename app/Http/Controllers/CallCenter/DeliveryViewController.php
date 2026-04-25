<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliveryViewController extends Controller
{
    public function index()
    {
        $maxOrders  = (int) Setting::get('max_orders_per_delivery', 10);

        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->with(['shifts' => fn($q) => $q->where('date', $businessDate), 'wallet'])
            ->withCount(['deliveryOrders as orders_today'    => fn($q) => $q->whereBetween('created_at', [$startOfToday, $endOfToday])])
            ->get()
            ->map(fn($d) => [
                'id'               => $d->id,
                'name'             => $d->name,
                'role'             => $d->role,
                'phone'            => $d->phone,
                'is_on_shift'      => $d->shifts->where('is_active', true)->isNotEmpty(),
                'cc_shift_enabled' => (bool) $d->cc_shift_enabled,
                'orders_today'     => $d->orders_today,
                'cancelled_today'  => $d->cancelled_today,
                'max_orders'       => $d->shifts->first()?->max_orders ?? $maxOrders,
                'current_balance'  => $d->wallet?->balance ?? 0,
            ]);

        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view('callcenter.delivery.partials.content', compact('deliveries'))->render(),
                'title'      => 'إدارة المناديب',
                'csrf_token' => csrf_token(),
            ]);
        }

        return view('callcenter.delivery.index', compact('deliveries'));
    }

    public function active()
    {
        $maxOrders = (int) Setting::get('max_orders_per_delivery', 10);

        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->whereHas('shifts', fn($q) => $q->where('is_active', true)->where('date', $businessDate))
            ->with(['shifts' => fn($q) => $q->where('is_active', true)->where('date', $businessDate)])
            ->withCount(['deliveryOrders as orders_today' => fn($q) => $q->whereBetween('created_at', [$startOfToday, $endOfToday])->whereIn('status', ['received', 'delivered'])])
            ->get()
            ->map(fn($d) => [
                'id'         => $d->id,
                'name'       => $d->name,
                'role'       => $d->role,
                'orders_today' => $d->orders_today,
                'max_orders' => $d->shifts->first()?->max_orders ?? $maxOrders,
            ]);

        return response()->json($deliveries);
    }

    /**
     * Returns ALL delivery users (active accounts) with their shift
     * status for the CC delivery management table.
     * Used by reloadDeliveries() in the CC partial JS.
     */
    public function allForCC()
    {
        $maxOrders = (int) Setting::get('max_orders_per_delivery', 10);

        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $deliveries = User::whereIn('role', ['delivery', 'reserve_delivery'])
            ->where('is_active', true)
            ->with(['shifts' => fn($q) => $q->where('date', $businessDate), 'wallet'])
            ->withCount(['deliveryOrders as orders_today' => fn($q) => $q->whereBetween('created_at', [$startOfToday, $endOfToday])])
            ->withCount(['deliveryOrders as cancelled_today' => fn($q) => $q->whereBetween('created_at', [$startOfToday, $endOfToday])->where('status', 'cancelled')])
            ->get()
            ->map(fn($d) => [
                'id'              => $d->id,
                'name'            => $d->name,
                'role'            => $d->role,
                'phone'           => $d->phone,
                'is_on_shift'     => $d->shifts->where('is_active', true)->isNotEmpty(),
                'cc_shift_enabled'=> (bool) $d->cc_shift_enabled,
                'orders_today'    => $d->orders_today,
                'cancelled_today' => $d->cancelled_today,
                'max_orders'      => $d->shifts->first()?->max_orders ?? $maxOrders,
                'current_balance' => $d->wallet?->balance ?? 0,
            ]);

        return response()->json($deliveries);
    }


    public function toggleShift(Request $request, $id)
    {
        $delivery  = User::whereIn('role', ['delivery', 'reserve_delivery'])->findOrFail($id);
        list($startOfToday, $endOfToday) = \App\Models\Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();
        $maxOrders = (int) Setting::get('max_orders_per_delivery', 10);

        $shift = Shift::where('delivery_id', $id)
                      ->where('date', $businessDate)
                      ->first();

        if ($shift && $shift->is_active) {
            // ── Turn OFF ──────────────────────────────────────────
            $shift->update(['is_active' => false, 'ended_at' => now()]);
            // Revoke CC permission so the delivery agent cannot re-start on their own
            $delivery->update(['cc_shift_enabled' => false]);

            $message = 'تم إنهاء وردية ' . $delivery->name;
            $is_on   = false;
        } else {
            // ── Turn ON ───────────────────────────────────────────
            // Grant CC permission flag FIRST
            $delivery->update(['cc_shift_enabled' => true]);

            if ($shift) {
                $shift->update(['is_active' => true, 'started_at' => now(), 'ended_at' => null]);
            } else {
                Shift::create([
                    'delivery_id' => $id,
                    'date'        => $businessDate,
                    'started_at'  => now(),
                    'is_active'   => true,
                    'max_orders'  => $maxOrders,
                ]);
            }

            $message = 'تم بدء وردية ' . $delivery->name;
            $is_on   = true;
        }

        ActivityLog::log(
            event: $is_on ? 'shift.cc_started' : 'shift.cc_ended',
            description: $message,
            subjectType: 'shift',
            subjectId: $delivery->id,
            subjectLabel: $delivery->name
        );

        return response()->json(['success' => true, 'message' => $message, 'is_on' => $is_on]);
    }

    public function statement(Request $request, $id)
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = $request->input('from');
        $to   = $request->input('to');

        // Only allow delivery and reserve_delivery
        $user = User::whereIn('role', ['delivery', 'reserve_delivery'])->findOrFail($id);
        $wallet = $user->getOrCreateWallet();

        $roleLabels = [
            'admin'            => 'أدمن',
            'callcenter'       => 'كول سينتر',
            'delivery'         => 'مندوب',
            'reserve_delivery' => 'مندوب احتياطي',
        ];

        $query = \App\Models\WalletTransaction::where('wallet_id', $wallet->id);

        if ($from) {
            $query->where('transaction_date', '>=', $from);
        }
        if ($to) {
            $query->where('transaction_date', '<=', $to);
        }

        // Totals for the period
        $totals = (clone $query)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) as total_credit
            ")
            ->first();

        // All transactions ordered by date
        $transactions = (clone $query)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function (\App\Models\WalletTransaction $tx) {
                return [
                    'id'               => $tx->id,
                    'transaction_date' => $tx->transaction_date->format('Y-m-d'),
                    'description'      => $tx->description ?? '—',
                    'type_label'       => $tx->type_label,
                    'debit'            => $tx->direction === 'debit' ? number_format((float) $tx->amount, 2) : '',
                    'credit'           => $tx->direction === 'credit' ? number_format((float) $tx->amount, 2) : '',
                    'balance_after'    => number_format((float) $tx->balance_after, 2),
                ];
            });

        $periodBalance = (float) $totals->total_debit - (float) $totals->total_credit;

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'role'       => $user->role,
                'role_label' => $roleLabels[$user->role] ?? $user->role,
            ],
            'summary' => [
                'total_debit'    => number_format((float) $totals->total_debit, 2),
                'total_credit'   => number_format((float) $totals->total_credit, 2),
                'period_balance' => number_format($periodBalance, 2),
                'current_balance' => number_format((float) $wallet->balance, 2),
            ],
            'transactions' => $transactions,
        ]);
    }
}
