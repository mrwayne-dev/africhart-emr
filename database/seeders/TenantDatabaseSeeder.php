<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * TENANT seeder — runs inside one clinic's database.
 *
 * stancl invokes this per tenant via config('tenancy.seeder_parameters'), by
 * which point the connection has already been swapped, so these seeders write
 * into that clinic's database with no changes of their own.
 *
 * ⚠️ Demo data. Everything below is sample records for development and for the
 * throwaway tenants the §6 isolation suite provisions. A real clinic is
 * provisioned EMPTY apart from its own configuration — seeding a live clinic
 * with fictional patients would put invented people in a medical record.
 * Provisioning (A4) must not call this seeder for a real tenant.
 */
class TenantDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            StaffSeeder::class,
            MedicationSeeder::class,
            PatientSeeder::class,
            Phase1DemoSeeder::class,
        ]);
    }
}
