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
    public function index(Request $r)
    {
        $svcNotFee = function ($q) {
            $q->where('is_fee_service', false);
        };

        // === TANGGAL TERPILIH ===
        $day     = $r->query('d') ? Carbon::parse($r->query('d')) : today();
        $start   = $day->copy()->startOfDay();
        $end     = $day->copy()->endOfDay();
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
            // penting: load is_fee_service juga
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
        $feeHordeng  = 0; // gabung kecil & besar (dua2nya @3.000)
        $feeBoneka   = 0; // gabung besar & kecil (dua2nya @1.000)
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

        // Carry-over lipat: hitung fee lipat yang “jatuh tempo” hari ini
        $lipatToEnd     = $this->sumKgLipatUntil($end);                    // EXCLUDE bed cover
        $lipatToPrevEnd = $this->sumKgLipatUntil($start->copy()->subSecond());

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
            ->whereHas('service', $svcNotFee) // <—
            ->sum('total');

        $cashKeluarTunaiCum = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        $extraCashFromBonLunasTunaiCum = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0) // <—
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

        // AJ dibayar QRIS (akumulasi & hari ini)
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

        // Kas “disesuaikan” = kas tunai murni - AJ-QRIS (karena disalurkan ke kurir)
        $totalCashAdj = $totalCash - $ajQrisCum;


        // Piutang = bon (harga terkunci)
        $totalPiutang = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0) // <—
            ->where('pesanan_laundry.created_at', '<=', $end)
            ->where(function ($q) use ($idBon, $end) {
                $q->where(function ($qq) use ($idBon, $end) {
                    $qq->where('pesanan_laundry.metode_pembayaran_id', $idBon)
                        ->orWhere(function ($qqq) use ($end) {
                            $qqq->whereNotNull('pesanan_laundry.paid_at')
                                ->where('pesanan_laundry.paid_at', '>', $end);
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

        $bon = PesananLaundry::with(['service', 'metode'])
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
            ->whereHas('service', $svcNotFee) // <—
            ->sum('total');

        $totalOmzetBersihHariIni = max(0, $totalOmzetKotorHariIni - $totalFee);

        $totalTunaiHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee) // <—
            ->sum('total');

        $totalQrisHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idQris)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee) // <—
            ->sum('total');

        // === BREAKDOWN TAMBAHAN (H-1 untuk saldo kemarin) ===
        $cashMasukTunaiCumPrev = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $prevEnd)
            ->whereHas('service', $svcNotFee)
            ->sum('total');
        $cashKeluarTunaiCumPrev = Rekap::whereNull('service_id')->where('metode_pembayaran_id', $idTunai)->where('created_at', '<=', $prevEnd)->sum('total');

        $extraCashFromBonLunasTunaiCumPrev = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0)  // ← tambahkan ini
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

        $saldoCashKemarin = $cashMasukTunaiCumPrev + $extraCashFromBonLunasTunaiCumPrev - $cashKeluarTunaiCumPrev - $totalFeeCumPrev;

        // ---- Mutasi CASH HARI INI (pakai harga terkunci)
        $penjualanTunaiHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee) // <—
            ->sum('total');

        $pelunasanBonTunaiHariIni = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0) // <—
            ->where('pesanan_laundry.created_at', '<', $start)
            ->whereBetween('pesanan_laundry.paid_at', [$start, $end])
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        $pengeluaranTunaiHariIni = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        // ---- BON breakdown (harga terkunci)
        $bonKemarin = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0) // <—
            ->where('pesanan_laundry.created_at', '<=', $prevEnd)
            ->where(function ($q) use ($prevEnd, $idBon, $idTunai, $idQris) {
                $q->where('pesanan_laundry.metode_pembayaran_id', $idBon)
                    ->orWhere(function ($qq) use ($prevEnd) {
                        $qq->whereNotNull('pesanan_laundry.paid_at')
                            ->where('pesanan_laundry.paid_at', '>', $prevEnd);
                    });
            })
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        $bonMasukHariIni = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0) // <—
            ->whereBetween('pesanan_laundry.created_at', [$start, $end])
            ->where('pesanan_laundry.metode_pembayaran_id', $idBon)
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        $bonDilunasiHariIni = PesananLaundry::query()
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0) // <—
            ->where('pesanan_laundry.created_at', '<', $start)
            ->whereBetween('pesanan_laundry.paid_at', [$start, $end])
            ->whereIn('pesanan_laundry.metode_pembayaran_id', [$idTunai, $idQris])
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        $totalBonHariIni = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idBon)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee) // <—
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
            'feeSatuan', // ← fee kategori lain (HARI INI)
            'sisaLipatBaru',
            'kgLipatTerbayar',
            'setrikaKgTotal',
            'bedCoverCount',
            'hordengKecilCount',
            'hordengBesarCount',
            'bonekaBesarCount',
            'bonekaKecilCount',
            'satuanCount', // ← counter buat tampilkan di kartu
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
            'bonKemarin',
            'bonMasukHariIni',
            'bonDilunasiHariIni',
            'totalBonHariIni',
            'adaSaldoKemarin',
            'saldoPrev',
            'ajQrisCum',
            'ajQrisHariIni',
            'totalCashAdj',
        ));
    }

    // input baris rekap omzet/pengeluaran sekali submit
    public function store(Request $r)
    {
        $this->assertTodayOrFail($r);
        try {
            $rawRows = $r->input('rows', []);

            $rows = [];
            foreach ($rawRows as $row) {
                $serviceId = $row['service_id'] ?? null;
                $metodeId  = $row['metode_pembayaran_id'] ?? null;
                $qty       = (int)($row['qty'] ?? 0);
                $subtotal  = (int)($row['subtotal'] ?? 0);
                $total     = (int)($row['total'] ?? 0);

                if (!$serviceId || $qty <= 0 || $subtotal <= 0 || $total <= 0) continue;

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

            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    Rekap::create([
                        'service_id'            => $row['serviceId'],
                        'metode_pembayaran_id'  => $row['metodeId'],
                        'qty'                   => $row['qty'],
                        'harga_satuan'          => $row['subtotal'], // 🔒 kunci unit price
                        'subtotal'              => $row['subtotal'],
                        'total'                 => $row['total'],
                    ]);
                }
            });

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
        $this->assertTodayOrFail($request);
        $isFirstDay = !SaldoKartu::whereDate('created_at', '<', today())->exists();

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
        ];

        try {
            DB::transaction(function () use ($payload) {
                $row = SaldoKartu::whereDate('created_at', today())->lockForUpdate()->first();

                if ($row) $row->update($payload);
                else      SaldoKartu::create($payload);
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
        $this->assertTodayOrFail($r);
        $rekap->delete();
        return back()->with('ok', 'Baris rekap berhasil dihapus.');
    }

    public function destroyGroup(Request $r)
    {
        $this->assertTodayOrFail($r);
        $data = $r->validate([
            'service_id'            => ['required', 'exists:services,id'],
            'metode_pembayaran_id'  => ['nullable', 'exists:metode_pembayaran,id'],
        ]);

        $deleted = Rekap::where('service_id', $data['service_id'])
            ->where('metode_pembayaran_id', $data['metode_pembayaran_id'])
            ->whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])
            ->delete();

        return back()->with('ok', "Grup omzet dihapus ($deleted baris).");
    }

    public function storePengeluaran(Request $r)
    {
        $this->assertTodayOrFail($r);
        try {
            $raw = $r->input('outs', []);

            $rows = [];
            foreach ($raw as $row) {
                $ket      = trim((string)($row['keterangan'] ?? ''));
                $subtotal = (int)($row['subtotal'] ?? 0);
                $tanggal  = $row['tanggal'] ?? null;
                $metode   = $row['metode_pembayaran_id'] ?? null;

                if ($subtotal <= 0) continue;

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
            Log::error('[Rekap.storePengeluaran] gagal', ['msg' => $e->getMessage()]);
            return back()->withInput()
                ->withErrors(['storePengeluaran' => 'Terjadi kesalahan saat menyimpan pengeluaran. Coba lagi.'], 'pengeluaran');
        }
    }

    public function updateBonMetode(Request $r, PesananLaundry $pesanan)
    {
        $this->assertTodayOrFail($r);
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
        // Hanya mengizinkan aksi tulis jika TIDAK ada d, atau d == today (zona waktu app)
        if ($r->has('d')) {
            $d = \Carbon\Carbon::parse($r->query('d'))->toDateString();
            if ($d !== today()->toDateString()) {
                abort(403, 'Input/update rekap hanya diperbolehkan untuk tanggal hari ini.');
            }
        }
    }
}
