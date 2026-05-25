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

    }
}