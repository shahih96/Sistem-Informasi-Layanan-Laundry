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
            ['email' => 'qxpresslaundry@gmail.com'],
            [
                'name'      => 'Administrator',
                'password'  => Hash::make('qxlairan139'),
                'is_admin'  => true,                     
                'email_verified_at' => now(),
            ]
        );
    }
}
