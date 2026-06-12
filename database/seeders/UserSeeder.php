<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Seeded staff accounts are pre-verified so the demo logins skip email verification.
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@africhart.com',
            'password' => 'password',
            'role' => 'admin',
        ])->forceFill(['email_verified_at' => now()])->save();

        User::create([
            'name' => 'Dr. Emeka Okafor',
            'email' => 'doctor@africhart.com',
            'password' => 'password',
            'role' => 'doctor',
        ])->forceFill(['email_verified_at' => now()])->save();

        User::create([
            'name' => 'Nurse Amina',
            'email' => 'nurse@africhart.com',
            'password' => 'password',
            'role' => 'nurse',
        ])->forceFill(['email_verified_at' => now()])->save();

        User::create([
            'name' => 'Front Desk — Chioma',
            'email' => 'reception@africhart.com',
            'password' => 'password',
            'role' => 'receptionist',
        ])->forceFill(['email_verified_at' => now()])->save();
    }
}
