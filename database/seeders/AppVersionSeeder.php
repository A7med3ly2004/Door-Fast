<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class AppVersionSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('app_minimum_version', '1.0.0');
        Setting::set('app_latest_version',  '1.0.0');
        Setting::set('app_update_url',      'https://yourdomain.com/downloads/captain-app.apk');
    }
}
