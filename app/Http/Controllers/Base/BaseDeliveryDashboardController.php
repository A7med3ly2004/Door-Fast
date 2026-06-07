<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shift;
use App\Models\Setting;
use Carbon\Carbon;

abstract class BaseDeliveryDashboardController extends Controller
{
    /**
     * The view prefix for this role (e.g. 'delivery' or 'reserve_delivery')
     */
    abstract protected function viewPrefix(): string;

    /**
     * Extra data to merge into the JSON response — override to add role-specific fields
     */
    protected function extraData(array $base): array
    {
        return [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared methods
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        $prefix = $this->viewPrefix();
        if (request()->header('X-SPA-Navigation')) {
            return response()->json([
                'html'       => view("{$prefix}.partials.dashboard_content")->render(),
                'title'      => 'إحصائياتي اليوم',
                'csrf_token' => csrf_token(),
            ]);
        }
        return view("{$prefix}.dashboard");
    }

    public function data()
    {
        $delivery = auth()->user();
        list($startOfToday, $endOfToday) = Setting::businessDayRange();
        $businessDate = $startOfToday->toDateString();

        $allShiftsToday = Shift::where('delivery_id', $delivery->id)
            ->where('date', $businessDate)
            ->get();

        $previous_worked_seconds = 0;

        $activeShift       = $allShiftsToday->where('is_active', true)->first();
        $started_at        = $activeShift ? str_replace(['AM','PM','am','pm'], ['ص','م','ص','م'], \Carbon\Carbon::parse($activeShift->started_at)->format('h:i A')) : null;
        $started_timestamp = $activeShift ? Carbon::parse($activeShift->started_at)->timestamp  : null;

        $orders = Order::where('delivery_id', $delivery->id)
            ->where(function ($query) use ($startOfToday, $endOfToday) {
                $query->where('status', 'received')
                      ->orWhere(function ($subQuery) use ($startOfToday, $endOfToday) {
                          $subQuery->whereBetween('accepted_at',  [$startOfToday, $endOfToday])
                                   ->orWhereBetween('delivered_at', [$startOfToday, $endOfToday])
                                   ->orWhereBetween('created_at',   [$startOfToday, $endOfToday]);
                      });
            })
            ->get();

        $deliveredCount = $orders->where('status', 'delivered')->count();
        $receivedCount  = $orders->where('status', 'received')->count();
        $cancelledCount = $orders->where('status', 'cancelled')->count();

        $deliveredOrders  = $orders->where('status', 'delivered');
        $totalCollected   = $deliveredOrders->sum('total');
        $totalDeliveryFee = $deliveredOrders->sum('delivery_fee');
        $totalDiscount    = $deliveredOrders->sum('discount');

        // ── Tier & Profits Calculation ──
        $slices = collect($delivery->incentive_slices ?? []);
        $tierNumber = 0;
        $profitPerOrder = 0;

        foreach ($slices->values() as $index => $s) {
            if ($deliveredCount >= (int)$s['from'] && $deliveredCount <= (int)$s['to']) {
                $tierNumber = $index + 1;
                $profitPerOrder = (float)($s['amount'] ?? 0);
                break;
            }
        }
        $totalProfits = $deliveredCount * $profitPerOrder;

        $base = [
            'started_at'               => $started_at,
            'started_timestamp'        => $started_timestamp,
            'previous_worked_seconds'  => $previous_worked_seconds,
            'delivered_count'          => $deliveredCount,
            'received_count'           => $receivedCount,
            'cancelled_count'          => $cancelledCount,
            'total_collected'          => $totalCollected,
            'total_delivery_fee'       => $totalDeliveryFee,
            'total_discount'           => $totalDiscount,
            'tier_number'              => $tierNumber,
            'total_profits'            => number_format($totalProfits, 2),
        ];

        return response()->json(array_merge($base, $this->extraData($base)));
    }
}
