<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformasiLaundry extends Model {
    protected $table = 'informasi_laundry';
    protected $fillable = ['nama_service','harga_service'];
  }