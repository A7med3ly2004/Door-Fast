<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name'      => 'المدير العام',
            'username'  => 'admin',
            'password'  => Hash::make('admin123'),
            'role'      => 'admin',
            'phone'     => '01000000000',
            'is_active' => true,
        ]);

        // Call Center
        User::create([
            'name'      => 'سامي محمد',
            'username'  => 'sami',
            'password'  => Hash::make('sami123'),
            'role'      => 'callcenter',
            'phone'     => '01011111111',
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'هند محمد',
            'username'  => 'hind',
            'password'  => Hash::make('hind123'),
            'role'      => 'callcenter',
            'phone'     => '01022222222',
            'is_active' => true,
        ]);

        // Delivery
        User::create([
            'name'      => 'محمود علي',
            'username'  => 'mahmoud',
            'password'  => Hash::make('mahmoud123'),
            'role'      => 'delivery',
            'phone'     => '01033333333',
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'كريم حسن',
            'username'  => 'karim',
            'password'  => Hash::make('karim123'),
            'role'      => 'delivery',
            'phone'     => '01044444444',
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'أحمد سعد',
            'username'  => 'ahmed',
            'password'  => Hash::make('ahmed123'),
            'role'      => 'delivery',
            'phone'     => '01055555555',
            'is_active' => true,
        ]);
    }
}