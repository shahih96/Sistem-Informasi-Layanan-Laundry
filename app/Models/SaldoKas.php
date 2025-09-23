<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoKas extends Model {
    protected $table = 'saldo_kas';
    protected $fillable = ['saldo_kas'];
    public function rekaps(){ return $this->hasMany(Rekap::class); }
  }