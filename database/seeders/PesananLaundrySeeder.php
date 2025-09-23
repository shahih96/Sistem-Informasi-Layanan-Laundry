<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Service;
use App\Models\PesananLaundry;

class PesananLaundrySeeder extends Seeder
{
    public function run(): void
    {
        // Ambil id acak yang sudah ada; fallback ke 1 bila kosong
        $serviceId = Service::inRandomOrder()->value('id') ?? 1;
        $adminId   = User::inRandomOrder()->value('id') ?? 1;

        $rows = [
            [
                'service_id' => $serviceId,
                'nama_pel'   => 'Andi Saputra',
                'no_hp_pel'  => '082176386384', // request khusus
                'admin_id'   => $adminId,
            ],
            [
                'service_id' => $serviceId,
                'nama_pel'   => 'Siti Rahma',
                'no_hp_pel'  => '081234567810',
                'admin_id'   => $adminId,
            ],
            [
                'service_id' => $serviceId,
                'nama_pel'   => 'Budi Pratama',
                'no_hp_pel'  => '085212345678',
                'admin_id'   => $adminId,
            ],
            [
                'service_id' => $serviceId,
                'nama_pel'   => 'Nia Lestari',
                'no_hp_pel'  => '089512341234',
                'admin_id'   => $adminId,
            ],
            [
                'service_id' => $serviceId,
                'nama_pel'   => 'Rizky Maulana',
                'no_hp_pel'  => '087812341234',
                'admin_id'   => $adminId,
            ],
        ];

        foreach ($rows as $data) {
            PesananLaundry::create($data);
        }
    }
}
