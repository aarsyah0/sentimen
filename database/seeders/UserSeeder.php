<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'phone' => '081234567890',
            'password' => Hash::make('password'), // Password: password
            'avatar' => null,
            'bio' => 'Ini akun admin.',
            'role' => 'admin',
        ]);

        // User biasa
        User::create([
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@example.com',
            'phone' => '081298765432',
            'password' => Hash::make('password'), // Password: password
            'avatar' => null,
            'bio' => 'Pengguna biasa.',
            'role' => 'user',
        ]);
    }
}
