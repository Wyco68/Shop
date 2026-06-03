<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@carpart.test'],
            [
                'name' => 'Admin User',
                'email' => 'admin@carpart.test',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@carpart.test'],
            [
                'name' => 'Test Customer',
                'email' => 'user@carpart.test',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_active' => true,
                'phone_num' => '+1234567890',
                'address' => '123 Main Street, Test City',
                'email_verified_at' => now(),
            ]
        );
    }
}