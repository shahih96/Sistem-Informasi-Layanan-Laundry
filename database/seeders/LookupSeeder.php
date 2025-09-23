<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use \App\Models\MetodePembayaran;
use \App\Models\Service;
use \App\Models\InformasiLaundry;
use \App\Models\SaldoKas;
use \App\Models\Fee;
use Carbon\Carbon;


class LookupSeeder extends Seeder
{
    public function run(): void
    {
        
        $now = Carbon::now();

        $service = [
            [
                'nama_service' => 'Cuci Lipat Reguler (/Kg)',
                'harga_service' => 4000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama_service' => 'Cuci Setrika Reguler (/Kg)',
                'harga_service' => 6000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama_service' => 'Setrika Express (/Kg)',
                'harga_service' => 7000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama_service' => 'Antar Jemput (<5KM)',
                'harga_service' => 5000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama_service' => 'Hordeng',
                'harga_service' => 45000,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        $metodePembayaran = [
            [
                'nama' => 'tunai',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama' => 'qris',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $infoLaundry = [
            [
                'nama_service' => 'Cuci Lipat Reguler (/Kg)',
                'harga_service' => 4000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama_service' => 'Cuci Setrika Reguler (/Kg)',
                'harga_service' => 6000,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Metode Pembayaran
        MetodePembayaran::insert($metodePembayaran);

        // Master Service (sample)
        Service::insert($service);

        // Informasi Laundry untuk landing (boleh sama dengan service)
        InformasiLaundry::insert($infoLaundry);

        // Saldo Kas (single row)
        SaldoKas::firstOrCreate(['id'=>1], ['saldo_kas'=>0]);

        // Fee (single row)
        Fee::firstOrCreate(['id'=>1], ['fee_lipat'=>0, 'fee_setrika'=>0]);
    }
}
