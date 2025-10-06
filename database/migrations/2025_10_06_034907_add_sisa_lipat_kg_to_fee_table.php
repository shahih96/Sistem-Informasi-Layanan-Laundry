<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee', function (Blueprint $table) {
            $table->integer('sisa_lipat_kg')->default(0)->after('fee_setrika');
            $table->date('sisa_lipat_kg_updated_at')->nullable()->after('sisa_lipat_kg');
        });
    }

    public function down(): void
    {
        Schema::table('fee', function (Blueprint $table) {
            $table->dropColumn(['sisa_lipat_kg', 'sisa_lipat_kg_updated_at']);
        });
    }
};
