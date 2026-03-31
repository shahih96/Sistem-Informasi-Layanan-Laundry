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
        Schema::create('cabang', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique()->comment('Kode unik cabang: airan, kopi');
            $table->string('nama', 100)->comment('Nama lengkap cabang');
            $table->string('alamat', 255)->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->timestamps();
        });

        // Insert data cabang default (Airan dan Kopi)
        DB::table('cabang')->insert([
            [
                'id' => 1,
                'kode' => 'airan',
                'nama' => 'Qxpress Laundry Airan',
                'alamat' => null,
                'no_telepon' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'kode' => 'kopi',
                'nama' => 'Qxpress Laundry Kopi',
                'alamat' => null,
                'no_telepon' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabang');
    }
};
