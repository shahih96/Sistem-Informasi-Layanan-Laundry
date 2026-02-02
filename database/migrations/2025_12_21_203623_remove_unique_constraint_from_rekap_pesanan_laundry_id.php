<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rekap', function (Blueprint $table) {
            // Drop unique constraint to allow multiple rekap entries per pesanan
            // (for main service + antar jemput)
            $table->dropUnique('rekap_pesanan_laundry_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap', function (Blueprint $table) {
            // Re-add unique constraint if rolled back
            $table->unique('pesanan_laundry_id', 'rekap_pesanan_laundry_id_unique');
        });
    }
};
