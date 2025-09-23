<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
      Schema::create('bon', function (Blueprint $t){
        $t->id();
        $t->foreignId('service_id')->constrained('services')->cascadeOnDelete();
        $t->string('nama_pel');
        $t->enum('status_bon', ['dibuat','dibayar','batal'])->default('dibuat');
        $t->timestamps();
      });
    }
    public function down(){ Schema::dropIfExists('bon'); }
  };