<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $table = 'cabang';

    protected $fillable = [
        'kode',
        'nama',
        'alamat',
        'no_telepon',
    ];

    // Relasi: Cabang memiliki banyak Admin
    public function admins()
    {
        return $this->hasMany(Admin::class, 'cabang_id');
    }

    // Relasi: Cabang memiliki banyak Pesanan
    public function pesananLaundries()
    {
        return $this->hasMany(PesananLaundry::class, 'cabang_id');
    }

    // Relasi: Cabang memiliki banyak Bon
    public function bons()
    {
        return $this->hasMany(Bon::class, 'cabang_id');
    }

    // Relasi: Cabang memiliki banyak Rekap
    public function rekaps()
    {
        return $this->hasMany(Rekap::class, 'cabang_id');
    }

    // Relasi: Cabang memiliki banyak Saldo
    public function saldoBon()
    {
        return $this->hasMany(SaldoBon::class, 'cabang_id');
    }

    public function saldoKartu()
    {
        return $this->hasMany(SaldoKartu::class, 'cabang_id');
    }

    public function saldoKas()
    {
        return $this->hasMany(SaldoKas::class, 'cabang_id');
    }

    // Relasi: Cabang memiliki banyak Fee
    public function fees()
    {
        return $this->hasMany(Fee::class, 'cabang_id');
    }
}
