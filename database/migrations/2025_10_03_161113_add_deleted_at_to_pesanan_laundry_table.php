<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan_laundry', function (Blueprint $table) {
            $table->softDeletes(); // menambah kolom deleted_at nullable
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_laundry', function (Blueprint $table) {
            $table->dropSoftDeletes(); // menghapus kolom deleted_at
        });
    }
};
