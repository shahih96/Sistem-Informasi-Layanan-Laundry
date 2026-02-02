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
        Schema::table('pesanan_laundry', function (Blueprint $table) {
            $table->foreignId('antar_jemput_service_id')->nullable()->after('service_id')->constrained('services')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesanan_laundry', function (Blueprint $table) {
            $table->dropForeign(['antar_jemput_service_id']);
            $table->dropColumn('antar_jemput_service_id');
        });
    }
};
