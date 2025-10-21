<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan_laundry', function (Blueprint $t) {
            if (!Schema::hasColumn('pesanan_laundry','paid_at')) {
                $t->timestamp('paid_at')->nullable()->index();
            }
            if (!Schema::hasColumn('pesanan_laundry','hidden_at')) {
                $t->timestamp('hidden_at')->nullable()->index();
            }
        });

        DB::statement("
            UPDATE pesanan_laundry pl
            JOIN metode_pembayaran mp ON mp.id = pl.metode_pembayaran_id
            SET pl.paid_at = pl.updated_at
            WHERE pl.paid_at IS NULL
              AND LOWER(mp.nama) IN ('tunai','qris')
        ");
    }

    public function down(): void
    {
        Schema::table('pesanan_laundry', function (Blueprint $t) {
            if (Schema::hasColumn('pesanan_laundry','paid_at')) {
                $t->dropColumn('paid_at');
            }
            if (Schema::hasColumn('pesanan_laundry','hidden_at')) {
                $t->dropColumn('hidden_at');
            }
        });
    }
};
