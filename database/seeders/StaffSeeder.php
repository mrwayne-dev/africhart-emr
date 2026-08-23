<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // Seeded staff accounts are pre-verified so the demo logins skip email verification.
        Staff::create([
            'name' => 'Admin User',
            'email' => 'admin@africhart.com',
            'password' => 'password',
            'role' => 'admin',
        ])->forceFill(['email_verified_at' => now()])->save();

        Staff::create([
            'name' => 'Dr. Emeka Okafor',
            'email' => 'doctor@africhart.com',
            'password' => 'password',
            'role' => 'doctor',
        ])->forceFill(['email_verified_at' => now()])->save();

        Staff::create([
            'name' => 'Nurse Amina',
            'email' => 'nurse@africhart.com',
            'password' => 'password',
            'role' => 'nurse',
        ])->forceFill(['email_verified_at' => now()])->save();

        Staff::create([
            'name' => 'Front Desk — Chioma',
            'email' => 'reception@africhart.com',
            'password' => 'password',
            'role' => 'receptionist',
        ])->forceFill(['email_verified_at' => now()])->save();
    }
}
