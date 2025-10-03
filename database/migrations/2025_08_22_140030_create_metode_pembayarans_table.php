<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
      Schema::create('metode_pembayaran', function (Blueprint $t){
        $t->id();
        $t->enum('nama', ['tunai','qris','bon']);
        $t->timestamps();
      });
    }
    public function down(){ Schema::dropIfExists('metode_pembayaran'); }
  };