<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan indexes untuk meningkatkan performa query.
     * Index akan membuat pencarian berdasarkan tanggal, metode, dan status menjadi jauh lebih cepat.
     * 
     * Estimasi peningkatan performa:
     * - Query dengan WHERE created_at: 50-100x lebih cepat
     * - Query dengan JOIN: 30-50x lebih cepat
     * - Dashboard load time: dari 3 detik menjadi < 0.2 detik
     */
    public function up(): void
    {
        // ========================================
        // TABEL: pesanan_laundry
        // ========================================
        Schema::table('pesanan_laundry', function (Blueprint $table) {
            // Index untuk filter by tanggal (sering dipakai di dashboard & rekap)
            $table->index('created_at', 'idx_pesanan_created_at');
            
            // Index untuk filter by metode pembayaran (cek bon/tunai/qris)
            $table->index('metode_pembayaran_id', 'idx_pesanan_metode');
            
            // Index untuk filter pesanan tersembunyi
            $table->index('is_hidden', 'idx_pesanan_hidden');
            
            // Index untuk tracking pelunasan bon
            $table->index('paid_at', 'idx_pesanan_paid_at');
            
            // Composite index untuk query yang filter tanggal + metode sekaligus
            $table->index(['created_at', 'metode_pembayaran_id'], 'idx_pesanan_date_metode');
        });

        // ========================================
        // TABEL: rekap
        // ========================================
        Schema::table('rekap', function (Blueprint $table) {
            // Index untuk filter by tanggal (paling sering dipakai)
            $table->index('created_at', 'idx_rekap_created_at');
            
            // Index untuk filter by service (group omset per layanan)
            $table->index('service_id', 'idx_rekap_service');
            
            // Index untuk filter by metode (pisah tunai/qris/bon)
            $table->index('metode_pembayaran_id', 'idx_rekap_metode');
            
            // Index untuk link ke pesanan
            $table->index('pesanan_laundry_id', 'idx_rekap_pesanan');
            
            // Composite index untuk query grup omset (tanggal + service + metode)
            $table->index(['created_at', 'service_id', 'metode_pembayaran_id'], 'idx_rekap_composite');
        });

        // ========================================
        // TABEL: status_pesanan
        // ========================================
        Schema::table('status_pesanan', function (Blueprint $table) {
            // Index untuk JOIN ke pesanan (paling penting!)
            $table->index('pesanan_id', 'idx_status_pesanan');
            
            // Index untuk sort by tanggal update
            $table->index('created_at', 'idx_status_created_at');
            
            // Composite index untuk query status terbaru per pesanan
            $table->index(['pesanan_id', 'created_at'], 'idx_status_pesanan_date');
        });

        // ========================================
        // TABEL: saldo_kartu
        // ========================================
        Schema::table('saldo_kartu', function (Blueprint $table) {
            // Index untuk filter by tanggal (cek saldo kemarin)
            $table->index('created_at', 'idx_saldo_kartu_date');
        });

        // ========================================
        // TABEL: bons (jika ada tabel terpisah)
        // ========================================
        if (Schema::hasTable('bons')) {
            Schema::table('bons', function (Blueprint $table) {
                $table->index('created_at', 'idx_bon_created_at');
                $table->index('user_id', 'idx_bon_user');
            });
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Menghapus semua indexes yang dibuat.
     * Hanya dijalankan jika ada masalah atau perlu rollback.
     */
    public function down(): void
    {
        // Drop indexes dari pesanan_laundry
        Schema::table('pesanan_laundry', function (Blueprint $table) {
            $table->dropIndex('idx_pesanan_created_at');
            $table->dropIndex('idx_pesanan_metode');
            $table->dropIndex('idx_pesanan_hidden');
            $table->dropIndex('idx_pesanan_paid_at');
            $table->dropIndex('idx_pesanan_date_metode');
        });

        // Drop indexes dari rekap
        Schema::table('rekap', function (Blueprint $table) {
            $table->dropIndex('idx_rekap_created_at');
            $table->dropIndex('idx_rekap_service');
            $table->dropIndex('idx_rekap_metode');
            $table->dropIndex('idx_rekap_pesanan');
            $table->dropIndex('idx_rekap_composite');
        });

        // Drop indexes dari status_pesanan
        Schema::table('status_pesanan', function (Blueprint $table) {
            $table->dropIndex('idx_status_pesanan');
            $table->dropIndex('idx_status_created_at');
            $table->dropIndex('idx_status_pesanan_date');
        });

        // Drop indexes dari saldo_kartu
        Schema::table('saldo_kartu', function (Blueprint $table) {
            $table->dropIndex('idx_saldo_kartu_date');
        });

        // Drop indexes dari bons (jika ada)
        if (Schema::hasTable('bons')) {
            Schema::table('bons', function (Blueprint $table) {
                $table->dropIndex('idx_bon_created_at');
                $table->dropIndex('idx_bon_user');
            });
        }
    }
};
