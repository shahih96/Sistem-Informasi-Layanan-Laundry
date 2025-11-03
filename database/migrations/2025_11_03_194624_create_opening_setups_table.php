<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('opening_setups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('init_cash')->default(0); // saldo kas awal
            $table->date('cutover_date')->nullable();            // default nanti kita isi hari ini
            $table->boolean('locked')->default(false);           // kunci agar tidak bisa diubah dari UI
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_setups');
    }
};
