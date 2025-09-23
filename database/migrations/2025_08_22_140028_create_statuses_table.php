<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
      Schema::create('status_pesanan', function (Blueprint $t){
        $t->id();
        $t->foreignId('pesanan_id')->constrained('pesanan_laundry')->cascadeOnDelete();
        $t->string('keterangan'); // contoh: "Diterima", "Proses cuci", "Selesai", "Diantar"
        $t->timestamps();
      });
    }
    public function down(){ Schema::dropIfExists('status_pesanan'); }
  };