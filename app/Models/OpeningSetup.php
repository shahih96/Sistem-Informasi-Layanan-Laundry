<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class OpeningSetup extends Model
{
    use HasFactory;

    protected $table = 'opening_setups';

    protected $fillable = [
        'init_cash',      // saldo kas awal (Rp)
        'cutover_date',   // tanggal mulai pakai sistem
        'locked',         // true jika sudah dikunci
        'cabang_id',      // cabang
    ];

    protected $casts = [
        'init_cash'    => 'integer',
        'cutover_date' => 'date',
        'locked'       => 'boolean',
    ];

    // Relasi ke Cabang
    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    protected static function booted()
    {
        // Global Scope: Otomatis filter by cabang_id
        static::addGlobalScope('cabang', function ($query) {
            if (Auth::check() && Auth::user()->cabang_id) {
                $query->where('opening_setups.cabang_id', Auth::user()->cabang_id);
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
