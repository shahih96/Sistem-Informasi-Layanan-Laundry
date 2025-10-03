<?php

// database/migrations/xxxx_alter_pesanan_laundry_add_metode_drop_pembayaran.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan_laundry', function (Blueprint $t) {
            if (!Schema::hasColumn('pesanan_laundry','metode_pembayaran_id')) {
                $t->foreignId('metode_pembayaran_id')
                  ->nullable()
                  ->constrained('metode_pembayaran')
                  ->nullOnDelete()
                  ->after('qty');
            }
            if (Schema::hasColumn('pesanan_laundry','pembayaran')) {
                $t->dropColumn('pembayaran');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_laundry', function (Blueprint $t) {
            if (!Schema::hasColumn('pesanan_laundry','pembayaran')) {
                $t->enum('pembayaran',['lunas','belum_lunas'])->default('belum_lunas');
            }
            if (Schema::hasColumn('pesanan_laundry','metode_pembayaran_id')) {
                $t->dropConstrainedForeignId('metode_pembayaran_id');
            }
        });
    }
};
