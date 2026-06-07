<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\PatientService;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $patientService = app(PatientService::class);

        $patients = [
            ['full_name' => 'Chioma Adaeze Nwosu', 'date_of_birth' => '1990-03-14', 'phone' => '08031234501', 'blood_group' => 'O+', 'allergies' => 'Penicillin'],
            ['full_name' => 'Emeka Chukwuemeka Obi', 'date_of_birth' => '1985-07-22', 'phone' => '08031234502', 'blood_group' => 'A+', 'allergies' => null],
            ['full_name' => 'Fatima Bello Mohammed', 'date_of_birth' => '1978-11-02', 'phone' => '08031234503', 'blood_group' => 'B+', 'allergies' => 'Sulfa drugs'],
            ['full_name' => 'Oluwaseun David Adeyemi', 'date_of_birth' => '1995-06-30', 'phone' => '08031234504', 'blood_group' => 'AB+', 'allergies' => null],
            ['full_name' => 'Amina Yusuf Ibrahim', 'date_of_birth' => '2000-01-18', 'phone' => '08031234505', 'blood_group' => 'O-', 'allergies' => 'Dust, pollen'],
            ['full_name' => 'Chinedu Ikenna Eze', 'date_of_birth' => '1982-09-09', 'phone' => '08031234506', 'blood_group' => 'A-', 'allergies' => null],
            ['full_name' => 'Aisha Binta Abubakar', 'date_of_birth' => '1993-12-25', 'phone' => '08031234507', 'blood_group' => 'B-', 'allergies' => 'Latex'],
            ['full_name' => 'Tunde Babatunde Ogunleye', 'date_of_birth' => '1975-04-11', 'phone' => '08031234508', 'blood_group' => 'O+', 'allergies' => null],
            ['full_name' => 'Ngozi Blessing Okonkwo', 'date_of_birth' => '1988-08-07', 'phone' => '08031234509', 'blood_group' => 'AB-', 'allergies' => 'Aspirin'],
            ['full_name' => 'Yakubu Musa Danladi', 'date_of_birth' => '1970-02-28', 'phone' => '08031234510', 'blood_group' => 'A+', 'allergies' => null],
            ['full_name' => 'Funke Adebisi Oladipo', 'date_of_birth' => '1997-05-16', 'phone' => '08031234511', 'blood_group' => 'O+', 'allergies' => 'Peanuts'],
            ['full_name' => 'Obinna Francis Nwankwo', 'date_of_birth' => '1991-10-03', 'phone' => '08031234512', 'blood_group' => 'B+', 'allergies' => null],
            ['full_name' => 'Hauwa Sadiya Garba', 'date_of_birth' => '2003-07-21', 'phone' => '08031234513', 'blood_group' => 'A+', 'allergies' => null],
            ['full_name' => 'Adewale Samuel Akinola', 'date_of_birth' => '1986-03-19', 'phone' => '08031234514', 'blood_group' => 'O-', 'allergies' => 'Shellfish'],
            ['full_name' => 'Uchenna Grace Okoro', 'date_of_birth' => '1999-09-12', 'phone' => '08031234515', 'blood_group' => 'AB+', 'allergies' => null],
            ['full_name' => 'Ibrahim Suleiman Bako', 'date_of_birth' => '1968-12-01', 'phone' => '08031234516', 'blood_group' => 'B-', 'allergies' => 'Penicillin'],
            ['full_name' => 'Yetunde Folake Bakare', 'date_of_birth' => '1994-06-08', 'phone' => '08031234517', 'blood_group' => 'O+', 'allergies' => null],
            ['full_name' => 'Kelechi Promise Okafor', 'date_of_birth' => '2001-02-23', 'phone' => '08031234518', 'blood_group' => 'A-', 'allergies' => null],
            ['full_name' => 'Maryam Zainab Abdullahi', 'date_of_birth' => '1980-10-30', 'phone' => '08031234519', 'blood_group' => 'B+', 'allergies' => 'Iodine'],
            ['full_name' => 'Segun Olalekan Adekunle', 'date_of_birth' => '1973-08-15', 'phone' => '08031234520', 'blood_group' => 'O+', 'allergies' => null],
            ['full_name' => 'Ifeoma Patricia Uche', 'date_of_birth' => '1996-04-27', 'phone' => '08031234521', 'blood_group' => 'AB+', 'allergies' => null],
            ['full_name' => 'Abdulrahman Idris Musa', 'date_of_birth' => '1987-11-19', 'phone' => '08031234522', 'blood_group' => 'A+', 'allergies' => 'Eggs'],
            ['full_name' => 'Blessing Chidinma Obi', 'date_of_birth' => '2005-06-05', 'phone' => '08031234523', 'blood_group' => 'O-', 'allergies' => null],
            ['full_name' => 'Sani Kabiru Yusuf', 'date_of_birth' => '1965-01-09', 'phone' => '08031234524', 'blood_group' => 'B+', 'allergies' => 'Codeine'],
            ['full_name' => 'Omolara Deborah Ajayi', 'date_of_birth' => '1992-09-29', 'phone' => '08031234525', 'blood_group' => 'A+', 'allergies' => null],
        ];

        foreach ($patients as $data) {
            $patientService->createPatient($data, $admin->id);
        }
    }
}
