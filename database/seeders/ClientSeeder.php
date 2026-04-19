<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientAddress;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name'    => 'أحمد خالد',
                'phone'   => '01012345678',
                'phone2'  => '01112345678',
                'addresses' => [
                    ['address' => 'شارع النصر، عمارة 12، الدور الثالث', 'is_default' => true],
                    ['address' => 'ميدان التحرير، بجوار البنك الأهلي',  'is_default' => false],
                ],
            ],
            [
                'name'    => 'منى سعيد',
                'phone'   => '01123456789',
                'phone2'  => null,
                'addresses' => [
                    ['address' => 'المعادي، شارع 9',         'is_default' => true],
                    ['address' => 'القاهرة الجديدة، مول مصر', 'is_default' => false],
                ],
            ],
            [
                'name'    => 'تامر رمضان',
                'phone'   => '01098765432',
                'phone2'  => '01598765432',
                'addresses' => [
                    ['address' => 'مدينة نصر، ش عباس العقاد', 'is_default' => true],
                ],
            ],
            [
                'name'    => 'ريم فاروق',
                'phone'   => '01156789012',
                'phone2'  => null,
                'addresses' => [
                    ['address' => 'الزمالك، ش 26 يوليو',     'is_default' => true],
                    ['address' => 'الدقي، ميدان لبنان',       'is_default' => false],
                    ['address' => 'المهندسين، ش جامعة الدول', 'is_default' => false],
                ],
            ],
            [
                'name'    => 'كمال عبدالله',
                'phone'   => '01224567890',
                'phone2'  => null,
                'addresses' => [
                    ['address' => 'شبرا، ش الترعة', 'is_default' => true],
                ],
            ],
        ];

        foreach ($clients as $data) {
            $client = Client::create([
                'name'   => $data['name'],
                'phone'  => $data['phone'],
                'phone2' => $data['phone2'],
                'code'   => Client::generateCode(),
            ]);

            foreach ($data['addresses'] as $addr) {
                ClientAddress::create([
                    'client_id'  => $client->id,
                    'address'    => $addr['address'],
                    'is_default' => $addr['is_default'],
                ]);
            }
        }
    }
}