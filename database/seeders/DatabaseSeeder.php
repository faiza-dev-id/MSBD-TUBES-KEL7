<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ReferensiDataSeeder::class,
            WilayahSeeder::class,
            MasterPenilaianSeeder::class,
            DemoDataSeeder::class,
            DemoRoleAccountsSeeder::class,
        ]);
    }
}
