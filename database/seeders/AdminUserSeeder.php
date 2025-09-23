<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin default untuk login dashboard
        User::updateOrCreate(
            ['email' => 'admin@qxpress.test'],
            [
                'name'      => 'Administrator',
                'password'  => Hash::make('admin12345'), // ganti bila perlu
                'is_admin'  => true,                     // pastikan kolom ini ada (boolean)
                'email_verified_at' => now(),
            ]
        );

        // Optional: user non-admin (contoh)
        User::updateOrCreate(
            ['email' => 'staff@qxpress.test'],
            [
                'name'      => 'Staff Laundry',
                'password'  => Hash::make('staff12345'),
                'is_admin'  => false,
                'email_verified_at' => now(),
            ]
        );
    }
}
