<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodePembayaran extends Model {
    protected $table = 'metode_pembayaran';
    protected $fillable = ['nama'];
    public function rekaps(){ return $this->hasMany(Rekap::class,'metode_pembayaran_id'); }
  }