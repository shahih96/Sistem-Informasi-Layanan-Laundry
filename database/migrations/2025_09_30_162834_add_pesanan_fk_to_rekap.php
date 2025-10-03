<?php

// database/migrations/xxxx_add_pesanan_fk_to_rekap.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rekap', function (Blueprint $t) {
            if (!Schema::hasColumn('rekap','pesanan_laundry_id')) {
                $t->foreignId('pesanan_laundry_id')
                  ->nullable()
                  ->constrained('pesanan_laundry')
                  ->nullOnDelete()
                  ->after('service_id');
            }
        });
    }
    public function down(): void
    {
        Schema::table('rekap', function (Blueprint $t) {
            if (Schema::hasColumn('rekap','pesanan_laundry_id')) {
                $t->dropConstrainedForeignId('pesanan_laundry_id');
            }
        });
    }
};

