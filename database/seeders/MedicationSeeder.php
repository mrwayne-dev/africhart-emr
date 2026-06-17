<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    /**
     * Seed the per-clinic drug catalog with a small starter list and sensible
     * starting prices (in ₦) the clinic can adjust afterwards. Self-contained so
     * it works on any deploy (does not depend on the gitignored storage/ JSON).
     */
    public function run(): void
    {
        $medications = [
            ['name' => 'Paracetamol', 'default_price' => 200, 'dosages' => ['500mg', '1000mg'], 'routes' => ['oral'], 'common_frequency' => '3 times daily'],
            ['name' => 'Amoxicillin', 'default_price' => 800, 'dosages' => ['250mg', '500mg'], 'routes' => ['oral'], 'common_frequency' => '3 times daily'],
            ['name' => 'Ibuprofen', 'default_price' => 350, 'dosages' => ['200mg', '400mg'], 'routes' => ['oral'], 'common_frequency' => '2-3 times daily'],
            ['name' => 'Metformin', 'default_price' => 600, 'dosages' => ['500mg', '850mg', '1000mg'], 'routes' => ['oral'], 'common_frequency' => '2 times daily'],
            ['name' => 'Amlodipine', 'default_price' => 700, 'dosages' => ['5mg', '10mg'], 'routes' => ['oral'], 'common_frequency' => 'Once daily'],
            ['name' => 'Ciprofloxacin', 'default_price' => 1200, 'dosages' => ['250mg', '500mg'], 'routes' => ['oral'], 'common_frequency' => '2 times daily'],
            ['name' => 'Omeprazole', 'default_price' => 900, 'dosages' => ['20mg', '40mg'], 'routes' => ['oral'], 'common_frequency' => 'Once daily'],
            ['name' => 'Metronidazole', 'default_price' => 500, 'dosages' => ['200mg', '400mg'], 'routes' => ['oral', 'iv'], 'common_frequency' => '3 times daily'],
            ['name' => 'Diclofenac', 'default_price' => 450, 'dosages' => ['25mg', '50mg', '75mg'], 'routes' => ['oral', 'im'], 'common_frequency' => '2-3 times daily'],
            ['name' => 'Artemether/Lumefantrine', 'default_price' => 1500, 'dosages' => ['20/120mg', '40/240mg'], 'routes' => ['oral'], 'common_frequency' => '2 times daily for 3 days'],
        ];

        foreach ($medications as $medication) {
            Medication::updateOrCreate(
                ['name' => $medication['name']],
                [
                    'default_price' => $medication['default_price'],
                    'dosages' => $medication['dosages'],
                    'routes' => $medication['routes'],
                    'common_frequency' => $medication['common_frequency'],
                    'is_active' => true,
                ],
            );
        }
    }
}
