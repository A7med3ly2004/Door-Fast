<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'order_hold_minutes',    'value' => '10'],
            ['key' => 'max_orders_per_delivery','value' => '10'],
            ['key' => 'company_name',           'value' => 'DoorFast'],
            ['key' => 'company_phone',          'value' => '01000000000'],
            ['key' => 'sms_enabled',            'value' => 'false'],
        ];

        foreach ($settings as $s) {
            Setting::create($s);
        }
    }
}