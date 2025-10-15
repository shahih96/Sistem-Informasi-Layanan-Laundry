<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambah kolom hanya jika belum ada
        if (!Schema::hasColumn('rekap', 'pesanan_laundry_id')) {
            Schema::table('rekap', function (Blueprint $table) {
                $table->unsignedBigInteger('pesanan_laundry_id')->nullable()->after('id');
            });
        }

        // 2) Unique index (biar satu pesanan_laundry maksimal 1 baris rekap auto)
        if (!$this->indexExists('rekap', 'rekap_pesanan_laundry_id_unique')) {
            Schema::table('rekap', function (Blueprint $table) {
                $table->unique('pesanan_laundry_id', 'rekap_pesanan_laundry_id_unique');
            });
        }

        // 3) (Opsional) Foreign key ke tabel pesanan_laundry, abaikan jika sudah ada
        if (!$this->foreignKeyExists('rekap', 'rekap_pesanan_laundry_id_foreign')) {
            Schema::table('rekap', function (Blueprint $table) {
                $table->foreign('pesanan_laundry_id', 'rekap_pesanan_laundry_id_foreign')
                      ->references('id')->on('pesanan_laundry')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Hapus FK jika ada
        if ($this->foreignKeyExists('rekap', 'rekap_pesanan_laundry_id_foreign')) {
            Schema::table('rekap', function (Blueprint $table) {
                $table->dropForeign('rekap_pesanan_laundry_id_foreign');
            });
        }

        // Hapus unique index jika ada
        if ($this->indexExists('rekap', 'rekap_pesanan_laundry_id_unique')) {
            Schema::table('rekap', function (Blueprint $table) {
                $table->dropUnique('rekap_pesanan_laundry_id_unique');
            });
        }

        // (Opsional) Jangan drop kolom bila sudah dipakai di lingkungan lain.
        // Kalau memang mau dibalik, uncomment di bawah (hanya jika perlu):
        // if (Schema::hasColumn('rekap', 'pesanan_laundry_id')) {
        //     Schema::table('rekap', function (Blueprint $table) {
        //         $table->dropColumn('pesanan_laundry_id');
        //     });
        // }
    }

    private function indexExists(string $table, string $index): bool
    {
        $dbName = DB::getDatabaseName();
        $result = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ? AND index_name = ?
            LIMIT 1
        ", [$dbName, $table, $index]);

        return (bool) $result;
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $dbName = DB::getDatabaseName();
        $result = DB::selectOne("
            SELECT 1
            FROM information_schema.table_constraints
            WHERE constraint_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'
            LIMIT 1
        ", [$dbName, $table, $constraint]);

        return (bool) $result;
    }
};
