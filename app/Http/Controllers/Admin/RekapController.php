<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rekap;
use App\Models\Service;
use App\Models\MetodePembayaran;
use App\Models\PesananLaundry;
use App\Models\SaldoKas;
use App\Models\Fee;
use App\Models\SaldoKartu;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RekapController extends Controller
{
    public function index()
    {
        // id metode
        $idTunai = MetodePembayaran::where('nama', 'tunai')->value('id');
        $idQris  = MetodePembayaran::where('nama', 'qris')->value('id');
        $idBon   = MetodePembayaran::where('nama', 'bon')->value('id');

        // === OMSET (rekap dengan service_id) ===
        $omset = Rekap::query()
            ->whereNotNull('service_id')
            ->select([
                'service_id',
                'metode_pembayaran_id',
                DB::raw('SUM(qty)        AS qty'),
                DB::raw('SUM(subtotal)   AS subtotal'),
                DB::raw('SUM(total)      AS total'),
                DB::raw('MAX(created_at) AS max_created_at'),
            ])
            ->with(['service', 'metode'])
            ->groupBy('service_id', 'metode_pembayaran_id')
            ->orderByDesc('max_created_at')
            ->paginate(10, ['*'], 'omset');

        // === PENGELUARAN (rekap tanpa service_id) ===
        $pengeluaran = Rekap::with('metode')
            ->whereNull('service_id')
            ->latest()
            ->paginate(10, ['*'], 'pengeluaran');

        // === Ringkasan angka untuk kartu di atas ===
        // Cash = tunai + qris
        $totalCash = Rekap::whereNotNull('service_id')
            ->whereIn('metode_pembayaran_id', array_filter([$idTunai, $idQris]))
            ->sum('total');

        // Piutang = bon
        $totalPiutang = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idBon)
            ->sum('total');

        // ---------------- HITUNG FEE DARI OMZET ----------------
        $rekapOmzet = Rekap::with('service')
            ->whereNotNull('service_id')
            ->whereDate('created_at', today())
            ->get();

        $feeLipat = 0;
        $feeSetrika = 0;

        // kumpulkan total kg untuk semua layanan "lipat"
        $lipatKgTotal = 0;

        foreach ($rekapOmzet as $row) {
            $qty  = (int) ($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            // --- LIPAT ---
            // Regular (/Kg): tambahkan apa adanya
            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) {
                $lipatKgTotal += $qty;
            }

            // Express max 7Kg: konversi ke kilogram lalu akumulasi
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) {
                $lipatKgTotal += 7 * $qty;
            }

            // --- SETRIKA ---
            // Khusus: Setrika Express 3/5/7 Kg (tarif flat per kuantitas)
            if (str_contains($name, 'cuci setrika express 3kg')) {
                $feeSetrika += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 5kg')) {
                $feeSetrika += 5000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 7kg')) {
                $feeSetrika += 7000 * $qty;
                continue;
            }

            // Semua layanan ber-kata kunci "setrika" (per kg = 1.000)
            if (str_contains($name, 'setrika')) {
                $feeSetrika += $qty * 1000;
            }
        }

        // Setelah loop, hitung fee lipat berdasarkan total kg terkumpul
        $feeLipat = intdiv($lipatKgTotal, 7) * 3000;

        $totalFee = $feeLipat + $feeSetrika;

        // -------------------------------------------------------

        // List pesanan (opsional)
        $lunas = PesananLaundry::with('service', 'metode')
            ->whereIn('metode_pembayaran_id', array_filter([$idTunai, $idQris]))
            ->latest()->paginate(10, ['*'], 'lunas');

        $bon  = PesananLaundry::with('service', 'metode')
            ->where('metode_pembayaran_id', $idBon)
            ->latest()->paginate(10, ['*'], 'bon');

        // Ambil saldo_kartu dari kolom saldo_baru (prioritas hari ini, fallback terakhir)
        $saldoRow = SaldoKartu::whereDate('created_at', today())->latest('id')->first()
            ?? SaldoKartu::latest('id')->first();
        $saldoKartu = (int) ($saldoRow->saldo_baru ?? 0);

        return view('admin.rekap.index', compact(
            'omset',
            'pengeluaran',
            'totalCash',
            'totalPiutang',
            'totalFee',
            'feeLipat',
            'feeSetrika', // dikirim juga kalau mau ditampilkan rinci
            'lunas',
            'bon',
            'saldoKartu',
        ));
    }

    // input baris rekap omzet/pengeluaran sekali submit
    public function store(Request $r)
    {
        $r->validate([
            'rows'   => 'required|array|min:1',
            'rows.*.service_id' => 'nullable|exists:services,id',
            'rows.*.metode_pembayaran_id' => 'nullable|exists:metode_pembayaran,id',
            'rows.*.qty' => 'required|integer|min:1',
            'rows.*.subtotal' => 'required|integer',
            'rows.*.total' => 'required|integer',
        ]);

        DB::transaction(function () use ($r) {
            foreach ($r->rows as $row) {
                Rekap::create([
                    'service_id'            => $row['service_id'] ?? null,
                    'metode_pembayaran_id'  => $row['metode_pembayaran_id'] ?? null,
                    'qty'                   => $row['qty'],
                    'subtotal'              => $row['subtotal'],   // harga satuan dari Services
                    'total'                 => $row['total'],      // qty * harga satuan
                ]);
            }
        });

        return back()->with('ok', 'Rekap disimpan.');
    }

    public function input()
    {
        $services = Service::all();
        $metodes  = MetodePembayaran::all();

        return view('admin.rekap.input', compact('services', 'metodes'));
    }

    public function storeSaldo(Request $request)
    {
        // Validasi akan throw ValidationException sendiri (tidak perlu try-catch)
        $data = $request->validate([
            'saldo_kartu' => ['required', 'numeric', 'min:0'],
            'tap_gagal'   => ['required', 'integer', 'min:0'],
        ]);

        $payload = [
            'saldo_baru' => (int) $data['saldo_kartu'],
            'tap_gagal'  => (int) $data['tap_gagal'],
        ];

        try {
            // 1) Gunakan transaction dengan retry (mis. 3x) jika terjadi deadlock
            DB::transaction(function () use ($payload) {
                // Kunci baris "hari ini" agar tidak balapan (race condition)
                $row = SaldoKartu::whereDate('created_at', today())
                    ->lockForUpdate()
                    ->first();

                if ($row) {
                    $row->update($payload);
                } else {
                    // create() otomatis set created_at = now()
                    SaldoKartu::create($payload);
                }
            }, 3);

            return back()->with('ok', 'Saldo kartu berhasil disimpan.');
        } catch (Throwable $e) {
            // 2) Tangkap semua error agar user dapat pesan yang ramah
            Log::error('[storeSaldo] Gagal simpan saldo kartu', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Kembalikan pesan generic ke user
            return back()
                ->withInput()
                ->withErrors(['storeSaldo' => 'Terjadi kesalahan saat menyimpan saldo. Silakan coba lagi.']);
        }
    }


    public function destroy(Rekap $rekap)
    {
        $rekap->delete();
        return back()->with('ok', 'Baris rekap berhasil dihapus.');
    }

    public function destroyGroup(Request $r)
    {
        $data = $r->validate([
            'service_id'            => ['required', 'exists:services,id'],
            'metode_pembayaran_id'  => ['nullable', 'exists:metode_pembayaran,id'],
        ]);

        $deleted = Rekap::where('service_id', $data['service_id'])
            ->where('metode_pembayaran_id', $data['metode_pembayaran_id'])
            ->delete();

        return back()->with('ok', "Grup omzet dihapus ($deleted baris).");
    }

    public function storePengeluaran(Request $r)
    {
        $r->validate([
            'outs'                        => ['required', 'array', 'min:1'],
            'outs.*.keterangan'           => ['nullable', 'string', 'max:255'],
            'outs.*.subtotal'             => ['required', 'integer', 'min:0'],
            'outs.*.tanggal'              => ['nullable', 'date'],
            'outs.*.metode_pembayaran_id' => ['nullable', 'exists:metode_pembayaran,id'],
        ]);

        DB::transaction(function () use ($r) {
            foreach ($r->outs as $row) {
                Rekap::create([
                    'service_id'            => null, // pengeluaran = tanpa service
                    'metode_pembayaran_id'  => $row['metode_pembayaran_id'] ?? null,
                    'qty'                   => 1,
                    'subtotal'              => (int) $row['subtotal'],
                    'total'                 => (int) $row['subtotal'],
                    'keterangan'            => $row['keterangan'] ?? null,
                    // opsional: pakai tanggal dari form sebagai created_at
                    'created_at'            => filled($row['tanggal'] ?? null)
                        ? \Carbon\Carbon::parse($row['tanggal'])->endOfDay()
                        : now(),
                ]);
            }
        });

        return back()->with('ok', 'Pengeluaran berhasil disimpan.');
    }
}
