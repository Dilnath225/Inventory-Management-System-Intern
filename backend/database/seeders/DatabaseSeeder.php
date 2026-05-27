<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default Admin user
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@ceyntics.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        // Create a staff user for testing
        User::create([
            'name'     => 'Staff User',
            'email'    => 'staff@ceyntics.com',
            'password' => Hash::make('password'),
            'role'     => 'staff',
        ]);
    }
}
