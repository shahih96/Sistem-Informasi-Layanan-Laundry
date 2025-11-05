<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananLaundry;
use App\Models\Service;
use App\Models\MetodePembayaran;
use App\Models\Rekap;
use App\Models\BonMigrasiSetup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PesananLaundryController extends Controller
{
    public function index()
    {
        $pesanan  = PesananLaundry::with([
            'service',
            'metode',
            'statuses' => fn($q) => $q->latest(),
        ])->latest()->paginate(15);

        $services = Service::orderBy('nama_service')->get();
        $metodes  = MetodePembayaran::orderBy('id')->get();
        $pelangganOptions = PesananLaundry::select('nama_pel', 'no_hp_pel')
            ->groupBy('nama_pel', 'no_hp_pel')
            ->orderBy('nama_pel')
            ->limit(500)
            ->get();

        return view('admin.pesanan.index', compact('pesanan', 'services', 'metodes', 'pelangganOptions'));
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
            $hargaSekarang = (int) Service::whereKey($data['service_id'])->value('harga_service');

            $pesanan = PesananLaundry::create([
                'service_id'           => $data['service_id'],
                'nama_pel'             => $data['nama_pel'],
                'no_hp_pel'            => $data['no_hp_pel'],
                'qty'                  => (int) $data['qty'],
                'admin_id'             => Auth::id(),
                'metode_pembayaran_id' => $data['metode_pembayaran_id'],
                'harga_satuan'         => $hargaSekarang, // kunci harga saat dibuat
            ]);

            $pesanan->statuses()->create([
                'keterangan' => $data['status_awal'],
            ]);

            Rekap::firstOrCreate(
                ['pesanan_laundry_id' => $pesanan->id],
                [
                    'service_id'           => $pesanan->service_id,
                    'metode_pembayaran_id' => $pesanan->metode_pembayaran_id,
                    'qty'                  => $pesanan->qty,
                    'harga_satuan'         => $pesanan->harga_satuan,
                    'subtotal'             => $pesanan->harga_satuan,
                    'total'                => $pesanan->harga_satuan * $pesanan->qty,
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

        $pesanan->update([
            'nama_pel'             => $data['nama_pel'],
            'no_hp_pel'            => $data['no_hp_pel'],
            'service_id'           => $data['service_id'],
            'qty'                  => (int) $data['qty'],
            'metode_pembayaran_id' => $data['metode_pembayaran_id'],
        ]);

        return back()->with('ok', 'Pesanan berhasil diperbarui.');
    }

    public function destroy(PesananLaundry $pesanan)
    {
        $pesanan->update(['is_hidden' => true]);
        return back()->with('ok', 'Pesanan disembunyikan dari halaman tracking.');
    }

    /**
     * Migrasi bon lama → buat pesanan BON tanpa membuat rekap.
     * Form: nama_pelanggan, service_id, qty (sederhana).
     * - Disimpan ke kolom: nama_pel, no_hp_pel (diisi '-' agar tidak NULL), dst.
     * - Ditolak kalau migrasi sudah dikunci.
     */
    public function storeMigrasiBon(Request $r)
    {
        // stop jika sudah dikunci
        $flag = BonMigrasiSetup::latest('id')->first();
        if ($flag && $flag->locked) {
            return back()->withErrors(['migrasi' => 'Migrasi bon sudah dikunci. Form ini tidak bisa dipakai lagi.']);
        }

        $data = $r->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'service_id'     => [
                'required',
                Rule::exists('services', 'id')->where(fn($q) => $q->where('is_fee_service', 0))
            ],
            'qty'            => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $svc = Service::findOrFail($data['service_id']);
        $hargaSatuan = (int) $svc->harga_service; // versi simpel (tanpa harga_custom)

        $idBon = MetodePembayaran::whereRaw('LOWER(nama)=?', ['bon'])->value('id');
        if (!$idBon) {
            return back()->withErrors(['migrasi' => 'Metode BON belum dikonfigurasi. Tambahkan metode "bon" dulu di master Metode Pembayaran.']);
        }

        $createdAt = now();

        DB::transaction(function () use ($data, $idBon, $hargaSatuan, $createdAt) {
            $p = PesananLaundry::create([
                'service_id'           => $data['service_id'],
                'nama_pel'             => $data['nama_pelanggan'],
                'no_hp_pel'            => '-',                 // ⬅️ anti-NULL supaya lolos constraint
                'qty'                  => (int)$data['qty'],
                'admin_id'             => Auth::id(),
                'metode_pembayaran_id' => $idBon,              // selalu BON saat migrasi
                'harga_satuan'         => $hargaSatuan,        // kunci harga
                'paid_at'              => null,                // masih piutang
                'created_at'           => $createdAt,
                'updated_at'           => $createdAt,
            ]);

            // status penanda
            $p->statuses()->create([
                'keterangan' => 'BON (Migrasi)',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        });

        return back()->with('ok', 'Bon lama berhasil dimigrasikan sebagai pesanan BON. Saat lunas, ubah metodenya ke Tunai/QRIS.');
    }

    /**
     * Kunci migrasi bon -> setelah ini form hilang & submit ditolak.
     */
    public function lockMigrasiBon(Request $r)
    {
        $row = BonMigrasiSetup::latest('id')->first();
        if (!$row) {
            BonMigrasiSetup::create(['locked' => true]);
        } else if (!$row->locked) {
            $row->update(['locked' => true]);
        }
        return back()->with('ok_migrasi_bon', 'Migrasi bon dikunci. Form disembunyikan.');
    }
}
