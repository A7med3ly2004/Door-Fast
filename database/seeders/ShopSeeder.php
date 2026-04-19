<?php

namespace Database\Seeders;

use App\Models\Shop;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $shops = [
            [
                'name'    => 'تك ستور',
                'phone'   => '01012340001',
                'address' => 'مدينة نصر، ش التسعين',
                'notes'   => 'متخصص في الإلكترونيات',
            ],
            [
                'name'    => 'جاردن ستور',
                'phone'   => '01098760002',
                'address' => 'المعادي، ش النيل',
                'notes'   => 'منتجات الحدائق والنباتات',
            ],
            [
                'name'    => 'هوم ستايل',
                'phone'   => '01155550003',
                'address' => 'الزمالك، ش 26 يوليو',
                'notes'   => 'أدوات المنزل والديكور',
            ],
            [
                'name'    => 'سمارت هاوس',
                'phone'   => '01022223333',
                'address' => 'مدينة نصر، ح العاشر',
                'notes'   => 'الأجهزة الذكية',
            ],
        ];

        foreach ($shops as $shop) {
            Shop::create($shop);
        }
    }
}