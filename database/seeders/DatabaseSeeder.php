<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,   // user admin + staff
            LookupSeeder::class,      // metode pembayaran, services, informasi, saldo_kas, fee
        ]);
    }
}
