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
            ['nama_service' => 'Cuci Self Service Max 7Kg',        'harga_service' => 10000, 'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Kering Self Service Max 7Kg',      'harga_service' => 10000, 'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Setrika Express (/Kg)',            'harga_service' => 7000,  'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Setrika Regular (/Kg)',            'harga_service' => 4000,  'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Cuci Lipat Regular (/Kg)',         'harga_service' => 4000,  'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Cuci Setrika Express 3Kg',         'harga_service' => 30000, 'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Cuci Setrika Express 5Kg',         'harga_service' => 40000, 'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Cuci Setrika Express 7Kg',         'harga_service' => 45000, 'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Cuci Lipat Express Max 7Kg',       'harga_service' => 30000, 'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Bed Cover',                        'harga_service' => 40000, 'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Hordeng Besar Max 2pcs',           'harga_service' => 45000, 'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Hordeng Kecil Max 3pcs',           'harga_service' => 45000, 'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Cuci Setrika Regular (/Kg)',       'harga_service' => 6000,  'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Deterjen',                         'harga_service' => 1000,  'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Pewangi',                          'harga_service' => 1000,  'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Proclin',                          'harga_service' => 1000,  'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Plastik Asoy',                     'harga_service' => 2000,  'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Antar Jemput (<=5KM)',             'harga_service' => 5000,  'created_at' => $now, 'updated_at' => $now],
            ['nama_service' => 'Antar Jemput (>5KM)',              'harga_service' => 10000, 'created_at' => $now, 'updated_at' => $now],
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
            [
                'nama' => 'bon',
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
