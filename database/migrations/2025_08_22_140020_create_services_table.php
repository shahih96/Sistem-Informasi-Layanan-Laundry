<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
        Schema::create('services', function (Blueprint $t){
            $t->id();
            $t->string('nama_service');
            $t->unsignedInteger('harga_service');
            $t->timestamps();
        });
    }
    public function down(){ Schema::dropIfExists('services'); }
};
