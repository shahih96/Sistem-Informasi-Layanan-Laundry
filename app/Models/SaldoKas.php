<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SaldoKas extends Model {
    protected $table = 'saldo_kas';
    protected $fillable = ['saldo_kas','cabang_id'];
    
    public function rekaps(){ return $this->hasMany(Rekap::class); }
    public function cabang(){ return $this->belongsTo(Cabang::class, 'cabang_id'); }

    protected static function booted()
    {
        // Global Scope: Otomatis filter by cabang_id
        static::addGlobalScope('cabang', function ($query) {
            if (Auth::check() && Auth::user()->cabang_id) {
                $query->where('saldo_kas.cabang_id', Auth::user()->cabang_id);
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