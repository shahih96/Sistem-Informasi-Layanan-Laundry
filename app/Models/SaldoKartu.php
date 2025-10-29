<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/SaldoKartu.php
class SaldoKartu extends Model {
    protected $table = 'saldo_kartu';
    protected $fillable = ['saldo_awal','saldo_baru','tap_hari_ini','tap_gagal','manual_total_tap'];
    public function saldoBon(){ return $this->hasMany(SaldoBon::class); }
  }