<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

class DeliveryProfitService
{
    /**
     * يُستدعى فور توصيل طلب.
     * يحسب الشريحة الجديدة لهذا اليوم ويُحدّث كل طلبات اليوم.
     */
    public function recalculateDayProfits(User $delivery, Order $justDeliveredOrder): void
    {
        $slices = collect($delivery->incentive_slices ?? []);

        if ($slices->isEmpty()) {
            // لا توجد شرائح — صفّر الطلب الجديد فقط
            $justDeliveredOrder->update([
                'delivery_tier_number' => 0,
                'delivery_profit'      => 0,
            ]);
            return;
        }

        // حدود يوم العمل الحالي (يعتمد على businessDayRange)
        [$dayStart, $dayEnd] = Setting::businessDayRange(now());

        // جلب كل طلبات هذا المندوب الموصّلة في يوم العمل الحالي (بعد الحفظ)
        $todayOrders = Order::where('delivery_id', $delivery->id)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$dayStart, $dayEnd])
            ->orderBy('delivered_at', 'asc')
            ->get(['id', 'delivered_at']);

        $dayCount = $todayOrders->count(); // عدد الطلبات الكلي لهذا اليوم الآن

        // تحديد الشريحة بناءً على العدد الكلي
        $tierResult = $this->findTier($slices, $dayCount);

        // تحديث كل طلبات اليوم بنفس الشريحة الجديدة
        Order::whereIn('id', $todayOrders->pluck('id'))
            ->update([
                'delivery_tier_number' => $tierResult['tier_number'],
                'delivery_profit'      => $tierResult['amount'],
            ]);
    }

    /**
     * البحث عن الشريحة المطابقة لعدد طلبات معيّن
     */
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

        // إذا تجاوز العدد أعلى شريحة، استخدم الشريحة الأخيرة
        $last = $slices->last();
        return [
            'tier_number' => $slices->count(),
            'amount'      => (float)($last['amount'] ?? 0),
        ];
    }
}
