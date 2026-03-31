<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Daftar tabel yang perlu ditambahkan cabang_id
        $tables = [
            'users',
            'admins',
            'pesanan_laundry',    // SINGULAR, bukan pesanan_laundries
            'bon',                // SINGULAR, bukan bons
            'rekap',              // SINGULAR, bukan rekaps
            'saldo_bon',
            'saldo_kartu',
            'saldo_kas',
            'fee',                // SINGULAR, bukan fees
            'status_pesanan',
        ];

        foreach ($tables as $tableName) {
            // Cek apakah tabel ada
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                // Tambah kolom cabang_id dengan default 1 (Airan)
                $table->unsignedBigInteger('cabang_id')->default(1)->after('id');
                
                // Tambah foreign key constraint
                $table->foreign('cabang_id')
                    ->references('id')
                    ->on('cabang')
                    ->onDelete('restrict'); // Tidak boleh hapus cabang jika masih ada data
            });

            // Update semua data existing ke cabang_id = 1 (Airan)
            // Ini penting untuk data production yang sudah ada
            DB::table($tableName)->whereNull('cabang_id')->update(['cabang_id' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'admins',
            'pesanan_laundry',
            'bon',
            'rekap',
            'saldo_bon',
            'saldo_kartu',
            'saldo_kas',
            'fee',
            'status_pesanan',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['cabang_id']);
                $table->dropColumn('cabang_id');
            });
        }
    }
};
