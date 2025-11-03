<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpeningSetup extends Model
{
    use HasFactory;

    protected $table = 'opening_setups';

    protected $fillable = [
        'init_cash',      // saldo kas awal (Rp)
        'cutover_date',   // tanggal mulai pakai sistem
        'locked',         // true jika sudah dikunci
    ];

    protected $casts = [
        'init_cash'    => 'integer',
        'cutover_date' => 'date',
        'locked'       => 'boolean',
    ];
}
