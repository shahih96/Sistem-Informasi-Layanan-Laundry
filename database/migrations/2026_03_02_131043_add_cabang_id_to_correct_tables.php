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
        // Daftar tabel yang BELUM punya cabang_id (nama tabel yang BENAR!)
        $tables = [
            'pesanan_laundry',    // SINGULAR!
            'bon',                // SINGULAR!
            'rekap',              // SINGULAR!
            'saldo_bon',
            'saldo_kartu',
            'saldo_kas',
            'fee',                // SINGULAR!
            'status_pesanan',
            'opening_setups',
        ];

        foreach ($tables as $tableName) {
            // Cek apakah tabel ada dan belum punya cabang_id
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'cabang_id')) {
                continue; // Skip jika sudah punya
            }

            Schema::table($tableName, function (Blueprint $table) {
                // Tambah kolom cabang_id dengan default 1 (Airan)
                $table->unsignedBigInteger('cabang_id')->default(1)->after('id');
                
                // Tambah foreign key constraint
                $table->foreign('cabang_id')
                    ->references('id')
                    ->on('cabang')
                    ->onDelete('restrict');
            });

            // Update semua data existing ke cabang_id = 1 (Airan)
            DB::table($tableName)->whereNull('cabang_id')->update(['cabang_id' => 1]);
            
            echo "✅ Added cabang_id to {$tableName}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'pesanan_laundry',
            'bon',
            'rekap',
            'saldo_bon',
            'saldo_kartu',
            'saldo_kas',
            'fee',
            'status_pesanan',
            'opening_setups',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'cabang_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['cabang_id']);
                $table->dropColumn('cabang_id');
            });
        }
    }
};
