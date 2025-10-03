<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Pembayaran;

class PesananLaundry extends Model
{
    use SoftDeletes;
    protected $table = 'pesanan_laundry';
    public $timestamps = true;
    protected $fillable = ['service_id','nama_pel','no_hp_pel','admin_id','qty','metode_pembayaran_id'];

    public function admin(){ return $this->belongsTo(User::class,'admin_id'); }
    public function service(){ return $this->belongsTo(Service::class); }
    public function statuses(){ return $this->hasMany(StatusPesanan::class,'pesanan_id'); }
    public function metode(){ return $this->belongsTo(MetodePembayaran::class,'metode_pembayaran_id'); }
    public function rekap(){ return $this->hasOne(Rekap::class,'pesanan_laundry_id'); }

    // ➜ status terbaru (log terakhir)
    public function latestStatusLog()
    {
        return $this->hasOne(StatusPesanan::class, 'pesanan_id')->latestOfMany(); 
    }
}
