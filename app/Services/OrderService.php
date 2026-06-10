<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Jobs\NotifyReserveDeliveryOrder;
use App\Jobs\NotifyPrimaryDeliveryOrder;

/**
 * OrderService
 *
 * المسؤوليات:
 *  - إنشاء الطلبات (CC + Admin) — المنطق المشترك في مكان واحد.
 *  - كل العمليات ملفوفة في DB::transaction().
 *  - إطلاق الـ Events يتم بداخل الـ transaction.
 *
 * القاعدة:
 *  - يستقبل validated data فقط — لا validation هنا.
 *  - الـ Controller مسؤول عن الـ validation وبناء الـ Response.
 */
class OrderService
{
    /**
     * إنشاء طلب جديد — مشترك بين CC و Admin.
     *
     * @param array $data         Validated request data (من Controller)
     * @param int|null $callcenterId  auth()->id() للـ CC، أو null للـ Admin
     * @param bool $adminMode     true = Admin order (no hold, sent_to_delivery_at = now())
     *
     * @return array{
     *   order: Order,
     *   client: Client,
     *   addressWarning: string|null,
     * }
     */
    public function createOrder(array $data, ?int $callcenterId, bool $adminMode = false): array
    {
        $result = DB::transaction(function () use ($data, $callcenterId, $adminMode): array {

            // ── 1. Find or create client ───────────────────────────
            [$client, $addressWarning] = $this->resolveClient($data, $adminMode);

            // ── 2. Compute totals ──────────────────────────────────
            $items = $data['items'];
            $itemsTotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $deliveryFee = (float) ($data['delivery_fee'] ?? 0);
            $baseTotal = $itemsTotal + $deliveryFee;

            $discount = (float) ($data['discount'] ?? 0);
            $discountType = $data['discount_type'] ?? 'amount';
            $discountAmt = $discountType === 'percent'
                ? ($baseTotal * $discount / 100)
                : $discount;

            $total = $baseTotal - $discountAmt;

            // ── 3. Resolve delivery & status ───────────────────────
            $deliveryId = !empty($data['delivery_id']) ? $data['delivery_id'] : null;
            $isDeliveryChosen = (bool) $deliveryId;
            $orderStatus = $isDeliveryChosen ? 'received' : 'pending';
            $acceptedAt = $isDeliveryChosen ? Carbon::now() : null;

            // Admin: no hold period — sent_to_delivery_at = now() always.
            // CC:    pending orders wait for hold period before appearing.
            if ($adminMode) {
                $sentToDeliveryAt = Carbon::now();
            } else {
                $holdMinutes = (int) Setting::get('order_hold_minutes', 10);
                $sentToDeliveryAt = $isDeliveryChosen
                    ? Carbon::now()
                    : Carbon::now()->addMinutes($holdMinutes);
            }

            // ── 4. Create order ────────────────────────────────────
            $order = new Order([
                'order_number' => Order::generateNumber(),
                'callcenter_id' => $callcenterId,      // null = admin order
                'admin_id' => $adminMode ? auth()->id() : null,
                'delivery_id' => $deliveryId,
                'is_delivery_chosen' => $isDeliveryChosen,
                'client_id' => $client->id,
                'client_address' => $data['client_address'],
                'client_delivery_link' => $data['client_delivery_link'] ?? null ?: null,
                'send_to_phone' => $data['send_to_phone'] ?? null ?: null,
                'send_to_phone2' => $data['send_to_phone2'] ?? null ?: null,
                'send_to_address' => $data['send_to_address'] ?? null ?: null,
                'send_to_delivery_link' => $data['send_to_delivery_link'] ?? null ?: null,
                'notes' => $data['notes'] ?? null,
                'delivery_fee' => $deliveryFee,
                'discount' => $discount,
                'discount_type' => $discountType,
                'total' => $total,
                'status' => $orderStatus,
                'accepted_at' => $acceptedAt,
                'sent_to_delivery_at' => $sentToDeliveryAt,
            ]);

            $order->save();

            // ── 5. Handle send-to customer ─────────────────────────
            $this->resolveSendToClient($data, $order);

            // ── 6. Create order items ──────────────────────────────
            foreach ($items as $item) {
                if (empty($item['item_name']))
                    continue;

                OrderItem::create([
                    'order_id' => $order->id,
                    'shop_id' => $item['shop_id'] ?? null ?: null,
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            // ── 7. Logs ────────────────────────────────────────────
            $this->writeOrderLogs($order, $client, $deliveryId, $callcenterId, $adminMode, $data['opened_at'] ?? null);

            // ── 8. Fire event (inside transaction) ────────────────
            try {
                // للأدمن أو عند تحديد مندوب: أطلق الـ Event فوراً
                if ($adminMode || $isDeliveryChosen) {
                    event(new OrderStatusUpdated([
                        'order_id' => $order->id,
                        'status' => $orderStatus,
                        'order_number' => $order->order_number,
                        'delivery_id' => $order->delivery_id,
                    ]));
                }
                // للكول سنتر بدون مندوب: الـ Event سيُطلق من الـ Job بعد انتهاء الـ hold
            } catch (\Throwable) {
                // Never fail a write because of a broadcast failure
            }

            return [
                'order' => $order,
                'client' => $client,
                'addressWarning' => $addressWarning,
                'sentToDeliveryAt' => $sentToDeliveryAt,
                'isDeliveryChosen' => $isDeliveryChosen,
            ];
        });

        // ── 9. Dispatch notification jobs ────────────────────────────────────
        // يشتغل بعد commit الـ transaction في كل الحالات
        if ($result['isDeliveryChosen']) {
            // ✅ مندوب مخصص — dispatch فوري بدون delay
            // NotifyPrimaryDeliveryOrder سيبعت FCM للمندوب المحدد مباشرةً
            // NotifyReserveDeliveryOrder سيتجاهل الطلب تلقائياً (is_delivery_chosen = true)
            NotifyPrimaryDeliveryOrder::dispatch($result['order']->id);
            NotifyReserveDeliveryOrder::dispatch($result['order']->id);
        } else {
            // بدون مندوب — dispatch بعد انتهاء فترة الـ hold كالمعتاد
            $reserveDelay = (int) Setting::get('reserve_delay_minutes', 5);
            $dispatchAt = $result['sentToDeliveryAt']->copy()->addMinutes($reserveDelay);

            NotifyPrimaryDeliveryOrder::dispatch($result['order']->id)
                ->delay($result['sentToDeliveryAt']);

            NotifyReserveDeliveryOrder::dispatch($result['order']->id)
                ->delay($dispatchAt);
        }

        // نرجع نفس الـ keys الأصلية بالظبط — مفيش تغيير على الـ Controllers
        return [
            'order' => $result['order'],
            'client' => $result['client'],
            'addressWarning' => $result['addressWarning'],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Find or create the client and optionally save a new address.
     *
     * @return array{0: Client, 1: string|null}
     */
    private function resolveClient(array $data, bool $adminMode): array
    {
        $addressWarning = null;
        $source = $adminMode ? 'أدمن' : 'كول سنتر';

        $client = Client::where('phone', $data['phone'])
            ->orWhere('phone2', $data['phone'])
            ->first();

        if ($client) {
            $update = [];
            if (!empty($data['name']) && $client->name !== $data['name'])
                $update['name'] = $data['name'];
            if (!empty($data['phone2']) && $client->phone2 !== $data['phone2'])
                $update['phone2'] = $data['phone2'];
            if ($update)
                $client->update($update);
        } else {
            $client = Client::create([
                'phone' => $data['phone'],
                'phone2' => $data['phone2'] ?? null,
                'name' => $data['name'],
                'code' => $data['code'],
            ]);

            ActivityLog::log(
                event: 'client.created_inline',
                description: "تم إضافة عميل جديد أثناء الفاتورة ({$source}) — " . $client->name,
                subjectType: 'client',
                subjectId: $client->id,
                subjectLabel: $client->name,
                properties: ['client_code' => $client->code, 'phone' => $client->phone]
            );
        }

        // Save new address if requested
        if (!empty($data['is_new_address']) && !empty($data['client_address'])) {
            $addrCount = $client->addresses()->count();
            if ($addrCount < 5) {
                ClientAddress::create([
                    'client_id' => $client->id,
                    'address' => $data['client_address'],
                    'is_default' => $addrCount === 0,
                ]);
            } else {
                $addressWarning = 'تم حفظ الطلب لكن لم يُضَف العنوان — العميل لديه 5 عناوين بالفعل';
            }
        }

        return [$client, $addressWarning];
    }

    /**
     * Optionally create a "send-to" client when a phone is provided
     * without an existing client_id.
     */
    private function resolveSendToClient(array $data, Order $order): void
    {
        if (empty($data['send_to_phone'])) {
            return;
        }

        if (!empty($data['send_to_client_id'])) {
            $sendToClient = Client::find($data['send_to_client_id']);
            if ($sendToClient) {
                // Update if the phone2 is different
                if (!empty($data['send_to_phone2']) && $data['send_to_phone2'] !== $sendToClient->phone2) {
                    $sendToClient->update(['phone2' => $data['send_to_phone2']]);
                }
                // Add new address if provided and not already existing
                if (!empty($data['send_to_address']) && $sendToClient->addresses()->count() < 5) {
                    $sendToClient->addresses()->firstOrCreate(
                        ['address' => $data['send_to_address']],
                        ['is_default' => $sendToClient->addresses()->count() === 0]
                    );
                }
                $order->update(['send_to_client_id' => $sendToClient->id]);
            }
            return;
        }

        $sendToClient = Client::where('phone', $data['send_to_phone'])
            ->orWhere('phone2', $data['send_to_phone'])
            ->first();

        if (!$sendToClient) {
            $sendToClient = Client::create([
                'phone' => $data['send_to_phone'],
                'name' => $data['send_to_name'] ?? 'Unnamed',
                'code' => $data['send_to_code'] ?? Client::generateCode(),
                'phone2' => $data['send_to_phone2'] ?? null,
            ]);
        }

        if (!$sendToClient->wasRecentlyCreated) {
            if (!empty($data['send_to_phone2']) && $sendToClient->phone2 !== $data['send_to_phone2']) {
                $sendToClient->update(['phone2' => $data['send_to_phone2']]);
            }
        }

        if ($sendToClient->wasRecentlyCreated) {
            $sendToClient->addresses()->create([
                'address' => $data['send_to_address'] ?? '',
                'is_default' => true,
            ]);

            ActivityLog::log(
                event: 'client.created_sendto',
                description: 'تم إضافة عميل الإرسال إليه تلقائياً — ' . $sendToClient->name,
                subjectType: 'client',
                subjectId: $sendToClient->id,
                subjectLabel: $sendToClient->name,
                properties: ['phone' => $sendToClient->phone, 'code' => $sendToClient->code]
            );
        }

        $order->update(['send_to_client_id' => $sendToClient->id]);
    }

    /**
     * Write OrderLog + ActivityLog after order creation.
     */
    private function writeOrderLogs(Order $order, Client $client, ?int $deliveryId, ?int $callcenterId, bool $adminMode, ?string $openedAt = null): void
    {
        if ($adminMode) {
            $logAction = $deliveryId
                ? 'إنشاء طلب مباشر من الأدمن — تم تحديد مندوب'
                : 'إنشاء طلب من الأدمن — بدون مندوب (طلبات جديدة)';
            $logNotes = 'بواسطة: ' . auth()->user()->name;
            $actEvent = 'order.created_admin';
            $actDesc = $deliveryId
                ? 'تم إنشاء طلب من الأدمن مع تحديد مندوب'
                : 'تم إنشاء طلب من الأدمن بدون مندوب';
        } else {
            $logAction = 'تم إنشاء الطلب';
            $logNotes = 'بواسطة كول سنتر: ' . auth()->user()->name;
            $actEvent = 'order.created_cc';
            $actDesc = 'تم إنشاء طلب جديد من كول سنتر';
        }

        $firstLog = new OrderLog([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'تم فتح الفاتورة',
            'notes' => 'وقت فتح الفاتورة الفعلي',
        ]);
        $firstLog->created_at = $openedAt ? \Carbon\Carbon::parse($openedAt) : $order->created_at;
        $firstLog->updated_at = $firstLog->created_at;
        $firstLog->save();

        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => $logAction,
            'notes' => $logNotes,
        ]);

        ActivityLog::log(
            event: $actEvent,
            description: $actDesc,
            subjectType: 'order',
            subjectId: $order->id,
            subjectLabel: $order->order_number,
            properties: [
                'order_number' => $order->order_number,
                'client_name' => $client->name,
                'client_code' => $client->code,
                'client_phone' => $client->phone,
                'total' => $order->total,
                'delivery_id' => $order->delivery_id,
            ]
        );
    }
}