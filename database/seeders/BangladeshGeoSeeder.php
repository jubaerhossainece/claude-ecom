<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Division;
use App\Models\Thana;
use Illuminate\Database\Seeder;

class BangladeshGeoSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('data/bangladesh-geo.json')),
            associative: true,
        );

        foreach ($data as $divisionData) {
            $division = Division::create([
                'name' => $divisionData['name'],
                'bn_name' => $divisionData['bn_name'],
            ]);

            foreach ($divisionData['districts'] as $districtData) {
                $district = District::create([
                    'division_id' => $division->id,
                    'name' => $districtData['name'],
                    'bn_name' => $districtData['bn_name'],
                ]);

                foreach ($districtData['thanas'] as $thanaData) {
                    Thana::create([
                        'district_id' => $district->id,
                        'name' => $thanaData['name'],
                        'bn_name' => $thanaData['bn_name'],
                    ]);
                }
            }
        }
    }
}
