<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusPesanan extends Model
{
    protected $table = 'status_pesanan'; // sesuaikan
    protected $fillable = ['pesanan_id','status_id','keterangan'];

    public function pesanan(){ return $this->belongsTo(PesananLaundry::class,'pesanan_id'); }
    public function status(){ return $this->belongsTo(Status::class,'status_id'); } // master “statuses”
}
