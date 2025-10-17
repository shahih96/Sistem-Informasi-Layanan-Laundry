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
        $prevEnd = $start->copy()->subSecond();
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
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->where('pesanan_laundry.created_at', '<=', $end)
            ->where('pesanan_laundry.updated_at', '<=', $end)
            ->where(function($q) use ($idTunai) {
                $q->whereNull('rekap.id')
                  ->orWhere('rekap.metode_pembayaran_id', '<>', $idTunai);
            })
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
        $totalPiutang = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->where('pesanan_laundry.created_at','<=',$end)
            ->where(function ($q) use ($idBon, $end) {
                // MASIH BON per $end
                $q->where(function ($qq) use ($idBon, $end) {
                    $qq->where('pesanan_laundry.metode_pembayaran_id', $idBon)            // statusnya bon
                       ->orWhere(function ($qqq) use ($idBon, $end) {                     // sekarang sudah lunas
                           $qqq->where('pesanan_laundry.metode_pembayaran_id','<>',$idBon) // tapi
                               ->where('pesanan_laundry.updated_at','>', $end);            // pelunasannya SETELAH $end
                       });
                });
            })
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * IFNULL(services.harga_service,0)'));

        // -------------------------------------------------------

        // List pesanan
        $lunas = PesananLaundry::with('service', 'metode')
            ->whereIn('metode_pembayaran_id', array_filter([$idTunai, $idQris]))
            ->whereBetween('created_at', [$start, $end])
            ->latest()->paginate(10, ['*'], 'lunas');

        $bon = PesananLaundry::with(['service','metode'])
            ->where('created_at', '<=', $end)
            ->where(function ($q) use ($idBon, $idTunai, $idQris, $start, $end) {

                // 1) As-of $end MASIH BON
                $q->where(function ($qq) use ($idBon, $end) {
                    $qq->where('metode_pembayaran_id', $idBon)                 // masih bon saat ini
                        ->orWhere(function ($qqq) use ($idBon, $end) {          // sekarang sudah lunas,
                            $qqq->where('metode_pembayaran_id', '<>', $idBon)   // tapi pelunasannya SETELAH $end
                                ->where('updated_at', '>', $end);
                        });
                })

                // 2) DIBAYAR PADA TANGGAL YANG DILIHAT ($start..$end)
                //    TAPI pastikan pesanan SUDAH ADA sebelum hari ini
                //    (kalau dibuat dan langsung tunai/qris hari ini, JANGAN tampil)
                ->orWhere(function ($qq) use ($idTunai, $idQris, $start, $end) {
                    $qq->where('created_at', '<', $start)
                        ->whereBetween('updated_at', [$start, $end])
                        ->whereIn('metode_pembayaran_id', [$idTunai, $idQris]);
                });
            })
            ->latest('created_at')
            ->paginate(20, ['*'], 'bon');

        // === KARTU TAP (menggunakan catatan terakhir sebelum hari terpilih) ===
        $CAP     = 5_000_000;  // saldo maksimum setelah isi ulang
        $PER_TAP = 10_000;     // pengurangan per 1 tap

        $saldoRowDay = SaldoKartu::whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->first();

        $saldoRowPrev = SaldoKartu::where('created_at', '<', $start)
            ->latest('id')
            ->first();

        $saldoKartu = $saldoRowDay ? (int) $saldoRowDay->saldo_baru : null;
        $tapGagalHariIni = (int) ($saldoRowDay?->tap_gagal ?? 0);

        // ====== HITUNG TOTAL TAP HARI INI ======
        $totalTapHariIni = 0;

        if ($saldoRowDay && $saldoRowPrev) {
            // Ada data hari ini & ada data sebelumnya → pakai selisih saldo (auto)
            $saldoToday = max(0, min($CAP, (int)$saldoRowDay->saldo_baru));
            $saldoPrev  = max(0, min($CAP, (int)$saldoRowPrev->saldo_baru));

            if ($saldoToday <= $saldoPrev) {
                // Tidak ada isi ulang (no wrap)
                $totalTapHariIni = intdiv($saldoPrev - $saldoToday, $PER_TAP);
            } else {
                // Ada isi ulang ke CAP (wrap)
                $totalTapHariIni = intdiv($saldoPrev, $PER_TAP) + intdiv($CAP - $saldoToday, $PER_TAP);
            }

        } elseif ($saldoRowDay && !$saldoRowPrev) {
            // Ini hari pertama/awal banget → pakai MODE MANUAL kalau ada
            $manualTap   = $saldoRowDay->total_tap_manual;   // nullable
            $manualAwal  = $saldoRowDay->saldo_awal_manual;  // nullable

            if ($manualTap !== null) {
                // 1) Prioritas: kalau user isi TOTAL TAP manual → pakai langsung
                $totalTapHariIni = max(0, (int)$manualTap);

            } elseif ($manualAwal !== null) {
                // 2) Atau, kalau user isi SALDO AWAL manual → hitung selisih ke saldo_baru
                $saldoAwal = max(0, min($CAP, (int)$manualAwal));
                $saldoAkhir = max(0, min($CAP, (int)$saldoRowDay->saldo_baru));

                if ($saldoAwal >= $saldoAkhir) {
                    // Tidak ada wrap dalam 1 hari pertama
                    $totalTapHariIni = intdiv($saldoAwal - $saldoAkhir, $PER_TAP);
                } else {
                    // Kalau mau dukung wrap di hari pertama: asumsikan sempat isi ulang ke CAP
                    $totalTapHariIni = intdiv($saldoAwal, $PER_TAP) + intdiv($CAP - $saldoAkhir, $PER_TAP);
                }
            } else {
                // 3) Tidak ada data manual sama sekali → biarkan 0 (atau buat default lain kalau mau)
                $totalTapHariIni = 0;
            }
        } else {
            // Tidak ada input hari ini → tetap 0
            $totalTapHariIni = 0;
        }

        // Total Omzet bersih dan kotor Hari Ini
        $totalOmzetKotorHariIni = Rekap::whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');
        // sudah dikurang dengan fee
        $totalOmzetBersihHariIni = max(0, $totalOmzetKotorHariIni - $totalFee);

        $totalTunaiHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $totalQrisHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idQris)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        // === BREAKDOWN TAMBAHAN UNTUK KETERANGAN DI KARTU ===

        // ---- Saldo Cash KEMARIN (as-of $prevEnd) ----
        $cashMasukTunaiCumPrev = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $prevEnd)
            ->sum('total');

        $cashKeluarTunaiCumPrev = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $prevEnd)
            ->sum('total');

        $extraCashFromBonLunasTunaiCumPrev = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->where('pesanan_laundry.created_at', '<=', $prevEnd)
            ->where('pesanan_laundry.updated_at', '<=', $prevEnd)
            ->where(function($q) use ($idTunai) {
                $q->whereNull('rekap.id')
                  ->orWhere('rekap.metode_pembayaran_id', '<>', $idTunai);
            })
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * IFNULL(services.harga_service,0)'));

        // FEE s.d. H-1
        $rowsToPrevEnd = Rekap::with('service')
            ->whereNotNull('service_id')
            ->where('created_at', '<=', $prevEnd)
            ->get();

        $kgLipatTotalCumPrev = 0;
        $feeSetrikaCumPrev   = 0;
        foreach ($rowsToPrevEnd as $row) {
            $qty  = (int) ($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) { $kgLipatTotalCumPrev += $qty; continue; }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) { $kgLipatTotalCumPrev += 7 * $qty; continue; }
            if (str_contains($name, 'bed cover')) { $kgLipatTotalCumPrev += 7 * $qty; continue; }

            if (str_contains($name, 'cuci setrika express 3kg')) { $feeSetrikaCumPrev += 3000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 5kg')) { $feeSetrikaCumPrev += 5000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 7kg')) { $feeSetrikaCumPrev += 7000 * $qty; continue; }
            if (str_contains($name, 'setrika')) { $feeSetrikaCumPrev += $qty * 1000; }
        }
        $feeLipatCumPrev = intdiv($kgLipatTotalCumPrev, 7) * 3000;
        $totalFeeCumPrev = $feeLipatCumPrev + $feeSetrikaCumPrev;

        // SALDO CASH KEMARIN
        $saldoCashKemarin = $cashMasukTunaiCumPrev + $extraCashFromBonLunasTunaiCumPrev - $cashKeluarTunaiCumPrev - $totalFeeCumPrev;

        // ---- Mutasi CASH HARI INI ----
        $penjualanTunaiHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $pelunasanBonTunaiHariIni = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->where('pesanan_laundry.created_at','<',$start)
            ->whereBetween('pesanan_laundry.updated_at', [$start, $end])
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * IFNULL(services.harga_service,0)'));

        $pengeluaranTunaiHariIni = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        // ---- Breakdown BON: kemarin + masuk – dilunasi ----
        $bonKemarin = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->where('pesanan_laundry.created_at', '<=', $prevEnd)
            ->where(function ($q) use ($prevEnd, $idBon, $idTunai, $idQris) {
                $q->where('pesanan_laundry.metode_pembayaran_id', $idBon)
                  ->orWhere(function ($qq) use ($prevEnd, $idTunai, $idQris) {
                      $qq->whereIn('pesanan_laundry.metode_pembayaran_id', [$idTunai, $idQris])
                         ->where('pesanan_laundry.updated_at', '>', $prevEnd);
                  });
            })
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * IFNULL(services.harga_service,0)'));

        $bonMasukHariIni = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->whereBetween('pesanan_laundry.created_at', [$start, $end])
            ->where('pesanan_laundry.metode_pembayaran_id', $idBon)
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * IFNULL(services.harga_service,0)'));

        $bonDilunasiHariIni = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->where('pesanan_laundry.created_at','<',$start)
            ->whereBetween('pesanan_laundry.updated_at', [$start, $end])
            ->whereIn('pesanan_laundry.metode_pembayaran_id', [$idTunai, $idQris])
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * IFNULL(services.harga_service,0)'));

        // Bon yang tercatat di rekap HARI INI (untuk keterangan Omzet)
        $totalBonHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idBon)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

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
            'totalTunaiHariIni',
            'totalQrisHariIni',
            'totalTapHariIni',
            'tapGagalHariIni',
            'day','isToday',
            'saldoCashKemarin',
            'penjualanTunaiHariIni',
            'pelunasanBonTunaiHariIni',
            'pengeluaranTunaiHariIni',
            'bonKemarin',
            'bonMasukHariIni',
            'bonDilunasiHariIni',
            'totalBonHariIni',
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

        // === Flag "hari pertama" untuk saldo kartu ===
        $todayStart = today()->startOfDay();

        // sudah pernah ada catatan sebelum hari ini?
        $hasPrev = SaldoKartu::where('created_at', '<', $todayStart)->exists();

        // kalau belum pernah ada histori sama sekali -> butuh field manual
        $needsManualTap = !$hasPrev;

        return view('admin.rekap.input', compact('services', 'metodes', 'needsManualTap'));
    }

    public function storeSaldo(Request $request)
    {
        // Deteksi “hari pertama” (belum ada catatan sebelum hari ini)
        $isFirstDay = !SaldoKartu::where('created_at', '<', today()->startOfDay())->exists();

        // ===== Validasi dinamis =====
        $rules = [
            'tap_gagal' => ['required','integer','min:0'],
        ];

        if ($isFirstDay) {
            // Hari pertama → boleh pakai input manual
            $rules['saldo_kartu'] = ['nullable','integer','min:0','max:5000000'];
            $rules['total_tap']   = ['nullable','integer','min:0'];
            $rules['saldo_awal']  = ['nullable','integer','min:0','max:5000000'];
        } else {
            // Hari biasa → wajib isi saldo akhir
            $rules['saldo_kartu'] = ['required','integer','min:0','max:5000000'];
        }

        $data = $request->validate($rules, [], [], 'saldo');

        // ===== Hitung saldo akhir bila perlu (khusus hari pertama) =====
        $CAP     = 5_000_000;
        $PER_TAP = 10_000;

        $saldoBaru = array_key_exists('saldo_kartu', $data) ? $data['saldo_kartu'] : null;

        if ($isFirstDay && ($saldoBaru === null || $saldoBaru === '')) {
            $totalTap  = array_key_exists('total_tap',  $data) ? $data['total_tap']  : null;
            $saldoAwal = array_key_exists('saldo_awal', $data) ? $data['saldo_awal'] : null;

            if ($totalTap !== null) {
                // Ada total tap manual → prioritas utama
                if ($saldoAwal !== null) {
                    $saldoBaru = max(0, min($CAP, (int)$saldoAwal - (int)$totalTap * $PER_TAP));
                } else {
                    // Jika saldo awal tak diisi, asumsikan dari CAP
                    $saldoBaru = max(0, $CAP - (int)$totalTap * $PER_TAP);
                }
            } elseif ($saldoAwal !== null) {
                // Hanya saldo awal yang diisi → jadikan saldo akhir (fallback aman)
                $saldoBaru = max(0, min($CAP, (int)$saldoAwal));
            } else {
                // Tidak ada apa pun → minta salah satu diisi
                return back()
                    ->withInput()
                    ->withErrors(['saldo_kartu' => 'Isi salah satu: Saldo Akhir (saldo_kartu), Total Tap, atau Saldo Awal.'], 'saldo');
            }
        }

        // ===== Payload simpan =====
        $payload = [
            'saldo_baru'        => (int) $saldoBaru,
            'tap_gagal'         => (int) ($data['tap_gagal'] ?? 0),
            // simpan input manual bila ada (nullable kolomnya)
            'total_tap_manual'  => array_key_exists('total_tap',  $data) ? $data['total_tap']  : null,
            'saldo_awal_manual' => array_key_exists('saldo_awal', $data) ? $data['saldo_awal'] : null,
        ];

        try {
            // 1) Transaction + lock baris hari ini
            DB::transaction(function () use ($payload) {
                $row = SaldoKartu::whereDate('created_at', today())
                    ->lockForUpdate()
                    ->first();

                if ($row) {
                    $row->update($payload);
                } else {
                    SaldoKartu::create($payload); // created_at = now()
                }
            }, 3);

            return back()->with('ok', 'Saldo kartu berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('[storeSaldo] Gagal simpan saldo kartu', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

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
        $map   = MetodePembayaran::pluck('id', 'nama');
        $newId = $map[$r->metode] ?? null;
        if (!$newId) {
            return back()->withErrors(['metode' => 'Metode tidak valid.']);
        }

        // Batas "hari ini" (menggunakan timezone app)
        $todayStart = now()->startOfDay();
        $todayEnd   = now()->endOfDay();

        DB::transaction(function () use ($pesanan, $newId, $todayStart, $todayEnd) {
            // 1) update metode di pesanan
            $pesanan->update(['metode_pembayaran_id' => $newId]);

            // 2) Jika perubahan terjadi DI HARI YANG SAMA dengan created_at pesanan,
            //    ikutkan sinkronisasi ke REKAP yang TER-LINK (buku kas hari ini boleh berubah).
            if ($pesanan->created_at->between($todayStart, $todayEnd)) {
                Rekap::where('pesanan_laundry_id', $pesanan->id)
                    ->update(['metode_pembayaran_id' => $newId]);
                // catatan: kalau baris rekap tidak ada karena alasan tertentu, update() = 0; aman.
            }

            // 3) Jika perubahan terjadi di HARI BERBEDA, TIDAK menyentuh rekap lama.
            //    Cash akan dihitung oleh fallback (pesanan kini TUNAI tapi rekap lama bukan tunai).
        });

        return back()->with('ok', 'Metode pembayaran pesanan diperbarui.');
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