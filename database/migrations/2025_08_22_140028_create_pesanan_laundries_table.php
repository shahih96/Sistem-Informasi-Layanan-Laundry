<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
      Schema::create('pesanan_laundry', function (Blueprint $t){
        $t->id();
        $t->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
        $t->string('nama_pel');
        $t->string('no_hp_pel', 20);
        $t->foreignId('admin_id')->constrained('users')->cascadeOnDelete(); // pengelola
        $t->timestamps();
      });
    }
    public function down(){ Schema::dropIfExists('pesanan_laundry'); }
  };