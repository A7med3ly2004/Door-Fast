<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\User;
use App\Models\Client;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $callcenters = User::where('role', 'callcenter')->get();
        $deliveries  = User::where('role', 'delivery')->get();
        $clients     = Client::with('defaultAddress')->get();
        $shops       = Shop::all();

        $statuses = ['pending', 'received', 'delivered', 'cancelled'];

        for ($i = 1; $i <= 20; $i++) {
            $client   = $clients->random();
            $cc       = $callcenters->random();
            $delivery = $deliveries->random();
            $status   = $statuses[array_rand($statuses)];

            $order = Order::create([
                'order_number'  => 'ORD-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'callcenter_id' => $cc->id,
                'delivery_id'   => in_array($status, ['received', 'delivered']) ? $delivery->id : null,
                'client_id'     => $client->id,
                'client_address'=> $client->defaultAddress?->address ?? 'عنوان تجريبي',
                'delivery_fee'  => 50,
                'discount'      => rand(0, 1) ? 50 : 0,
                'discount_type' => 'amount',
                'status'        => $status,
                'created_at'    => now()->subDays(rand(0, 7)),
            ]);

            // إضافة أصناف
            $itemsCount = rand(1, 3);
            $itemsTotal = 0;

            for ($j = 0; $j < $itemsCount; $j++) {
                $qty   = rand(1, 3);
                $price = rand(100, 1000);
                $total = $qty * $price;
                $itemsTotal += $total;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'shop_id'    => $shops->random()->id,
                    'item_name'  => 'صنف تجريبي ' . ($j + 1),
                    'quantity'   => $qty,
                    'unit_price' => $price,
                    'total'      => $total,
                ]);
            }

            // تحديث الإجمالي
            $order->update([
                'total' => $itemsTotal + $order->delivery_fee - $order->discount,
            ]);

            // log
            OrderLog::create([
                'order_id' => $order->id,
                'user_id'  => $cc->id,
                'action'   => 'تم إنشاء الطلب',
            ]);
        }
    }
}