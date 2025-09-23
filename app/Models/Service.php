<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model {
    protected $fillable = ['nama_service','harga_service'];
    public function pesanan(){ return $this->hasMany(PesananLaundry::class); }
    public function rekaps(){ return $this->hasMany(Rekap::class); }
    public function bon(){ return $this->hasMany(Bon::class); }
  }
