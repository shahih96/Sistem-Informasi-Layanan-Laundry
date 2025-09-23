<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bon extends Model {
    protected $table = 'bon';
    protected $fillable = ['service_id','nama_pel','status_bon'];
    public function service(){ return $this->belongsTo(Service::class); }
    public function saldoBon(){ return $this->hasOne(SaldoBon::class,'bon_id'); }
    public function rekap(){ return $this->hasOne(Rekap::class); }
  }