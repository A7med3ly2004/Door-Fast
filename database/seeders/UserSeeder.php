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
            'name' => 'Developer',
            'username' => 'Developer',
            'password' => Hash::make('Developer123'),
            'role' => 'admin',
            'phone' => '01000000000',
            'is_active' => true,
        ]);

    }
}