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
use App\Models\OpeningSetup;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class RekapController extends Controller
{
    public function index(Request $r)
    {
        $svcNotFee = function ($q) {
            $q->where('is_fee_service', false);
        };

        // === TANGGAL TERPILIH ===
        $day     = $r->query('d') ? Carbon::parse($r->query('d')) : today();
        $start   = $day->copy()->startOfDay();
        $end     = $day->copy()->endOfDay();
        $prevEnd = $day->copy()->subDay()->endOfDay();
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
            ->with(['service:id,nama_service,is_fee_service', 'metode:id,nama'])
            ->groupBy('service_id', 'metode_pembayaran_id')
            ->orderByDesc('max_created_at')
            ->paginate(20, ['*'], 'omset');

        // === PENGELUARAN (rekap tanpa service_id) ===
        $pengeluaran = Rekap::with('metode')
            ->whereNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->paginate(10, ['*'], 'pengeluaran');

        // ===================== TOTAL FEE (HARI INI) =====================
        $rekapHariIni = Rekap::with('service')
            ->whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // counter & fee per kategori
        $lipatKgHariIni = 0;
        $setrikaKgTotal = 0;

        $bedCoverCount     = 0;
        $hordengKecilCount = 0;
        $hordengBesarCount = 0;
        $bonekaBesarCount  = 0;
        $bonekaKecilCount  = 0;
        $satuanCount       = 0;

        $feeLipat    = 0;
        $feeSetrika  = 0;
        $feeBedCover = 0;
        $feeHordeng  = 0;
        $feeBoneka   = 0;
        $feeSatuan   = 0;

        foreach ($rekapHariIni as $row) {
            $qty  = (int) ($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            // -------- LIPAT (EXCLUDE bed cover) --------
            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) {
                $lipatKgHariIni += $qty; // per kg
                continue;
            }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) {
                $lipatKgHariIni += 7 * $qty; // paket 7kg
                continue;
            }

            // -------- BED COVER (Kategori terpisah, fix Rp 3.000/pcs) --------
            if (str_contains($name, 'bed cover')) {
                $bedCoverCount += $qty;
                $feeBedCover   += 3000 * $qty;
                continue;
            }

            // -------- SETRIKA --------
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
            if (str_contains($name, 'setrika')) { // fallback per kg
                $setrikaKgTotal += $qty;
                $feeSetrika     += 1000 * $qty;
                continue;
            }

            // -------- KATEGORI LAIN (fee fix) --------
            if (str_contains($name, 'hordeng kecil')) {
                $hordengKecilCount += $qty;
                $feeHordeng        += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'hordeng besar')) {
                $hordengBesarCount += $qty;
                $feeHordeng        += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'boneka besar')) {
                $bonekaBesarCount += $qty;
                $feeBoneka        += 1000 * $qty;
                continue;
            }
            if (str_contains($name, 'boneka kecil')) {
                $bonekaKecilCount += $qty;
                $feeBoneka        += 1000 * $qty;
                continue;
            }
            if (str_contains($name, 'satuan')) {
                $satuanCount += $qty;
                $feeSatuan   += 1000 * $qty;
                continue;
            }
        }

        // Carry-over lipat
        $lipatToEnd     = $this->sumKgLipatUntil($end);
        $lipatToPrevEnd = $this->sumKgLipatUntil($prevEnd);

        $sisaLipatBaru   = $lipatToEnd % 7;
        $kgLipatTerbayar = (intdiv($lipatToEnd, 7) - intdiv($lipatToPrevEnd, 7)) * 7;
        $feeLipat        = (intdiv($lipatToEnd, 7) - intdiv($lipatToPrevEnd, 7)) * 3000;

        $feeLainnya = $feeBedCover + $feeHordeng + $feeBoneka + $feeSatuan;
        $totalFee   = $feeLipat + $feeSetrika + $feeLainnya;

        // ==================== Ringkasan angka untuk kartu di atas ====================
        // === RINGKASAN CASH (AKUMULASI s.d. $end) ===
        $cashMasukTunaiCum = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->whereHas('service', $svcNotFee)
            ->sum('total');

        $cashKeluarTunaiCum = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        // === Tambahan CASH dari pelunasan BON dibayar TUNAI (termasuk migrasi) ===
        $extraCashFromBonLunasTunaiCum = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0)
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->where('pesanan_laundry.created_at', '<=', $end)
            ->whereNotNull('pesanan_laundry.paid_at')
            ->where('pesanan_laundry.paid_at', '<=', $end)
            ->where(function ($q) use ($idTunai) {
                $q->whereNull('rekap.id')
                    ->orWhere('rekap.metode_pembayaran_id', '<>', $idTunai);
            })
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        // === FEE KUMULATIF s.d. $end (TERMASUK kategori lain) ===
        $rowsToEnd = Rekap::with('service')
            ->whereNotNull('service_id')
            ->where('created_at', '<=', $end)
            ->get();

        $kgLipatTotalCum = 0;
        $feeSetrikaCum   = 0;
        $feeBedCoverCum  = 0;
        $feeHordengCum   = 0;
        $feeBonekaCum    = 0;
        $feeSatuanCum    = 0;

        foreach ($rowsToEnd as $row) {
            $qty  = (int) ($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            // lipat (exclude bed cover)
            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) {
                $kgLipatTotalCum += $qty;
                continue;
            }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) {
                $kgLipatTotalCum += 7 * $qty;
                continue;
            }

            // bed cover
            if (str_contains($name, 'bed cover')) {
                $feeBedCoverCum += 3000 * $qty;
                continue;
            }

            // setrika
            if (str_contains($name, 'cuci setrika express 3kg')) {
                $feeSetrikaCum += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 5kg')) {
                $feeSetrikaCum += 5000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 7kg')) {
                $feeSetrikaCum += 7000 * $qty;
                continue;
            }
            if (str_contains($name, 'setrika')) {
                $feeSetrikaCum += 1000 * $qty;
                continue;
            }

            // lain
            if (str_contains($name, 'hordeng kecil')) {
                $feeHordengCum += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'hordeng besar')) {
                $feeHordengCum += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'boneka besar')) {
                $feeBonekaCum  += 1000 * $qty;
                continue;
            }
            if (str_contains($name, 'boneka kecil')) {
                $feeBonekaCum  += 1000 * $qty;
                continue;
            }
            if (str_contains($name, 'satuan')) {
                $feeSatuanCum  += 1000 * $qty;
                continue;
            }
        }

        $feeLipatCum = intdiv($kgLipatTotalCum, 7) * 3000;
        $feeLainCum  = $feeBedCoverCum + $feeHordengCum + $feeBonekaCum + $feeSatuanCum;
        $totalFeeCum = $feeLipatCum + $feeSetrikaCum + $feeLainCum;

        // === SALDO KAS (AKUMULASI as-of $end) ===
        $totalCash = $cashMasukTunaiCum + $extraCashFromBonLunasTunaiCum - $cashKeluarTunaiCum - $totalFeeCum;

        // === AJ dibayar QRIS (akumulasi & hari ini) ===
        $ajQrisCum = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idQris)
            ->where('created_at', '<=', $end)
            ->whereHas('service', fn($q) => $q->where('is_fee_service', 1))
            ->sum('total');

        $ajQrisHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idQris)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', fn($q) => $q->where('is_fee_service', 1))
            ->sum('total');

        // AJ dibayar QRIS s.d. H-1 (≤ prevEnd)
        $ajQrisCumPrev = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idQris)
            ->where('created_at', '<=', $prevEnd)
            ->whereHas('service', fn($q) => $q->where('is_fee_service', 1))
            ->sum('total');

        // ====== OPENING KAS (menambah saldo jika melewati cutover) ======
        $opening = OpeningSetup::latest('id')->first();
        $openingCash = 0;
        $openingCashForDisplay = 0;
        if ($opening && $opening->cutover_date) {
            $cut = Carbon::parse($opening->cutover_date)->copy()->endOfDay();
            if ($cut->lte($end)) {
                $openingCash = (int)$opening->init_cash;
                $totalCash += $openingCash;
            }
            
            $cutStart = Carbon::parse($opening->cutover_date)->copy()->startOfDay();
            $cutEnd = Carbon::parse($opening->cutover_date)->copy()->endOfDay();
            if ($start->between($cutStart, $cutEnd)) {
                $openingCashForDisplay = (int)$opening->init_cash;
            }
        }

        // Kas “disesuaikan” = kas tunai murni - AJ-QRIS
        $totalCashAdj = $totalCash - $ajQrisCum;

        // Piutang = bon (harga terkunci)
        $totalPiutang = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0)
            ->where('pesanan_laundry.created_at', '<=', $end)
            ->where(function ($q) use ($idBon, $end) {
                $q->where(function ($qq) use ($idBon, $end) {
                    $qq->where('pesanan_laundry.metode_pembayaran_id', $idBon)
                        ->orWhere(function ($qqq) use ($end) {
                            $qqq->whereNotNull('paid_at')
                                ->where('paid_at', '>', $end);
                        });
                });
            })
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        // -------------------------------------------------------

        // List pesanan LUNAS (dibuat hari ini)
        $lunas = PesananLaundry::with('service', 'metode')
            ->whereIn('metode_pembayaran_id', array_filter([$idTunai, $idQris]))
            ->whereBetween('created_at', [$start, $end])
            ->latest()->paginate(10, ['*'], 'lunas');

        // === TABEL BON PELANGGAN ===
        $bon = PesananLaundry::with(['service', 'metode'])
            ->where('created_at', '<=', $end)
            ->where(function ($q) use ($idBon, $idTunai, $idQris, $start, $end) {

                // (1) SEMUA yang masih BON (termasuk yang dibuat hari ini)
                $q->where('metode_pembayaran_id', $idBon)

                    // (2) BON LAMA (dibuat sebelum hari ini) yang dilunasi hari ini (tunai/qris)
                    ->orWhere(function ($qq) use ($start, $end, $idTunai, $idQris) {
                        $qq->where('created_at', '<', $start)
                            ->whereBetween('paid_at', [$start, $end])
                            ->whereIn('metode_pembayaran_id', [$idTunai, $idQris]);
                    })

                    // (3) BON MIGRASI yang dibuat & dilunasi hari ini → tetap tampil
                    ->orWhere(function ($qq) use ($start, $end, $idTunai, $idQris) {
                        $qq->whereBetween('created_at', [$start, $end])
                            ->whereBetween('paid_at',   [$start, $end])
                            ->whereIn('metode_pembayaran_id', [$idTunai, $idQris])
                            ->whereExists(function ($q3) {
                                $q3->select(DB::raw(1))
                                    ->from('status_pesanan')
                                    ->whereColumn('status_pesanan.pesanan_id', 'pesanan_laundry.id')
                                    ->where('status_pesanan.keterangan', 'like', 'BON (Migrasi)%');
                            });
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
            if (!is_null($saldoRowDay->manual_total_tap) && $saldoRowDay->manual_total_tap > 0) {
                $totalTapHariIni = (int) $saldoRowDay->manual_total_tap;
            } elseif ($saldoRowPrev) {
                $saldoToday = max(0, min($CAP, (int)$saldoRowDay->saldo_baru));
                $saldoPrevR = max(0, min($CAP, (int)$saldoRowPrev->saldo_baru));

                if ($saldoToday <= $saldoPrevR) {
                    $totalTapHariIni = intdiv($saldoPrevR - $saldoToday, $PER_TAP);
                } else {
                    $totalTapHariIni = intdiv($saldoPrevR, $PER_TAP) + intdiv($CAP - $saldoToday, $PER_TAP);
                }
            }
        }
        $totalTapHariIni = max(0, $totalTapHariIni);

        $adaSaldoKemarin = \App\Models\SaldoKartu::where('created_at', '<', $start)->exists();

        // Total Omzet (hari ini)
        $totalOmzetKotorHariIni = Rekap::whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee)
            ->sum('total');

        $totalOmzetBersihHariIni = max(0, $totalOmzetKotorHariIni - $totalFee);

        $totalTunaiHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee)
            ->sum('total');

        $totalQrisHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idQris)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee)
            ->sum('total');

        // === BREAKDOWN TAMBAHAN (H-1 untuk saldo kemarin) ===
        $cashMasukTunaiCumPrev = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $prevEnd)
            ->whereHas('service', $svcNotFee)
            ->sum('total');

        $cashKeluarTunaiCumPrev = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $prevEnd)
            ->sum('total');

        $extraCashFromBonLunasTunaiCumPrev = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0)
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->where('pesanan_laundry.created_at', '<=', $prevEnd)
            ->whereNotNull('pesanan_laundry.paid_at')
            ->where('pesanan_laundry.paid_at', '<=', $prevEnd)
            ->where(function ($q) use ($idTunai) {
                $q->whereNull('rekap.id')
                    ->orWhere('rekap.metode_pembayaran_id', '<>', $idTunai);
            })
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        // FEE s.d. H-1 (TERMASUK kategori lain)
        $rowsToPrevEnd = Rekap::with('service')
            ->whereNotNull('service_id')
            ->where('created_at', '<=', $prevEnd)
            ->get();

        $kgLipatTotalCumPrev = 0;
        $feeSetrikaCumPrev   = 0;
        $feeBedCoverCumPrev  = 0;
        $feeHordengCumPrev   = 0;
        $feeBonekaCumPrev    = 0;
        $feeSatuanCumPrev    = 0;

        foreach ($rowsToPrevEnd as $row) {
            $qty  = (int) ($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) {
                $kgLipatTotalCumPrev += $qty;
                continue;
            }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) {
                $kgLipatTotalCumPrev += 7 * $qty;
                continue;
            }

            if (str_contains($name, 'bed cover')) {
                $feeBedCoverCumPrev += 3000 * $qty;
                continue;
            }

            if (str_contains($name, 'cuci setrika express 3kg')) {
                $feeSetrikaCumPrev += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 5kg')) {
                $feeSetrikaCumPrev += 5000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 7kg')) {
                $feeSetrikaCumPrev += 7000 * $qty;
                continue;
            }
            if (str_contains($name, 'setrika')) {
                $feeSetrikaCumPrev += 1000 * $qty;
                continue;
            }

            if (str_contains($name, 'hordeng kecil')) {
                $feeHordengCumPrev += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'hordeng besar')) {
                $feeHordengCumPrev += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'boneka besar')) {
                $feeBonekaCumPrev  += 1000 * $qty;
                continue;
            }
            if (str_contains($name, 'boneka kecil')) {
                $feeBonekaCumPrev  += 1000 * $qty;
                continue;
            }
            if (str_contains($name, 'satuan')) {
                $feeSatuanCumPrev  += 1000 * $qty;
                continue;
            }
        }

        $feeLipatCumPrev = intdiv($kgLipatTotalCumPrev, 7) * 3000;
        $feeLainCumPrev  = $feeBedCoverCumPrev + $feeHordengCumPrev + $feeBonekaCumPrev + $feeSatuanCumPrev;
        $totalFeeCumPrev = $feeLipatCumPrev + $feeSetrikaCumPrev + $feeLainCumPrev;

        $saldoCashKemarin = $cashMasukTunaiCumPrev + $extraCashFromBonLunasTunaiCumPrev - $cashKeluarTunaiCumPrev - $totalFeeCumPrev - $ajQrisCumPrev;

        // ====== TAMBAH OPENING UNTUK SALDO KEMARIN (jika cutover <= prevEnd) ======
        if ($opening && $opening->cutover_date) {
            $cutPrev = Carbon::parse($opening->cutover_date)->copy()->endOfDay();
            if ($cutPrev->lte($prevEnd)) {
                $saldoCashKemarin += (int)$opening->init_cash;
            }
        }

        // ---- Mutasi CASH HARI INI (pakai harga terkunci)
        $penjualanTunaiHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee)
            ->sum('total');

        // ---- Pelunasan BON (tunai) HARI INI, termasuk bon migrasi
        $pelunasanBonTunaiHariIni = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0)
            ->whereBetween('pesanan_laundry.paid_at', [$start, $end])
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->where(function ($q) use ($start) {
                $q->where('pesanan_laundry.created_at', '<', $start) // bon lama
                    ->orWhereExists(function ($qq) {                   // bon MIGRASI (dibuat hari ini)
                        $qq->select(DB::raw(1))
                            ->from('status_pesanan')
                            ->whereColumn('status_pesanan.pesanan_id', 'pesanan_laundry.id')
                            ->where('status_pesanan.keterangan', 'like', 'BON (Migrasi)%');
                    });
            })
            ->sum(DB::raw(
                'GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'
            ));

        $pengeluaranTunaiHariIni = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        // Breakdown pengeluaran tunai: pisahkan tarik kas, fee ongkir, gaji, dan pengeluaran normal
        $ownerDrawWords = ['bos', 'kanjeng', 'ambil duit', 'ambil duid', 'tarik kas'];
        $feeOngkirWords = ['ongkir', 'anter jemput', 'antar jemput'];
        $gajiWords = ['gaji'];
        
        $tarikKasHariIni = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($q) use ($ownerDrawWords) {
                foreach ($ownerDrawWords as $w) {
                    $q->orWhereRaw('LOWER(COALESCE(keterangan,"")) LIKE ?', ['%' . strtolower($w) . '%']);
                }
            })
            ->sum('total');
        
        $feeOngkirHariIni = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($q) use ($feeOngkirWords) {
                foreach ($feeOngkirWords as $w) {
                    $q->orWhereRaw('LOWER(COALESCE(keterangan,"")) LIKE ?', ['%' . strtolower($w) . '%']);
                }
            })
            ->sum('total');
        
        $gajiHariIni = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($q) use ($gajiWords) {
                foreach ($gajiWords as $w) {
                    $q->orWhereRaw('LOWER(COALESCE(keterangan,"")) LIKE ?', ['%' . strtolower($w) . '%']);
                }
            })
            ->sum('total');
        
        $pengeluaranTunaiMurniHariIni = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($q) use ($ownerDrawWords, $feeOngkirWords, $gajiWords) {
                // Exclude tarik kas
                foreach ($ownerDrawWords as $w) {
                    $q->whereRaw('LOWER(COALESCE(keterangan,"")) NOT LIKE ?', ['%' . strtolower($w) . '%']);
                }
                // Exclude fee ongkir
                foreach ($feeOngkirWords as $w) {
                    $q->whereRaw('LOWER(COALESCE(keterangan,"")) NOT LIKE ?', ['%' . strtolower($w) . '%']);
                }
                // Exclude gaji
                foreach ($gajiWords as $w) {
                    $q->whereRaw('LOWER(COALESCE(keterangan,"")) NOT LIKE ?', ['%' . strtolower($w) . '%']);
                }
            })
            ->sum('total');

        // ---- BON breakdown (harga terkunci)
        $bonKemarin = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0)
            ->where('pesanan_laundry.created_at', '<=', $prevEnd)
            ->where(function ($q) use ($prevEnd, $idBon) {
                $q->where('pesanan_laundry.metode_pembayaran_id', $idBon)
                    ->orWhere(function ($qq) use ($prevEnd) {
                        $qq->whereNotNull('paid_at')
                            ->where('paid_at', '>', $prevEnd);
                    });
            })
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        $bonMasukHariIni = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0)
            ->whereBetween('pesanan_laundry.created_at', [$start, $end])
            ->where('pesanan_laundry.metode_pembayaran_id', $idBon)
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        $bonDilunasiHariIni = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0)
            ->where('pesanan_laundry.created_at', '<', $start)
            ->whereBetween('pesanan_laundry.paid_at', [$start, $end])
            ->whereIn('pesanan_laundry.metode_pembayaran_id', [$idTunai, $idQris])
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        $totalBonHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idBon)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee)
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
            'feeBedCover',
            'feeHordeng',
            'feeBoneka',
            'feeSatuan',
            'sisaLipatBaru',
            'kgLipatTerbayar',
            'setrikaKgTotal',
            'bedCoverCount',
            'hordengKecilCount',
            'hordengBesarCount',
            'bonekaBesarCount',
            'bonekaKecilCount',
            'satuanCount',
            'lunas',
            'bon',
            'saldoKartu',
            'totalOmzetBersihHariIni',
            'totalOmzetKotorHariIni',
            'totalTunaiHariIni',
            'totalQrisHariIni',
            'totalTapHariIni',
            'tapGagalHariIni',
            'day',
            'isToday',
            'saldoCashKemarin',
            'penjualanTunaiHariIni',
            'pelunasanBonTunaiHariIni',
            'pengeluaranTunaiHariIni',
            'tarikKasHariIni',
            'feeOngkirHariIni',
            'gajiHariIni',
            'pengeluaranTunaiMurniHariIni',
            'bonKemarin',
            'bonMasukHariIni',
            'bonDilunasiHariIni',
            'totalBonHariIni',
            'adaSaldoKemarin',
            'saldoPrev',
            'ajQrisCum',
            'ajQrisHariIni',
            'totalCashAdj',
            'ajQrisCumPrev',
            'openingCashForDisplay',
        ));
    }

    // input baris rekap omzet/pengeluaran sekali submit
    public function store(Request $r)
    {
        $this->assertEditableOrFail($r);
        
        // Tentukan tanggal target dari POST body atau default hari ini
        $targetDate = $r->input('d') 
            ? \Carbon\Carbon::parse($r->input('d'))->setTime(now()->hour, now()->minute, now()->second)
            : now();
            
        Log::info('[Rekap.store] START', [
            'd_param' => $r->input('d'),
            'target_date' => $targetDate->toDateTimeString(),
        ]);
            
        try {
            $rawRows = $r->input('rows', []);

            $rows = [];
            $bonId = MetodePembayaran::where('nama', 'bon')->value('id');
            
            foreach ($rawRows as $row) {
                $serviceId = $row['service_id'] ?? null;
                $metodeId  = $row['metode_pembayaran_id'] ?? null;
                $qty       = (int)($row['qty'] ?? 0);
                $subtotal  = (int)($row['subtotal'] ?? 0);
                $total     = (int)($row['total'] ?? 0);

                if (!$serviceId || $qty <= 0 || $subtotal <= 0 || $total <= 0) continue;
                
                // BLOKIR transaksi BON di mode revisi H-1
                if ($targetDate->isYesterday() && $metodeId == $bonId) {
                    return back()->withInput()
                        ->withErrors(['rows' => 'Transaksi BON tidak dapat ditambahkan atau direvisi di hari sebelumnya. Hubungi admin untuk koreksi manual.'], 'omzet');
                }

                $rows[] = compact('serviceId', 'metodeId', 'qty', 'subtotal', 'total');
            }

            if (count($rows) === 0) {
                return back()->withInput()
                    ->withErrors(['rows' => 'Tidak ada baris omzet yang valid. Pilih layanan dan isi jumlah/harga.'], 'omzet');
            }

            $r->validate([
                'rows.*.service_id'           => ['nullable', 'exists:services,id'],
                'rows.*.metode_pembayaran_id' => ['nullable', 'exists:metode_pembayaran,id'],
            ]);

            $savedIds = [];
            DB::transaction(function () use ($rows, $targetDate, &$savedIds) {
                foreach ($rows as $row) {
                    $rekap = Rekap::create([
                        'service_id'            => $row['serviceId'],
                        'metode_pembayaran_id'  => $row['metodeId'],
                        'qty'                   => $row['qty'],
                        'harga_satuan'          => $row['subtotal'],
                        'subtotal'              => $row['subtotal'],
                        'total'                 => $row['total'],
                        'created_at'            => $targetDate,
                        'updated_at'            => $targetDate,
                    ]);
                    $savedIds[] = $rekap->id;
                }
            });
            
            Log::info('[Rekap.store] SUCCESS', [
                'saved_ids' => $savedIds,
                'count' => count($savedIds),
            ]);

            return back()->with('ok', 'Rekap omzet berhasil disimpan.');
        } catch (\Throwable $e) {
            Log::error('[Rekap.store] gagal', ['msg' => $e->getMessage()]);
            return back()->withInput()
                ->withErrors(['store' => 'Terjadi kesalahan saat menyimpan rekap omzet. Coba lagi.'], 'omzet');
        }
    }

    public function input(Request $r)
    {
        $services = Service::all();
        $metodes  = MetodePembayaran::all();

        $day   = $r->query('d') ? \Carbon\Carbon::parse($r->query('d')) : today();
        $start = $day->copy()->startOfDay();

        $hasPrev = SaldoKartu::where('created_at', '<', $start)->exists();
        $adaSaldoKemarin = $hasPrev;

        return view('admin.rekap.input', compact('services', 'metodes', 'adaSaldoKemarin', 'day'));
    }

    public function storeSaldo(Request $request)
    {
        $this->assertEditableOrFail($request);
        
        // Tentukan tanggal target dari POST body atau default hari ini
        $targetDate = $request->input('d') 
            ? \Carbon\Carbon::parse($request->input('d'))->setTime(now()->hour, now()->minute, now()->second)
            : now();
            
        $isFirstDay = !SaldoKartu::whereDate('created_at', '<', $targetDate)->exists();

        $rules = [
            'tap_gagal' => ['required', 'integer', 'min:0'],
        ];

        if ($isFirstDay) {
            $rules['saldo_kartu']      = ['nullable', 'integer', 'min:0', 'max:5000000'];
            $rules['manual_total_tap'] = ['nullable', 'integer', 'min:0'];
        } else {
            $rules['saldo_kartu'] = ['required', 'integer', 'min:0', 'max:5000000'];
        }

        $data = $request->validate($rules, [], [], 'saldo');

        $payload = [
            'saldo_baru'        => (int) ($data['saldo_kartu'] ?? 0),
            'tap_gagal'         => (int) ($data['tap_gagal'] ?? 0),
            'manual_total_tap'  => $isFirstDay ? ($data['manual_total_tap'] ?? null) : null,
            'created_at'        => $targetDate,
            'updated_at'        => $targetDate,
        ];

        try {
            DB::transaction(function () use ($payload, $targetDate) {
                $row = SaldoKartu::whereDate('created_at', $targetDate)->lockForUpdate()->first();

                if ($row) {
                    $row->update($payload);
                } else {
                    SaldoKartu::create($payload);
                }
            });

            return back()->with('ok', 'Saldo kartu berhasil disimpan.');
        } catch (\Throwable $e) {
            Log::error('[storeSaldo] Gagal simpan saldo kartu', ['message' => $e->getMessage()]);
            return back()->withInput()
                ->withErrors(['storeSaldo' => 'Terjadi kesalahan saat menyimpan saldo.'], 'saldo');
        }
    }

    public function destroy(Rekap $rekap, Request $r)
    {
        $this->assertEditableOrFail($r);
        $rekap->delete();
        return back()->with('ok', 'Baris rekap berhasil dihapus.');
    }

    public function destroyGroup(Request $r)
    {
        $this->assertEditableOrFail($r);
        
        // Tentukan tanggal target dari request atau default hari ini
        $targetDate = $r->has('d') 
            ? \Carbon\Carbon::parse($r->query('d'))
            : today();
            
        $data = $r->validate([
            'service_id'            => ['required', 'exists:services,id'],
            'metode_pembayaran_id'  => ['nullable', 'exists:metode_pembayaran,id'],
        ]);
        
        // BLOKIR hapus transaksi BON di mode revisi H-1
        $bonId = MetodePembayaran::where('nama', 'bon')->value('id');
        if ($targetDate->isYesterday() && $data['metode_pembayaran_id'] == $bonId) {
            return back()->withErrors(['destroyGroup' => 'Transaksi BON tidak dapat dihapus di hari sebelumnya. Hubungi admin untuk koreksi manual.']);
        }

        $deleted = Rekap::where('service_id', $data['service_id'])
            ->where('metode_pembayaran_id', $data['metode_pembayaran_id'])
            ->whereBetween('created_at', [$targetDate->copy()->startOfDay(), $targetDate->copy()->endOfDay()])
            ->delete();

        return back()->with('ok', "Grup omzet dihapus ($deleted baris).");
    }

    public function storePengeluaran(Request $r)
    {
        $this->assertEditableOrFail($r);
        
        // Tentukan tanggal target dari POST body atau default hari ini
        $targetDate = $r->input('d') 
            ? \Carbon\Carbon::parse($r->input('d'))
            : today();
            
        try {
            $raw = $r->input('outs', []);

            $rows = [];
            $bonId = MetodePembayaran::where('nama', 'bon')->value('id');
            
            foreach ($raw as $row) {
                $ket      = trim((string)($row['keterangan'] ?? ''));
                $subtotal = (int)($row['subtotal'] ?? 0);
                $tanggal  = $row['tanggal'] ?? null;
                $metode   = $row['metode_pembayaran_id'] ?? null;

                if ($subtotal <= 0) continue;
                
                // BLOKIR transaksi BON di mode revisi H-1
                if ($targetDate->isYesterday() && $metode == $bonId) {
                    return back()->withInput()
                        ->withErrors(['outs' => 'Transaksi BON tidak dapat ditambahkan atau direvisi di hari sebelumnya. Hubungi admin untuk koreksi manual.'], 'pengeluaran');
                }

                $rows[] = [
                    'keterangan' => $ket,
                    'subtotal'   => $subtotal,
                    'tanggal'    => $tanggal,
                    'metode'     => $metode,
                ];
            }

            if (count($rows) === 0) {
                return back()->withInput()
                    ->withErrors(['outs' => 'Tidak ada baris pengeluaran yang valid. Isi nominal (> 0).'], 'pengeluaran');
            }

            $r->validate([
                'outs.*.metode_pembayaran_id' => ['nullable', 'exists:metode_pembayaran,id'],
            ]);

            DB::transaction(function () use ($rows, $targetDate) {
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
                            : $targetDate,
                        'updated_at'            => $targetDate,
                    ]);
                }
            });

            return back()->with('ok', 'Pengeluaran berhasil disimpan.');
        } catch (\Throwable $e) {
            Log::error('[Rekap.storePengeluaran] gagal', ['msg' => $e->getMessage()]);
            return back()->withInput()
                ->withErrors(['storePengeluaran' => 'Terjadi kesalahan saat menyimpan pengeluaran. Coba lagi.'], 'pengeluaran');
        }
    }

    public function updateBonMetode(Request $r, PesananLaundry $pesanan)
    {
        $this->assertEditableOrFail($r);
        
        // BLOKIR update metode BON di mode revisi H-1
        $targetDate = $r->input('d') 
            ? \Carbon\Carbon::parse($r->input('d'))
            : today();
            
        if ($targetDate->isYesterday()) {
            return back()->withErrors(['metode' => 'Metode pembayaran BON tidak dapat diubah di hari sebelumnya. Hubungi admin untuk koreksi manual.']);
        }
        
        $r->validate([
            'metode' => ['required', 'in:bon,tunai,qris'],
        ]);

        $map   = MetodePembayaran::pluck('id', 'nama');
        $newId = $map[$r->metode] ?? null;
        if (!$newId) return back()->withErrors(['metode' => 'Metode tidak valid.']);

        $idBon   = $map['bon']  ?? null;
        $idTunai = $map['tunai'] ?? null;
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

    // === HELPER: total KG lipat kumulatif s.d. $until (EXCLUDE bed cover) ===
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

            // bed cover TIDAK dihitung sebagai lipat lagi
        }
        return $total;
    }

    private function assertTodayOrFail(Request $r): void
    {
        if ($r->has('d')) {
            $d = \Carbon\Carbon::parse($r->query('d'))->toDateString();
            if ($d !== today()->toDateString()) {
                abort(403, 'Input/update rekap hanya diperbolehkan untuk tanggal hari ini.');
            }
        }
    }

    /**
     * Validasi: Hanya izinkan edit untuk hari ini (H) atau kemarin (H-1)
     * H-2 dan sebelumnya = read-only
     */
    private function assertEditableOrFail(Request $r): void
    {
        // Cek dari POST body dulu, jika tidak ada baru cek query string
        $targetDate = $r->input('d') ?: $r->query('d');
        
        if ($targetDate) {
            $targetDate = \Carbon\Carbon::parse($targetDate)->toDateString();
        } else {
            $targetDate = today()->toDateString();
        }

        $today = today()->toDateString();
        $yesterday = today()->subDay()->toDateString();

        if ($targetDate !== $today && $targetDate !== $yesterday) {
            abort(403, 'Edit rekap hanya diperbolehkan untuk hari ini atau kemarin (H-1). Data tanggal lebih lama bersifat read-only.');
        }
    }

    public function storeOpening(Request $r)
    {
        $this->assertTodayOrFail($r);

        $latest = OpeningSetup::latest('id')->first();
        if ($latest && $latest->locked) {
            return back()->with('ok_opening', 'Opening sudah dikunci. Tidak bisa diubah dari sini.');
        }

        $data = $r->validate([
            'init_cash'    => ['required', 'integer', 'min:0'],
            'cutover_date' => ['nullable', 'date'],
        ]);

        $cutover = $data['cutover_date'] ?? now()->toDateString();

        if (!$latest) {
            OpeningSetup::create([
                'init_cash'    => (int)$data['init_cash'],
                'cutover_date' => $cutover,
                'locked'       => false,
            ]);
        } else {
            $latest->update([
                'init_cash'    => (int)$data['init_cash'],
                'cutover_date' => $cutover,
            ]);
        }

        return back()->with('ok_opening', 'Opening kas awal disimpan.');
    }

    public function lockOpening(Request $r)
    {
        $this->assertTodayOrFail($r);

        $row = OpeningSetup::latest('id')->first();
        if (!$row) {
            return back()->with('ok_opening', 'Belum ada data opening untuk dikunci.');
        }

        if (!$row->locked) {
            $row->update(['locked' => true]);
        }

        return back()->with('ok_opening', 'Opening dikunci. Blok input disembunyikan.');
    }

    /**
     * Get detail omset untuk modal
     */
    public function getOmsetDetail(Request $request)
    {
        try {
            $serviceId = $request->query('service_id');
            $metodeId = $request->query('metode_id');
            $tanggal = $request->query('tanggal', today()->toDateString());

            Log::info('[getOmsetDetail] Request params', [
                'service_id' => $serviceId,
                'metode_id' => $metodeId,
                'tanggal' => $tanggal
            ]);

            if (!$serviceId || !$metodeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter service_id dan metode_id harus diisi',
                    'data' => []
                ], 400);
            }

            $day = Carbon::parse($tanggal);
            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();

            $rekaps = Rekap::with(['service', 'metode'])
                ->where('service_id', $serviceId)
                ->where('metode_pembayaran_id', $metodeId)
                ->whereBetween('created_at', [$start, $end])
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('[getOmsetDetail] Found records', ['count' => $rekaps->count()]);

            if ($rekaps->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan',
                    'data' => []
                ]);
            }

            $data = $rekaps->map(function($rekap) {
                $pelangganName = '-';
                $pelangganHp = null;

                // Cek apakah ada pesanan_laundry_id
                if ($rekap->pesanan_laundry_id) {
                    $pesanan = PesananLaundry::find($rekap->pesanan_laundry_id);
                    if ($pesanan) {
                        $pelangganName = $pesanan->nama_pel;
                        $pelangganHp = $pesanan->no_hp_pel;
                    }
                }

                return [
                    'id' => $rekap->id,
                    'service_name' => $rekap->service->nama_service ?? '-',
                    'metode' => $rekap->metode->nama ?? '-',
                    'qty' => $rekap->qty,
                    'total' => $rekap->total,
                    'pelanggan_name' => $pelangganName,
                    'pelanggan_hp' => $pelangganHp,
                    'tanggal' => $rekap->created_at->format('d/m/Y'),
                    'jam' => $rekap->created_at->format('H:i:s'),
                    'pesanan_laundry_id' => $rekap->pesanan_laundry_id,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('[getOmsetDetail] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Delete omset dan cascade ke pesanan_laundry jika ada
     */
    public function deleteOmset($id)
    {
        try {
            DB::beginTransaction();

            $rekap = Rekap::findOrFail($id);
            $pesananLaundryId = $rekap->pesanan_laundry_id;

            // Hapus rekap
            $rekap->delete();

            // Jika ada foreign key ke pesanan_laundry, hapus juga pesanannya
            if ($pesananLaundryId) {
                $pesanan = PesananLaundry::find($pesananLaundryId);
                if ($pesanan) {
                    // Hapus semua status pesanan terkait
                    $pesanan->statuses()->delete();
                    // Hapus pesanan
                    $pesanan->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $pesananLaundryId 
                    ? 'Data rekap dan pesanan laundry berhasil dihapus'
                    : 'Data rekap berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[deleteOmset] Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
