<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'vtravel.admin@gmail.com'],
            [
                'full_name' => 'Administrator',
                'email' => 'vtravel.admin@gmail.com',
                'phone' => '0900000000',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_verified' => true,
                'is_deleted' => 'active',
            ]
        );
    }
}