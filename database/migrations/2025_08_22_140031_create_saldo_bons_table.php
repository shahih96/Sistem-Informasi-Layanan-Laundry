<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
      Schema::create('saldo_bon', function (Blueprint $t){
        $t->id();
        $t->foreignId('bon_id')->constrained('bon')->cascadeOnDelete();
        $t->foreignId('saldo_kartu_id')->nullable()->constrained('saldo_kartu')->nullOnDelete();
        $t->integer('total_bon')->default(0);
        $t->timestamps();
      });
    }
    public function down(){ Schema::dropIfExists('saldo_bon'); }
  };