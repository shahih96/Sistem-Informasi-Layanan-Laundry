<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan_laundry', function (Blueprint $table) {
            $table->unsignedInteger('harga_satuan')->nullable()->after('service_id');
        });

        Schema::table('rekap', function (Blueprint $table) {
            $table->unsignedInteger('harga_satuan')->nullable()->after('service_id');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_laundry', function (Blueprint $table) {
            $table->dropColumn('harga_satuan');
        });

        Schema::table('rekap', function (Blueprint $table) {
            $table->dropColumn('harga_satuan');
        });
    }
};
