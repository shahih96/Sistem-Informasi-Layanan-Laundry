<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saldo_kartu', function (Blueprint $table) {
            $table->integer('manual_total_tap')->nullable()->after('tap_gagal');
        });
    }
    
    public function down(): void
    {
        Schema::table('saldo_kartu', function (Blueprint $table) {
            $table->dropColumn('manual_total_tap');
        });
    }
};
