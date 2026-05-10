<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

class CallcenterProfitService
{
    /**
     * يُستدعى فور إنشاء طلب جديد من الكول سينتر.
     * يحسب الشريحة الجديدة لهذا اليوم ويُحدّث كل طلبات اليوم.
     */
    public function recalculateDayProfits(User $callcenter, Order $justCreatedOrder): void
    {
        $slices = collect($callcenter->incentive_slices ?? []);

        if ($slices->isEmpty()) {
            $justCreatedOrder->update([
                'cc_tier_number' => 0,
                'cc_profit'      => 0,
            ]);
            return;
        }

        // حدود يوم العمل الحالي
        [$dayStart, $dayEnd] = Setting::businessDayRange(now());

        // عدد الطلبات المُنشأة من هذا الكول سينتر في يوم العمل
        $todayOrders = Order::where('callcenter_id', $callcenter->id)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->orderBy('created_at', 'asc')
            ->get(['id', 'created_at']);

        $dayCount = $todayOrders->count();

        // تحديد الشريحة
        $tierResult = $this->findTier($slices, $dayCount);

        // تحديث كل طلبات اليوم بنفس الشريحة
        Order::whereIn('id', $todayOrders->pluck('id'))
            ->update([
                'cc_tier_number' => $tierResult['tier_number'],
                'cc_profit'      => $tierResult['amount'],
            ]);
    }

    private function findTier(Collection $slices, int $count): array
    {
        if ($count === 0) {
            return ['tier_number' => 0, 'amount' => 0];
        }

        foreach ($slices->values() as $index => $s) {
            if ($count >= (int)$s['from'] && $count <= (int)$s['to']) {
                return [
                    'tier_number' => $index + 1,
                    'amount'      => (float)($s['amount'] ?? 0),
                ];
            }
        }

        // تجاوز أعلى شريحة → استخدم الأخيرة
        $last = $slices->last();
        return [
            'tier_number' => $slices->count(),
            'amount'      => (float)($last['amount'] ?? 0),
        ];
    }
}
