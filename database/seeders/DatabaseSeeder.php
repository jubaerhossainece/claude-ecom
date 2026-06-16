<?php

namespace Database\Seeders;

use Database\Seeders\BangladeshGeoSeeder;
use Database\Seeders\FakeDataSeeder;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BangladeshGeoSeeder::class,
            StoreSeeder::class,
            RolesAndAdminSeeder::class,
            FakeDataSeeder::class,
        ]);
    }
}
