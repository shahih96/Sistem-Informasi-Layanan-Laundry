<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
      Schema::create('rekap', function (Blueprint $t){
        $t->id();
        $t->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
        $t->foreignId('metode_pembayaran_id')->nullable()->constrained('metode_pembayaran')->nullOnDelete();
        $t->foreignId('bon_id')->nullable()->constrained('bon')->nullOnDelete();
        $t->foreignId('saldo_kas_id')->nullable()->constrained('saldo_kas')->nullOnDelete();
        $t->foreignId('fee_id')->nullable()->constrained('fee')->nullOnDelete();
        // kolom opsional untuk laporan ringkas
        $t->string('keterangan')->nullable();
        $t->unsignedInteger('qty')->default(1);
        $t->integer('subtotal')->default(0);
        $t->integer('total')->default(0);
        $t->timestamps();
      });
    }
    public function down(){ Schema::dropIfExists('rekap'); }
  };