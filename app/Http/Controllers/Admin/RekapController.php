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

use Carbon\Carbon;
use Throwable;

class RekapController extends Controller
{
    public function index(request $r)
    {
        // === TANGGAL TERPILIH ===
        $day   = $r->query('d') ? Carbon::parse($r->query('d')) : today();
        $start = $day->copy()->startOfDay();
        $end   = $day->copy()->endOfDay();
        $isToday = $day->isToday();

        // id metode
        $idTunai = MetodePembayaran::where('nama', 'tunai')->value('id');
        $idQris  = MetodePembayaran::where('nama', 'qris')->value('id');
        $idBon   = MetodePembayaran::where('nama', 'bon')->value('id');

        // === OMSET (rekap dengan service_id) ===
        $omset = Rekap::query()
            ->whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
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
            ->paginate(20, ['*'], 'omset');

        // === PENGELUARAN (rekap tanpa service_id) ===
        $pengeluaran = Rekap::with('metode')
            ->whereNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->paginate(10, ['*'], 'pengeluaran');

        // TOTAL FEE
            $rekapHariIni = Rekap::with('service')
            ->whereNotNull('service_id')
            ->whereNull('pesanan_laundry_id')
            ->whereBetween('created_at', [$start, $end])
            ->get();

            $feeLipat = 0;
            $feeSetrika = 0;
            $lipatKgHariIni = 0;
            $setrikaKgTotal  = 0;

            // kumpulkan total kg untuk semua layanan "lipat"
            $lipatKgTotal = 0;

            foreach ($rekapHariIni as $row) {
                $qty  = (int) ($row->qty ?? 0);
                if ($qty <= 0) continue;
            
                $name = strtolower($row->service->nama_service ?? '');
            
                // --- LIPAT ---
                if (str_contains($name, 'lipat') && str_contains($name, '/kg')) {
                    $lipatKgHariIni += $qty;
                    continue;
                }
                if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) {
                    $lipatKgHariIni += 7 * $qty;
                    continue;
                }
                // Bed cover diasumsikan 7 Kg per item
                if (str_contains($name, 'bed cover')) {
                    $lipatKgHariIni += 7 * $qty;
                    continue;
                }
            
                // --- SETRIKA ---
                if (str_contains($name, 'cuci setrika express 3kg')) {
                    $setrikaKgTotal += 3 * $qty;      // ⬅️ tambahkan kg
                    $feeSetrika     += 3000 * $qty;
                    continue;
                }
                if (str_contains($name, 'cuci setrika express 5kg')) {
                    $setrikaKgTotal += 5 * $qty;      // ⬅️ tambahkan kg
                    $feeSetrika     += 5000 * $qty;
                    continue;
                }
                if (str_contains($name, 'cuci setrika express 7kg')) {
                    $setrikaKgTotal += 7 * $qty;      // ⬅️ tambahkan kg
                    $feeSetrika     += 7000 * $qty;
                    continue;
                }

                // semua layanan yang ada kata "setrika" dihitung per kg = Rp 1.000
                if (str_contains($name, 'setrika')) {
                    $setrikaKgTotal += $qty;
                    $feeSetrika     += $qty * 1000;
                }

            }
            
            // ---- Carry-over lipat----
            // total KG lipat sampai akhir H (untuk sisa setelah H)
            $lipatToEnd = $this->sumKgLipatUntil($end);
            // total KG lipat sampai akhir H-1 (untuk hitung terbayar hari ini)
            $lipatToPrevEnd = $this->sumKgLipatUntil($start->copy()->subSecond()); // sebelum start

            $sisaLipatBaru     = $lipatToEnd % 7;
            $kgLipatTerbayar   = (intdiv($lipatToEnd, 7) - intdiv($lipatToPrevEnd, 7)) * 7;
            $feeLipat          = (intdiv($lipatToEnd, 7) - intdiv($lipatToPrevEnd, 7)) * 3000;

            $totalFee = $feeLipat + $feeSetrika;

        
        // ==================== Ringkasan angka untuk kartu di atas ======================================
        // === RINGKASAN CASH (AKUMULASI s.d. $end) ===
        // Masuk tunai kumulatif
        $cashMasukTunaiCum = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        // Keluar tunai kumulatif
        $cashKeluarTunaiCum = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        // Pesanan tunai yang belum pernah dicatat ke rekap (fallback), kumulatif
        $extraCashFromBonLunasTunaiCum = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->whereNull('rekap.id')
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->where('pesanan_laundry.created_at', '<=', $end)
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * IFNULL(services.harga_service,0)'));

        // === FEE KUMULATIF s.d. $end (untuk mengurangi saldo kas akumulasi) ===
        $rowsToEnd = Rekap::with('service')
            ->whereNotNull('service_id')
            ->where('created_at', '<=', $end)
            ->get();

        $kgLipatTotalCum = 0;
        $feeSetrikaCum   = 0;

        foreach ($rowsToEnd as $row) {
            $qty  = (int) ($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) { $kgLipatTotalCum += $qty; continue; }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) { $kgLipatTotalCum += 7 * $qty; continue; }
            if (str_contains($name, 'bed cover')) { $kgLipatTotalCum += 7 * $qty; continue; }

            if (str_contains($name, 'cuci setrika express 3kg')) { $feeSetrikaCum += 3000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 5kg')) { $feeSetrikaCum += 5000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 7kg')) { $feeSetrikaCum += 7000 * $qty; continue; }
            if (str_contains($name, 'setrika')) { $feeSetrikaCum += $qty * 1000; }
        }

        $feeLipatCum = intdiv($kgLipatTotalCum, 7) * 3000;
        $totalFeeCum = $feeLipatCum + $feeSetrikaCum;

        // === SALDO KAS (AKUMULASI as-of $end) ===
        $totalCash = $cashMasukTunaiCum + $extraCashFromBonLunasTunaiCum - $cashKeluarTunaiCum - $totalFeeCum;

        // Piutang = bon
        $totalPiutang = PesananLaundry::with('service')
        ->where('metode_pembayaran_id', $idBon)
        ->get()
        ->sum(function($p){
            $qty   = max(1, (int)($p->qty ?? 1));
            $harga = (int)($p->service->harga_service ?? 0);
            return $qty * $harga;
        });

        // -------------------------------------------------------

        // List pesanan
        $lunas = PesananLaundry::with('service', 'metode')
            ->whereIn('metode_pembayaran_id', array_filter([$idTunai, $idQris]))
            ->whereBetween('created_at', [$start, $end])
            ->latest()->paginate(10, ['*'], 'lunas');

        $bon = PesananLaundry::with(['service','metode'])
        ->where('created_at', '<=', $end)
        ->where(function ($q) use ($idBon, $idTunai, $idQris, $start, $end) {

            // 1) AS-OF $end MASIH BON
            //    - sekarang masih bon, atau
            //    - sekarang sudah lunas, tapi pelunasannya terjadi SETELAH $end
            $q->where(function ($qq) use ($idBon, $end) {
                $qq->where('metode_pembayaran_id', $idBon)                   // masih bon saat ini
                ->orWhere(function ($qqq) use ($idBon, $end) {            // sudah lunas sekarang,
                    $qqq->where('metode_pembayaran_id', '<>', $idBon)     // tapi HARI PELUNASAN > $end
                        ->where('updated_at', '>', $end);
                });
            })

            // 2) DIBAYAR PADA TANGGAL YANG DILIAT ($start..$end)
            //    Tampilkan juga baris ini (sebagai "muncul terakhir" di hari pelunasan)
            ->orWhere(function ($qq) use ($idTunai, $idQris, $start, $end) {
                $qq->whereBetween('updated_at', [$start, $end])
                ->whereIn('metode_pembayaran_id', [$idTunai, $idQris]);
            });
        })
        ->latest('created_at')
        ->paginate(10, ['*'], 'bon'); 

        // === KARTU TAP (menggunakan catatan terakhir sebelum hari terpilih) ===
        $CAP     = 5_000_000;  // saldo maksimum setelah isi ulang
        $PER_TAP = 10_000;     // pengurangan per 1 tap

        // Ambil row saldo_kartu untuk H (antara $start..$end)
        $saldoRowDay = SaldoKartu::whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->first();

        // Ambil catatan saldo terakhir SEBELUM hari terpilih (bisa H-1, H-2, dst)
        $saldoRowPrev = SaldoKartu::where('created_at', '<', $start)
            ->latest('id')
            ->first();

        // Sisa Saldo Kartu utk tanggal terpilih (null kalau memang belum diinput)
        $saldoKartu = $saldoRowDay ? (int) $saldoRowDay->saldo_baru : null;

        // Tap gagal: ambil yang diinput karyawan pada hari terpilih saja
        $tapGagalHariIni = (int) ($saldoRowDay?->tap_gagal ?? 0);

        // Hitung total tap berdasarkan selisih saldo dengan logika wrap ke CAP
        $totalTapHariIni = 0;
        if ($saldoRowDay && $saldoRowPrev) {
            $saldoToday = max(0, min($CAP, (int)$saldoRowDay->saldo_baru));
            $saldoPrev  = max(0, min($CAP, (int)$saldoRowPrev->saldo_baru));

            if ($saldoToday <= $saldoPrev) {
                // Tidak ada isi ulang ke CAP di antaranya
                $totalTapHariIni = intdiv($saldoPrev - $saldoToday, $PER_TAP);
            } else {
                // Ada isi ulang ke CAP di antaranya (wrap)
                // contoh: prev 250.000 → habis (25 tap), isi ke 5.000.000, lalu turun ke 4.900.000 (10 tap) => 35
                $totalTapHariIni = intdiv($saldoPrev, $PER_TAP) + intdiv($CAP - $saldoToday, $PER_TAP);
            }
        } else {
            // kalau salah satu tidak ada (belum input hari ini, atau belum ada histori sama sekali) biarkan 0
            $totalTapHariIni = 0;
        }

        // Total Omzet bersih dan kotor Hari Ini
        $totalOmzetKotorHariIni = Rekap::whereNotNull('service_id')
        ->whereBetween('created_at', [$start, $end])
        ->sum('total');
        // sudah dikurang dengan fee
        $totalOmzetBersihHariIni = max(0, $totalOmzetKotorHariIni - $totalFee);


        // KEEP QUERY PARAM 'd' di pagination
        $omset->appends(['d' => $day->toDateString()]);
        $pengeluaran->appends(['d' => $day->toDateString()]);
        $lunas->appends(['d' => $day->toDateString()]);
        $bon->appends(['d' => $day->toDateString()]);

        return view('admin.rekap.index', compact(
            'omset',
            'pengeluaran',
            'totalCash',
            'totalPiutang',
            'totalFee',
            'feeLipat',
            'feeSetrika',
            'sisaLipatBaru',
            'kgLipatTerbayar',
            'setrikaKgTotal',
            'lunas',
            'bon',
            'saldoKartu',
            'totalOmzetBersihHariIni',
            'totalOmzetKotorHariIni',
            'totalTapHariIni',
            'tapGagalHariIni',
            'day','isToday',
        ));
    }

    // input baris rekap omzet/pengeluaran sekali submit
    public function store(Request $r)
    {
        try {
            $rawRows = $r->input('rows', []);

            // 1) Normalisasi & filter baris valid
            $rows = [];
            foreach ($rawRows as $row) {
                $serviceId = $row['service_id'] ?? null;
                $metodeId  = $row['metode_pembayaran_id'] ?? null;
                $qty       = (int)($row['qty'] ?? 0);
                $subtotal  = (int)($row['subtotal'] ?? 0);
                $total     = (int)($row['total'] ?? 0);

                // Anggap "kosong" jika belum pilih layanan ATAU qty/total/subtotal 0
                if (!$serviceId || $qty <= 0 || $subtotal <= 0 || $total <= 0) {
                    continue;
                }

                $rows[] = compact('serviceId','metodeId','qty','subtotal','total');
            }

            // 2) Tidak ada baris valid? batal
            if (count($rows) === 0) {
                return back()
                    ->withInput()
                    ->withErrors(['rows' => 'Tidak ada baris omzet yang valid. Pilih layanan dan isi jumlah/harga.'], 'omzet');
            }

            // 3) Validasi ringkas (pastikan id ada di DB)
            $r->validate([
                'rows.*.service_id'           => ['nullable','exists:services,id'],
                'rows.*.metode_pembayaran_id' => ['nullable','exists:metode_pembayaran,id'],
            ]);

            // 4) Simpan dalam transaksi
            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    Rekap::create([
                        'service_id'            => $row['serviceId'],
                        'metode_pembayaran_id'  => $row['metodeId'],
                        'qty'                   => $row['qty'],
                        'subtotal'              => $row['subtotal'],
                        'total'                 => $row['total'],
                    ]);
                }
            });

            return back()->with('ok', 'Rekap omzet berhasil disimpan.');
        } catch (\Throwable $e) {
            Log::error('[Rekap.store] gagal', ['msg'=>$e->getMessage()]);
            return back()
                ->withInput()
                ->withErrors(['store' => 'Terjadi kesalahan saat menyimpan rekap omzet. Coba lagi.'], 'omzet');
        }
    }

    public function input()
    {
        $services = Service::all();
        $metodes  = MetodePembayaran::all();

        return view('admin.rekap.input', compact('services', 'metodes'));
    }

    public function storeSaldo(Request $request)
    {
        $data = $request->validate([
            'saldo_kartu' => ['required', 'integer', 'min:0', 'max:5000000'],
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
                ->withErrors(['storeSaldo' => 'Terjadi kesalahan saat menyimpan saldo. Silakan coba lagi.'], 'saldo');
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
        try {
            $raw = $r->input('outs', []);
    
            // 1) Normalisasi & filter: baris valid = ada nominal > 0 (keterangan/metode opsional)
            $rows = [];
            foreach ($raw as $row) {
                $ket     = trim((string)($row['keterangan'] ?? ''));
                $subtotal= (int)($row['subtotal'] ?? 0);
                $tanggal = $row['tanggal'] ?? null;
                $metode  = $row['metode_pembayaran_id'] ?? null;
    
                if ($subtotal <= 0) continue; // kosong → skip
    
                $rows[] = [
                    'keterangan' => $ket,
                    'subtotal'   => $subtotal,
                    'tanggal'    => $tanggal,
                    'metode'     => $metode,
                ];
            }
    
            if (count($rows) === 0) {
                return back()
                    ->withInput()
                    ->withErrors(['outs' => 'Tidak ada baris pengeluaran yang valid. Isi nominal (> 0).'], 'pengeluaran');
            }
    
            // 2) Validasi id metode (jika diisi)
            $r->validate([
                'outs.*.metode_pembayaran_id' => ['nullable','exists:metode_pembayaran,id'],
            ]);
    
            // 3) Simpan transaksi
            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    Rekap::create([
                        'service_id'            => null,
                        'metode_pembayaran_id'  => $row['metode'] ?: null,
                        'qty'                   => 1,
                        'subtotal'              => $row['subtotal'],
                        'total'                 => $row['subtotal'],
                        'keterangan'            => $row['keterangan'] ?: null,
                        'created_at'            => filled($row['tanggal'])
                            ? \Carbon\Carbon::parse($row['tanggal'])->endOfDay()
                            : now(),
                    ]);
                }
            });
    
            return back()->with('ok', 'Pengeluaran berhasil disimpan.');
        } catch (\Throwable $e) {
            Log::error('[Rekap.storePengeluaran] gagal', ['msg'=>$e->getMessage()]);
            return back()
                ->withInput()
                ->withErrors(['storePengeluaran' => 'Terjadi kesalahan saat menyimpan pengeluaran. Coba lagi.'], 'pengeluaran');
        }
    }    

    public function updateBonMetode(Request $r, PesananLaundry $pesanan)
    {
        $r->validate([
            'metode' => ['required', 'in:bon,tunai,qris'],
        ]);
    
        // map nama -> id
        $map = MetodePembayaran::pluck('id', 'nama');
        $newId = $map[$r->metode] ?? null;
    
        if (!$newId) {
            return back()->withErrors(['metode' => 'Metode tidak valid.']);
        }
    
        // ✅ Cukup update metode pembayaran di pesanan
        $pesanan->update(['metode_pembayaran_id' => $newId]);
    
        // ❌ Jangan ubah tabel rekap sama sekali di sini
        return back()->with('ok', 'Metode pembayaran bon diperbarui.');
    }

    private function sumKgLipatUntil($until): int
    {
        $rows = Rekap::with('service')
            ->whereNotNull('service_id')
            ->where('created_at', '<=', $until)
            ->get();

        $total = 0;
        foreach ($rows as $row) {
            $qty  = (int) ($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) {
                $total += $qty;
                continue;
            }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) {
                $total += 7 * $qty;
                continue;
            }
            if (str_contains($name, 'bed cover')) {
                $total += 7 * $qty;
                continue;
            }
        }
        return $total;
    }
}
