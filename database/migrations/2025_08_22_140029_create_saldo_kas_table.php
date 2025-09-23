<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
      Schema::create('saldo_kas', function (Blueprint $t){
        $t->id();
        $t->integer('saldo_kas')->default(0);
        $t->timestamps();
      });
    }
    public function down(){ Schema::dropIfExists('saldo_kas'); }
  };
