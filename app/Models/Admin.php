<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'cabang_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relasi ke Cabang
    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }
}
