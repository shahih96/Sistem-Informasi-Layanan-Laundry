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
                $setrikaKgTotal += 3 * $qty;
                $feeSetrika     += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 5kg')) {
                $setrikaKgTotal += 5 * $qty;
                $feeSetrika     += 5000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 7kg')) {
                $setrikaKgTotal += 7 * $qty;
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
        $lipatToEnd = $this->sumKgLipatUntil($end);
        $lipatToPrevEnd = $this->sumKgLipatUntil($start->copy()->subSecond()); // sebelum start

        $sisaLipatBaru     = $lipatToEnd % 7;
        $kgLipatTerbayar   = (intdiv($lipatToEnd, 7) - intdiv($lipatToPrevEnd, 7)) * 7;
        $feeLipat          = (intdiv($lipatToEnd, 7) - intdiv($lipatToPrevEnd, 7)) * 3000;

        $totalFee = $feeLipat + $feeSetrika;

        // ==================== Ringkasan angka untuk kartu di atas ======================================
        // === RINGKASAN CASH (AKUMULASI s.d. $end) ===
        $cashMasukTunaiCum = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        $cashKeluarTunaiCum = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        // Pesanan tunai yang belum pernah dicatat ke rekap (fallback), kumulatif
        // 🔒 pakai harga terkunci
        $extraCashFromBonLunasTunaiCum = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->where('pesanan_laundry.created_at', '<=', $end)
            ->whereNotNull('pesanan_laundry.paid_at')
            ->where('pesanan_laundry.paid_at', '<=', $end)
            ->where(function($q) use ($idTunai) {
                $q->whereNull('rekap.id')
                  ->orWhere('rekap.metode_pembayaran_id', '<>', $idTunai);
            })
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        // === FEE KUMULATIF s.d. $end
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

        // Piutang = bon  🔒 pakai harga terkunci
        $totalPiutang = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->where('pesanan_laundry.created_at','<=',$end)
            ->where(function ($q) use ($idBon, $end) {
                $q->where(function ($qq) use ($idBon, $end) {
                    $qq->where('pesanan_laundry.metode_pembayaran_id', $idBon)
                       ->orWhere(function ($qqq) use ($end) {
                           $qqq->whereNotNull('pesanan_laundry.paid_at')
                               ->where('pesanan_laundry.paid_at','>', $end);
                       });
                });
            })
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        // -------------------------------------------------------

        // List pesanan
        $lunas = PesananLaundry::with('service', 'metode')
            ->whereIn('metode_pembayaran_id', array_filter([$idTunai, $idQris]))
            ->whereBetween('created_at', [$start, $end])
            ->latest()->paginate(10, ['*'], 'lunas');

        $bon = PesananLaundry::with(['service','metode'])
            ->where('created_at', '<=', $end)
            ->where(function ($q) use ($idBon, $idTunai, $idQris, $start, $end) {
                $q->where(function ($qq) use ($idBon, $end) {
                    $qq->where('metode_pembayaran_id', $idBon)
                        ->orWhere(function ($qqq) use ($end) {
                            $qqq->whereNotNull('paid_at')
                                ->where('paid_at', '>', $end);
                        });
                })
                ->orWhere(function ($qq) use ($idTunai, $idQris, $start, $end) {
                    $qq->where('created_at', '<', $start)
                        ->whereBetween('paid_at', [$start, $end])
                        ->whereIn('metode_pembayaran_id', [$idTunai, $idQris]);
                });
            })
            ->latest('created_at')
            ->paginate(20, ['*'], 'bon');

        // === KARTU TAP ===
        $CAP     = 5_000_000;
        $PER_TAP = 10_000;

        $saldoRowDay = SaldoKartu::whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->first();

        $saldoRowPrev = SaldoKartu::where('created_at', '<', $start)
            ->latest('id')
            ->first();

        $saldoPrev = $saldoRowPrev
        ? max(0, min($CAP, (int) $saldoRowPrev->saldo_baru))
        : null;

        $saldoKartu      = $saldoRowDay ? (int) $saldoRowDay->saldo_baru : null;
        $tapGagalHariIni = (int) ($saldoRowDay?->tap_gagal ?? 0);
        $totalTapHariIni = 0;

        if ($saldoRowDay) {
            // Ambil dari input manual kalau ada
            if (!is_null($saldoRowDay->manual_total_tap) && $saldoRowDay->manual_total_tap > 0) {
                $totalTapHariIni = (int) $saldoRowDay->manual_total_tap;
            } elseif ($saldoRowPrev) {
                // Kalau nggak ada input manual, hitung otomatis
                $saldoToday = max(0, min($CAP, (int)$saldoRowDay->saldo_baru));
                $saldoPrev  = max(0, min($CAP, (int)$saldoRowPrev->saldo_baru));
        
                if ($saldoToday <= $saldoPrev) {
                    $totalTapHariIni = intdiv($saldoPrev - $saldoToday, $PER_TAP);
                } else {
                    $totalTapHariIni = intdiv($saldoPrev, $PER_TAP) + intdiv($CAP - $saldoToday, $PER_TAP);
                }
            }
        }        

        $totalTapHariIni = max(0, $totalTapHariIni);

        // cek apakah ada saldo kemarin (untuk menentukan apakah form manual ditampilkan)
        $adaSaldoKemarin = \App\Models\SaldoKartu::where('created_at', '<', $start)->exists();

        // Total Omzet bersih dan kotor Hari Ini
        $totalOmzetKotorHariIni = Rekap::whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');
        $totalOmzetBersihHariIni = max(0, $totalOmzetKotorHariIni - $totalFee);

        $totalTunaiHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $totalQrisHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idQris)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        // === BREAKDOWN TAMBAHAN (pakai harga terkunci) ===
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
            ->whereNotNull('pesanan_laundry.paid_at')
            ->where('pesanan_laundry.paid_at', '<=', $prevEnd)
            ->where(function($q) use ($idTunai) {
                $q->whereNull('rekap.id')
                  ->orWhere('rekap.metode_pembayaran_id', '<>', $idTunai);
            })
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

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

        $saldoCashKemarin = $cashMasukTunaiCumPrev + $extraCashFromBonLunasTunaiCumPrev - $cashKeluarTunaiCumPrev - $totalFeeCumPrev;

        // ---- Mutasi CASH HARI INI (pakai harga terkunci) ----
        $penjualanTunaiHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $pelunasanBonTunaiHariIni = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->where('pesanan_laundry.created_at','<',$start)
            ->whereBetween('pesanan_laundry.paid_at', [$start, $end])
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        $pengeluaranTunaiHariIni = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        // ---- BON breakdown (pakai harga terkunci) ----
        $bonKemarin = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->where('pesanan_laundry.created_at', '<=', $prevEnd)
            ->where(function ($q) use ($prevEnd, $idBon, $idTunai, $idQris) {
                $q->where('pesanan_laundry.metode_pembayaran_id', $idBon)
                  ->orWhere(function ($qq) use ($prevEnd) {
                      $qq->whereNotNull('pesanan_laundry.paid_at')
                         ->where('pesanan_laundry.paid_at', '>', $prevEnd);
                  });
            })
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        $bonMasukHariIni = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->whereBetween('pesanan_laundry.created_at', [$start, $end])
            ->where('pesanan_laundry.metode_pembayaran_id', $idBon)
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        $bonDilunasiHariIni = PesananLaundry::query()
            ->join('services','services.id','=','pesanan_laundry.service_id')
            ->where('pesanan_laundry.created_at','<',$start)
            ->whereBetween('pesanan_laundry.paid_at', [$start, $end])
            ->whereIn('pesanan_laundry.metode_pembayaran_id', [$idTunai, $idQris])
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        // Bon yang tercatat di rekap HARI INI
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
            'adaSaldoKemarin',
            'saldoPrev',
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

                if (!$serviceId || $qty <= 0 || $subtotal <= 0 || $total <= 0) {
                    continue;
                }

                $rows[] = compact('serviceId','metodeId','qty','subtotal','total');
            }

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
                        'harga_satuan'          => $row['subtotal'], // 🔒 kunci unit price dari form
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

    public function input(Request $r)
    {
        $services = Service::all();
        $metodes  = MetodePembayaran::all();
    
        $day   = $r->query('d') ? \Carbon\Carbon::parse($r->query('d')) : today();
        $start = $day->copy()->startOfDay();
    
        // "Hari pertama" = tidak ada saldo sebelum tanggal konteks
        $hasPrev = SaldoKartu::where('created_at', '<', $start)->exists();
        $adaSaldoKemarin = $hasPrev;
    
        return view('admin.rekap.input', compact('services', 'metodes', 'adaSaldoKemarin', 'day'));
    }    

    public function storeSaldo(Request $request)
    {
        $isFirstDay = !SaldoKartu::whereDate('created_at', '<', today())->exists();
    
        $rules = [
            'tap_gagal' => ['required','integer','min:0'],
        ];
    
        if ($isFirstDay) {
            $rules['saldo_kartu']       = ['nullable','integer','min:0','max:5000000'];
            $rules['manual_total_tap']  = ['nullable','integer','min:0'];
        } else {
            $rules['saldo_kartu'] = ['required','integer','min:0','max:5000000'];
        }
    
        $data = $request->validate($rules, [], [], 'saldo');
    
        $payload = [
            'saldo_baru'        => (int) ($data['saldo_kartu'] ?? 0),
            'tap_gagal'         => (int) ($data['tap_gagal'] ?? 0),
            'manual_total_tap'  => $isFirstDay ? ($data['manual_total_tap'] ?? null) : null,
        ];
    
        try {
            DB::transaction(function () use ($payload) {
                $row = SaldoKartu::whereDate('created_at', today())->lockForUpdate()->first();
    
                if ($row) {
                    $row->update($payload);
                } else {
                    SaldoKartu::create($payload);
                }
            });
    
            return back()->with('ok', 'Saldo kartu berhasil disimpan.');
        } catch (\Throwable $e) {
            Log::error('[storeSaldo] Gagal simpan saldo kartu', [
                'message' => $e->getMessage(),
            ]);
    
            return back()
                ->withInput()
                ->withErrors(['storeSaldo' => 'Terjadi kesalahan saat menyimpan saldo.'], 'saldo');
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

            $rows = [];
            foreach ($raw as $row) {
                $ket     = trim((string)($row['keterangan'] ?? ''));
                $subtotal= (int)($row['subtotal'] ?? 0);
                $tanggal = $row['tanggal'] ?? null;
                $metode  = $row['metode_pembayaran_id'] ?? null;

                if ($subtotal <= 0) continue;

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

            $r->validate([
                'outs.*.metode_pembayaran_id' => ['nullable','exists:metode_pembayaran,id'],
            ]);

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

        $map   = MetodePembayaran::pluck('id', 'nama');
        $newId = $map[$r->metode] ?? null;
        if (!$newId) {
            return back()->withErrors(['metode' => 'Metode tidak valid.']);
        }

        $idBon   = $map['bon']  ?? null;
        $idTunai = $map['tunai']?? null;
        $idQris  = $map['qris'] ?? null;
        $oldId   = $pesanan->metode_pembayaran_id;

        DB::transaction(function () use ($pesanan, $newId, $oldId, $idBon, $idTunai, $idQris) {
            $pesanan->update(['metode_pembayaran_id' => $newId]);

            if ($oldId === $idBon && in_array($newId, [$idTunai, $idQris], true)) {
                if (is_null($pesanan->paid_at)) {
                    $pesanan->update(['paid_at' => now()]);
                }
            }

            if (in_array($oldId, [$idTunai, $idQris], true) && $newId === $idBon) {
                $pesanan->update(['paid_at' => null]);
            }

            $todayStart = now()->startOfDay();
            $todayEnd   = now()->endOfDay();
            if ($pesanan->created_at->between($todayStart, $todayEnd)) {
                Rekap::where('pesanan_laundry_id', $pesanan->id)
                    ->update(['metode_pembayaran_id' => $newId]);
            }
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