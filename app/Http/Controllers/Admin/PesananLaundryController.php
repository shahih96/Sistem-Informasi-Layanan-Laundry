<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananLaundry;
use App\Models\Service;
use App\Models\MetodePembayaran;
use App\Models\Rekap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PesananLaundryController extends Controller
{
    public function index()
    {
        $pesanan  = PesananLaundry::with([
                        'service',
                        'metode',                                 
                        'statuses' => fn($q) => $q->latest(),
                    ])->latest()->paginate(10);

        $services = Service::orderBy('nama_service')->get();
        $metodes  = MetodePembayaran::orderBy('id')->get();
        $pelangganOptions = PesananLaundry::select('nama_pel','no_hp_pel')
            ->groupBy('nama_pel','no_hp_pel')
            ->orderBy('nama_pel')
            ->limit(500) 
            ->get();

        return view('admin.pesanan.index', compact('pesanan','services','metodes','pelangganOptions'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nama_pel'             => 'required|string|max:100',
            'no_hp_pel'            => 'required|string|max:20',
            'service_id'           => 'required|exists:services,id',
            'qty'                  => 'required|integer|min:1',
            'metode_pembayaran_id' => 'required|exists:metode_pembayaran,id',
            'status_awal'          => 'required|string|max:50',
        ]);

        DB::transaction(function () use ($data) {

            // 1) Simpan pesanan
            $pesanan = PesananLaundry::create([
                'service_id'           => $data['service_id'],
                'nama_pel'             => $data['nama_pel'],
                'no_hp_pel'            => $data['no_hp_pel'],
                'qty'                  => (int) $data['qty'],
                'admin_id'             => Auth::id(),
                'metode_pembayaran_id' => $data['metode_pembayaran_id'],
            ]);

            // 2) Simpan status awal
            $pesanan->statuses()->create([
                'keterangan' => $data['status_awal'],
            ]);

            // 3) Buat rekap otomatis dari pesanan
            $harga = (int) Service::whereKey($pesanan->service_id)->value('harga_service');

            Rekap::firstOrCreate(
                ['pesanan_laundry_id' => $pesanan->id],
                [
                    'service_id'           => $pesanan->service_id,
                    'metode_pembayaran_id' => $pesanan->metode_pembayaran_id, // bisa bon/tunai/qris
                    'qty'                  => $pesanan->qty,
                    'subtotal'             => $harga,
                    'total'                => $harga * $pesanan->qty,
                    'keterangan'           => 'Omset dari pesanan',
                ]
            );
        });

        return back()->with('ok', 'Pesanan & rekap berhasil dibuat.');
    }

    public function update(Request $r, PesananLaundry $pesanan)
    {
        $data = $r->validate([
            'nama_pel'             => 'required|string|max:100',
            'no_hp_pel'            => 'required|string|max:20',
            'service_id'           => 'required|exists:services,id',
            'qty'                  => 'required|integer|min:1',
            'metode_pembayaran_id' => 'required|exists:metode_pembayaran,id',
        ]);

        $pesanan->update($data);

        // Catatan: rekap tidak diubah di sini agar tidak dobel.
        // Jika kamu sudah menambahkan kolom 'pesanan_laundry_id' di tabel rekap,
        // kamu bisa cari & update baris rekap terkait di sini.

        return back()->with('ok', 'Pesanan berhasil diperbarui.');
    }

    public function destroy(PesananLaundry $pesanan)
    {
        $pesanan->update(['is_hidden' => true]);
        return back()->with('ok', 'Pesanan disembunyikan dari halaman tracking.');
    }    
}