<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Bon extends Model {
    protected $table = 'bon';
    protected $fillable = ['service_id','nama_pel','status_bon','cabang_id'];
    
    public function service(){ return $this->belongsTo(Service::class); }
    public function saldoBon(){ return $this->hasOne(SaldoBon::class,'bon_id'); }
    public function rekap(){ return $this->hasOne(Rekap::class); }
    public function cabang(){ return $this->belongsTo(Cabang::class, 'cabang_id'); }

    protected static function booted()
    {
        // Global Scope: Otomatis filter by cabang_id
        static::addGlobalScope('cabang', function ($query) {
            if (Auth::check() && Auth::user()->cabang_id) {
                $query->where('bon.cabang_id', Auth::user()->cabang_id);
            }
        });

        // Auto-set cabang_id saat create
        static::creating(function ($model) {
            if (Auth::check() && !$model->cabang_id) {
                $model->cabang_id = Auth::user()->cabang_id;
            }
        });
    }
  }