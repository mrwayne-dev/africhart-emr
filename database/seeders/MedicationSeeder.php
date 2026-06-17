<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    /**
     * Seed the per-clinic drug catalog from the bundled preset list, assigning
     * sensible starting prices (in ₦) the clinic can adjust afterwards.
     */
    public function run(): void
    {
        $defaultPrices = [
            'Paracetamol' => 200,
            'Amoxicillin' => 800,
            'Ibuprofen' => 350,
            'Metformin' => 600,
            'Amlodipine' => 700,
            'Ciprofloxacin' => 1200,
            'Omeprazole' => 900,
            'Metronidazole' => 500,
            'Diclofenac' => 450,
            'Artemether/Lumefantrine' => 1500,
        ];

        $path = storage_path('app/data/medications.json');
        $presets = is_file($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];

        foreach ($presets as $preset) {
            Medication::updateOrCreate(
                ['name' => $preset['name']],
                [
                    'default_price' => $defaultPrices[$preset['name']] ?? 0,
                    'dosages' => $preset['dosages'] ?? [],
                    'routes' => $preset['routes'] ?? [],
                    'common_frequency' => $preset['common_frequency'] ?? null,
                    'is_active' => true,
                ],
            );
        }
    }
}
