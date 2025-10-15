<?php

// database/migrations/2025_10_15_180000_add_is_hidden_to_pesanan_laundry.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('pesanan_laundry', 'is_hidden')) {
            Schema::table('pesanan_laundry', function (Blueprint $table) {
                $table->boolean('is_hidden')->default(false)->after('qty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pesanan_laundry', 'is_hidden')) {
            Schema::table('pesanan_laundry', function (Blueprint $table) {
                $table->dropColumn('is_hidden');
            });
        }
    }
};
