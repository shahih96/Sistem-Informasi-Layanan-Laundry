<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
      Schema::create('fee', function (Blueprint $t){
        $t->id();
        $t->unsignedInteger('fee_lipat')->default(0);
        $t->unsignedInteger('fee_setrika')->default(0);
        $t->timestamps();
      });
    }
    public function down(){ Schema::dropIfExists('fee'); }
  };