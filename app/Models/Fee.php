<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fee extends Model {
    protected $table = 'fee';
    protected $fillable = ['fee_lipat','fee_setrika', 'sisa_lipat_kg'];
    public function rekaps(){ return $this->hasMany(Rekap::class); }
  }