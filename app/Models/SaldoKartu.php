<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

// app/Models/SaldoKartu.php
class SaldoKartu extends Model {
    protected $table = 'saldo_kartu';
    protected $fillable = [
        'saldo_awal',
        'saldo_baru',
        'tap_hari_ini',
        'tap_gagal',
        'manual_total_tap',
        'cabang_id',
        'created_at',  // Untuk support H-1 revision
        'updated_at',  // Untuk support H-1 revision
    ];
    
    public function saldoBon(){ return $this->hasMany(SaldoBon::class); }
    public function cabang(){ return $this->belongsTo(Cabang::class, 'cabang_id'); }

    protected static function booted()
    {
        // Global Scope: Otomatis filter by cabang_id
        static::addGlobalScope('cabang', function ($query) {
            if (Auth::check() && Auth::user()->cabang_id) {
                $query->where('saldo_kartu.cabang_id', Auth::user()->cabang_id);
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