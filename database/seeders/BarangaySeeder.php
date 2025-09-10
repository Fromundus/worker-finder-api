<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/barangays.json');
        $barangays = json_decode(file_get_contents($path), true);

        foreach ($barangays as $b) {
            Location::updateOrCreate(
                [
                    'lat' => $b['lat'],
                    'lng' => $b['lng'],
                ],
                [
                    'barangay' => $b['name'],
                    'municipality' => $b['municipality'],
                ]
            );
        }
    }
}
