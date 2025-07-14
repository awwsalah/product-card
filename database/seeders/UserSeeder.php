<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin user
        User::create([
            'name' => 'Admin',
            'email' => 'awwsalah@gmail.com',
            'password' => Hash::make('Admin@123'),
            'role' => 'admin'
        ]);

        // Manager user
        User::create([
            'name' => 'Manager User',
            'email' => 'manager@gmail.com',
            'password' => Hash::make('Manager@123'),
            'role' => 'manager'
        ]);

        // Stock Worker user
        User::create([
            'name' => 'Stock Worker',
            'email' => 'worker@gmail.com',
            'password' => Hash::make('Worker@123'),
            'role' => 'stock_worker'
        ]);
    }
}