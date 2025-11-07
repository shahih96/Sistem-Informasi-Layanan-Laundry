<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rekap extends Model {
    protected $table = 'rekap';
    protected $fillable = [
        'pesanan_laundry_id',
        'service_id',
        'metode_pembayaran_id',
        'bon_id',
        'saldo_kas_id',
        'fee_id',
        'qty',
        'subtotal',
        'total',
        'keterangan',
        'harga_satuan',
        'created_at',  // Tambahkan untuk support H-1 revision
        'updated_at',  // Tambahkan untuk support H-1 revision
    ];

    public function service(){ return $this->belongsTo(Service::class); }
    public function metode(){ return $this->belongsTo(MetodePembayaran::class,'metode_pembayaran_id'); }
    public function bon(){ return $this->belongsTo(Bon::class); }
    public function saldoKas(){ return $this->belongsTo(SaldoKas::class,'saldo_kas_id'); }
    public function fee(){ return $this->belongsTo(Fee::class); }
    public function pesanan(){ return $this->belongsTo(PesananLaundry::class,'pesanan_laundry_id'); }
  }