<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoBon extends Model {
    protected $table = 'saldo_bon';
    protected $fillable = ['bon_id','saldo_kartu_id','total_bon'];
    public function bon(){ return $this->belongsTo(Bon::class); }
    public function saldoKartu(){ return $this->belongsTo(SaldoKartu::class); }
  }