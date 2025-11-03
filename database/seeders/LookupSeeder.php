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
        $now = \Carbon\Carbon::now();

        // nama layanan yang termasuk fee-service (AJ)
        $feeSvcNames = [
            'Antar Jemput (<=5KM)',
            'Antar Jemput (>5KM)',
        ];

        $service = [
            ['nama_service' => 'Cuci Self Service Max 7Kg',  'harga_service' => 10000],
            ['nama_service' => 'Kering Self Service Max 7Kg', 'harga_service' => 10000],
            ['nama_service' => 'Setrika Express (/Kg)',      'harga_service' => 7000],
            ['nama_service' => 'Setrika Regular (/Kg)',      'harga_service' => 4000],
            ['nama_service' => 'Cuci Lipat Regular (/Kg)',   'harga_service' => 4000],
            ['nama_service' => 'Cuci Setrika Express 3Kg',   'harga_service' => 30000],
            ['nama_service' => 'Cuci Setrika Express 5Kg',   'harga_service' => 40000],
            ['nama_service' => 'Cuci Setrika Express 7Kg',   'harga_service' => 45000],
            ['nama_service' => 'Cuci Lipat Express Max 7Kg', 'harga_service' => 30000],
            ['nama_service' => 'Bed Cover',                  'harga_service' => 40000],
            ['nama_service' => 'Hordeng Besar Max 2pcs',     'harga_service' => 45000],
            ['nama_service' => 'Hordeng Kecil Max 3pcs',     'harga_service' => 45000],
            ['nama_service' => 'Cuci Setrika Regular (/Kg)', 'harga_service' => 6000],
            ['nama_service' => 'Deterjen',                   'harga_service' => 1000],
            ['nama_service' => 'Pewangi',                    'harga_service' => 1000],
            ['nama_service' => 'Proclin',                    'harga_service' => 1000],
            ['nama_service' => 'Plastik Asoy',               'harga_service' => 2000],
            ['nama_service' => 'Antar Jemput (<=5KM)',       'harga_service' => 5000],
            ['nama_service' => 'Antar Jemput (>5KM)',        'harga_service' => 10000],
            ['nama_service' => 'Boneka Kecil',               'harga_service' => 10000],
            ['nama_service' => 'Boneka Besar',               'harga_service' => 20000],
        ];

        // Normalisasi: tambahkan is_fee_service + timestamps ke SEMUA baris
        $service = array_map(function ($row) use ($feeSvcNames, $now) {
            $isFee = in_array($row['nama_service'], $feeSvcNames, true) ? 1 : 0;
            return [
                'nama_service'   => $row['nama_service'],
                'harga_service'  => $row['harga_service'],
                'is_fee_service' => $isFee,           // <-- kunci
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }, $service);

        // insert
        \App\Models\MetodePembayaran::insert([
            ['nama' => 'tunai', 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'qris',  'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'bon',   'created_at' => $now, 'updated_at' => $now],
        ]);

        \App\Models\Service::insert($service);

        \App\Models\SaldoKas::firstOrCreate(['id' => 1], ['saldo_kas' => 0]);
        \App\Models\Fee::firstOrCreate(['id' => 1], ['fee_lipat' => 0, 'fee_setrika' => 0]);
    }
}
