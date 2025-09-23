<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
      Schema::create('saldo_kartu', function (Blueprint $t){
        $t->id();
        $t->integer('saldo_awal')->default(0);
        $t->integer('saldo_baru')->default(0);
        $t->unsignedInteger('tap_hari_ini')->default(0);
        $t->unsignedInteger('tap_gagal')->default(0);
        $t->timestamps();
      });
    }
    public function down(){ Schema::dropIfExists('saldo_kartu'); }
  };
