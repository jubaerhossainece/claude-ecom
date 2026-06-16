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
        $data = $this->geoData();

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

    private function geoData(): array
    {
        return [
            [
                'name' => 'Dhaka',
                'bn_name' => 'ঢাকা',
                'districts' => [
                    [
                        'name' => 'Dhaka',
                        'bn_name' => 'ঢাকা',
                        'thanas' => [
                            ['name' => 'Adabor', 'bn_name' => 'আদাবর'],
                            ['name' => 'Badda', 'bn_name' => 'বাড্ডা'],
                            ['name' => 'Banani', 'bn_name' => 'বনানী'],
                            ['name' => 'Dhanmondi', 'bn_name' => 'ধানমন্ডি'],
                            ['name' => 'Gulshan', 'bn_name' => 'গুলশান'],
                            ['name' => 'Kafrul', 'bn_name' => 'কাফরুল'],
                            ['name' => 'Khilgaon', 'bn_name' => 'খিলগাঁও'],
                            ['name' => 'Khilkhet', 'bn_name' => 'খিলক্ষেত'],
                            ['name' => 'Mirpur', 'bn_name' => 'মিরপুর'],
                            ['name' => 'Mohammadpur', 'bn_name' => 'মোহাম্মদপুর'],
                            ['name' => 'Motijheel', 'bn_name' => 'মতিঝিল'],
                            ['name' => 'Pallabi', 'bn_name' => 'পল্লবী'],
                            ['name' => 'Rampura', 'bn_name' => 'রামপুরা'],
                            ['name' => 'Sabujbagh', 'bn_name' => 'সবুজবাগ'],
                            ['name' => 'Shahbag', 'bn_name' => 'শাহবাগ'],
                            ['name' => 'Tejgaon', 'bn_name' => 'তেজগাঁও'],
                            ['name' => 'Uttara', 'bn_name' => 'উত্তরা'],
                            ['name' => 'Wari', 'bn_name' => 'ওয়ারী'],
                        ],
                    ],
                    [
                        'name' => 'Gazipur',
                        'bn_name' => 'গাজীপুর',
                        'thanas' => [
                            ['name' => 'Gazipur Sadar', 'bn_name' => 'গাজীপুর সদর'],
                            ['name' => 'Tongi', 'bn_name' => 'টঙ্গী'],
                            ['name' => 'Kaliakair', 'bn_name' => 'কালিয়াকৈর'],
                            ['name' => 'Kapasia', 'bn_name' => 'কাপাসিয়া'],
                            ['name' => 'Sreepur', 'bn_name' => 'শ্রীপুর'],
                        ],
                    ],
                    [
                        'name' => 'Narayanganj',
                        'bn_name' => 'নারায়ণগঞ্জ',
                        'thanas' => [
                            ['name' => 'Narayanganj Sadar', 'bn_name' => 'নারায়ণগঞ্জ সদর'],
                            ['name' => 'Rupganj', 'bn_name' => 'রূপগঞ্জ'],
                            ['name' => 'Araihazar', 'bn_name' => 'আড়াইহাজার'],
                            ['name' => 'Sonargaon', 'bn_name' => 'সোনারগাঁও'],
                        ],
                    ],
                    [
                        'name' => 'Manikganj',
                        'bn_name' => 'মানিকগঞ্জ',
                        'thanas' => [
                            ['name' => 'Manikganj Sadar', 'bn_name' => 'মানিকগঞ্জ সদর'],
                            ['name' => 'Singair', 'bn_name' => 'সিংগাইর'],
                            ['name' => 'Shibalaya', 'bn_name' => 'শিবালয়'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Chittagong',
                'bn_name' => 'চট্টগ্রাম',
                'districts' => [
                    [
                        'name' => 'Chittagong',
                        'bn_name' => 'চট্টগ্রাম',
                        'thanas' => [
                            ['name' => 'Akbarshah', 'bn_name' => 'আকবরশাহ'],
                            ['name' => 'Bayazid', 'bn_name' => 'বায়েজিদ'],
                            ['name' => 'Chandgaon', 'bn_name' => 'চান্দগাঁও'],
                            ['name' => 'Double Mooring', 'bn_name' => 'ডবলমুরিং'],
                            ['name' => 'Halishahar', 'bn_name' => 'হালিশহর'],
                            ['name' => 'Kotwali', 'bn_name' => 'কোতোয়ালি'],
                            ['name' => 'Pahartali', 'bn_name' => 'পাহাড়তলী'],
                            ['name' => 'Panchlaish', 'bn_name' => 'পাঁচলাইশ'],
                        ],
                    ],
                    [
                        'name' => 'Cox\'s Bazar',
                        'bn_name' => 'কক্সবাজার',
                        'thanas' => [
                            ['name' => 'Cox\'s Bazar Sadar', 'bn_name' => 'কক্সবাজার সদর'],
                            ['name' => 'Chakaria', 'bn_name' => 'চকরিয়া'],
                            ['name' => 'Teknaf', 'bn_name' => 'টেকনাফ'],
                        ],
                    ],
                    [
                        'name' => 'Comilla',
                        'bn_name' => 'কুমিল্লা',
                        'thanas' => [
                            ['name' => 'Comilla Sadar', 'bn_name' => 'কুমিল্লা সদর'],
                            ['name' => 'Chandina', 'bn_name' => 'চান্দিনা'],
                            ['name' => 'Debidwar', 'bn_name' => 'দেবিদ্বার'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Rajshahi',
                'bn_name' => 'রাজশাহী',
                'districts' => [
                    [
                        'name' => 'Rajshahi',
                        'bn_name' => 'রাজশাহী',
                        'thanas' => [
                            ['name' => 'Boalia', 'bn_name' => 'বোয়ালিয়া'],
                            ['name' => 'Rajpara', 'bn_name' => 'রাজপাড়া'],
                            ['name' => 'Motihar', 'bn_name' => 'মতিহার'],
                            ['name' => 'Shah Makhdum', 'bn_name' => 'শাহ মখদুম'],
                        ],
                    ],
                    [
                        'name' => 'Bogura',
                        'bn_name' => 'বগুড়া',
                        'thanas' => [
                            ['name' => 'Bogura Sadar', 'bn_name' => 'বগুড়া সদর'],
                            ['name' => 'Shibganj', 'bn_name' => 'শিবগঞ্জ'],
                            ['name' => 'Gabtali', 'bn_name' => 'গাবতলী'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Khulna',
                'bn_name' => 'খুলনা',
                'districts' => [
                    [
                        'name' => 'Khulna',
                        'bn_name' => 'খুলনা',
                        'thanas' => [
                            ['name' => 'Khulna Sadar', 'bn_name' => 'খুলনা সদর'],
                            ['name' => 'Khalishpur', 'bn_name' => 'খালিশপুর'],
                            ['name' => 'Khan Jahan Ali', 'bn_name' => 'খান জাহান আলী'],
                        ],
                    ],
                    [
                        'name' => 'Jessore',
                        'bn_name' => 'যশোর',
                        'thanas' => [
                            ['name' => 'Jessore Sadar', 'bn_name' => 'যশোর সদর'],
                            ['name' => 'Benapole', 'bn_name' => 'বেনাপোল'],
                            ['name' => 'Abhaynagar', 'bn_name' => 'অভয়নগর'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Sylhet',
                'bn_name' => 'সিলেট',
                'districts' => [
                    [
                        'name' => 'Sylhet',
                        'bn_name' => 'সিলেট',
                        'thanas' => [
                            ['name' => 'Sylhet Sadar', 'bn_name' => 'সিলেট সদর'],
                            ['name' => 'Beanibazar', 'bn_name' => 'বিয়ানীবাজার'],
                            ['name' => 'Jaintiapur', 'bn_name' => 'জৈন্তাপুর'],
                        ],
                    ],
                    [
                        'name' => 'Moulvibazar',
                        'bn_name' => 'মৌলভীবাজার',
                        'thanas' => [
                            ['name' => 'Moulvibazar Sadar', 'bn_name' => 'মৌলভীবাজার সদর'],
                            ['name' => 'Sreemangal', 'bn_name' => 'শ্রীমঙ্গল'],
                            ['name' => 'Kulaura', 'bn_name' => 'কুলাউড়া'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Barishal',
                'bn_name' => 'বরিশাল',
                'districts' => [
                    [
                        'name' => 'Barishal',
                        'bn_name' => 'বরিশাল',
                        'thanas' => [
                            ['name' => 'Barishal Sadar', 'bn_name' => 'বরিশাল সদর'],
                            ['name' => 'Babuganj', 'bn_name' => 'বাবুগঞ্জ'],
                            ['name' => 'Agailjhara', 'bn_name' => 'আগৈলঝাড়া'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Rangpur',
                'bn_name' => 'রংপুর',
                'districts' => [
                    [
                        'name' => 'Rangpur',
                        'bn_name' => 'রংপুর',
                        'thanas' => [
                            ['name' => 'Rangpur Sadar', 'bn_name' => 'রংপুর সদর'],
                            ['name' => 'Badarganj', 'bn_name' => 'বদরগঞ্জ'],
                            ['name' => 'Mithapukur', 'bn_name' => 'মিঠাপুকুর'],
                        ],
                    ],
                    [
                        'name' => 'Dinajpur',
                        'bn_name' => 'দিনাজপুর',
                        'thanas' => [
                            ['name' => 'Dinajpur Sadar', 'bn_name' => 'দিনাজপুর সদর'],
                            ['name' => 'Birampur', 'bn_name' => 'বিরামপুর'],
                            ['name' => 'Chirirbandar', 'bn_name' => 'চিরিরবন্দর'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Mymensingh',
                'bn_name' => 'ময়মনসিংহ',
                'districts' => [
                    [
                        'name' => 'Mymensingh',
                        'bn_name' => 'ময়মনসিংহ',
                        'thanas' => [
                            ['name' => 'Mymensingh Sadar', 'bn_name' => 'ময়মনসিংহ সদর'],
                            ['name' => 'Trishal', 'bn_name' => 'ত্রিশাল'],
                            ['name' => 'Bhaluka', 'bn_name' => 'ভালুকা'],
                        ],
                    ],
                    [
                        'name' => 'Tangail',
                        'bn_name' => 'টাঙ্গাইল',
                        'thanas' => [
                            ['name' => 'Tangail Sadar', 'bn_name' => 'টাঙ্গাইল সদর'],
                            ['name' => 'Mirzapur', 'bn_name' => 'মির্জাপুর'],
                            ['name' => 'Ghatail', 'bn_name' => 'ঘাটাইল'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
