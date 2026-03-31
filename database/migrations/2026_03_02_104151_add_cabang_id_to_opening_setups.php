<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('opening_setups')) {
            Schema::table('opening_setups', function (Blueprint $table) {
                // Tambah kolom cabang_id dengan default 1 (Airan)
                $table->unsignedBigInteger('cabang_id')->default(1)->after('id');
                
                // Tambah foreign key constraint
                $table->foreign('cabang_id')
                    ->references('id')
                    ->on('cabang')
                    ->onDelete('restrict');
            });

            // Update semua data existing ke cabang_id = 1 (Airan)
            DB::table('opening_setups')->whereNull('cabang_id')->update(['cabang_id' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('opening_setups')) {
            Schema::table('opening_setups', function (Blueprint $table) {
                $table->dropForeign(['cabang_id']);
                $table->dropColumn('cabang_id');
            });
        }
    }
};
