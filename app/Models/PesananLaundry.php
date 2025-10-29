<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PesananLaundry extends Model
{
    use SoftDeletes;

    protected $table = 'pesanan_laundry';
    public $timestamps = true;

    protected $fillable = [
        'service_id','nama_pel','no_hp_pel','admin_id','qty','metode_pembayaran_id','is_hidden','harga_satuan'
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
    ];

    public function scopeVisible($q)
    {
        return $q->where('is_hidden', false);
    }

    public function admin(){ return $this->belongsTo(User::class,'admin_id'); }
    public function service(){ return $this->belongsTo(Service::class); }
    public function statuses(){ return $this->hasMany(StatusPesanan::class,'pesanan_id'); }
    public function metode(){ return $this->belongsTo(MetodePembayaran::class,'metode_pembayaran_id'); }
    public function rekap(){ return $this->hasOne(Rekap::class,'pesanan_laundry_id'); }
    public function latestStatusLog(){return $this->hasOne(StatusPesanan::class, 'pesanan_id')->latestOfMany(); }

    protected static function booted()
    {
        static::updating(function (self $m) {
            if ($m->isDirty('metode_pembayaran_id')) {
                $old = $m->getOriginal('metode_pembayaran_id');
                $new = $m->metode_pembayaran_id;

                $idBon   = MetodePembayaran::where('nama','bon')->value('id');
                $idTunai = MetodePembayaran::where('nama','tunai')->value('id');
                $idQris  = MetodePembayaran::where('nama','qris')->value('id');

                // Bon -> Tunai/Qris => set paid_at
                if ($old === $idBon && in_array($new, [$idTunai, $idQris], true)) {
                    if (empty($m->paid_at)) {
                        $m->paid_at = now();
                    }
                }
                // Tunai/Qris -> Bon => unset paid_at
                if (in_array($old, [$idTunai, $idQris], true) && $new === $idBon) {
                    $m->paid_at = null;
                }
            }
        });
    }
}
