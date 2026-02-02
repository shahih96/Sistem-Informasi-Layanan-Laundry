<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
  protected $fillable = ['nama_service', 'harga_service', 'is_fee_service', 'expected_tap'];
  protected $casts = [
    'is_fee_service' => 'boolean',
  ];
  public function pesanan()
  {
    return $this->hasMany(PesananLaundry::class);
  }
  public function rekaps()
  {
    return $this->hasMany(Rekap::class);
  }
  public function bon()
  {
    return $this->hasMany(Bon::class);
  }
}