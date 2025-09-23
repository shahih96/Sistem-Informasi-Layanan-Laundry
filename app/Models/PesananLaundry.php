<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pembayaran;

class PesananLaundry extends Model
{
    protected $table = 'pesanan_laundry';
    protected $fillable = ['service_id','nama_pel','no_hp_pel','admin_id'];

    public function admin(){ return $this->belongsTo(User::class,'admin_id'); }
    public function service(){ return $this->belongsTo(Service::class); }
    public function statuses(){ return $this->hasMany(StatusPesanan::class,'pesanan_id'); }

    // ➜ status terbaru (log terakhir)
    public function latestStatusLog()
    {
        // pakai created_at atau id sebagai penentu “terbaru”
        return $this->hasOne(StatusPesanan::class, 'pesanan_id')->latestOfMany(); 
        // kalau kolom timestamp bukan created_at, pakai: ->latestOfMany('waktu_update');
    }
}
