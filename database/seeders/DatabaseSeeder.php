<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * CENTRAL seeder — runs against africhart_central.
 *
 * It used to seed the clinical tables, which is now the wrong database
 * entirely: patients, staff, medications and the rest live in a tenant's own
 * database. Those seeders moved to TenantDatabaseSeeder, which stancl runs per
 * tenant at provisioning.
 *
 * Left calling only what genuinely belongs to the platform. Plans are not
 * optional here — clinics.plan carries a foreign key to plans.slug, so a
 * central database without them cannot register a clinic.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
        ]);
    }
}
