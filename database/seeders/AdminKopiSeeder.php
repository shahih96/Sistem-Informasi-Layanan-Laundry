<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminKopiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat admin untuk cabang Kopi
        User::create([
            'name' => 'Admin Kopi',
            'email' => 'kopi@qxpress.com',
            'password' => Hash::make('qxlkopi10'),
            'is_admin' => true,
            'cabang_id' => 2,
            'email_verified_at' => now(),
        ]);

    }
}
