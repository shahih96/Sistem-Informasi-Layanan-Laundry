<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananLaundry;
use App\Models\Rekap;
use App\Models\MetodePembayaran;
use App\Models\OpeningSetup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        // ====== HARI INI (cards) ======
        $day     = $r->query('d') ? Carbon::parse($r->query('d')) : today();
        $start   = $day->copy()->startOfDay();
        $end     = $day->copy()->endOfDay();
        $prevEnd = $start->copy()->subSecond();

        // filter layanan non-fee (exclude AJ)
        $svcNotFee = function ($q) { $q->where('is_fee_service', false); };

        $totalPesananHariIni = PesananLaundry::whereBetween('created_at', [$start, $end])->count();

        // Pendapatan (kotor) HARI INI -> EXCLUDE layanan fee (AJ)
        $pendapatanHariIni = Rekap::whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('service', $svcNotFee)
            ->sum('total');

        // ====== FEE HARI INI (kategori lengkap) ======
        $rowsToday = Rekap::with('service')
            ->whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $feeSetrika  = 0;
        $feeBedCover = 0;  // 3.000 / item
        $feeHordeng  = 0;  // 3.000 / item
        $feeBoneka   = 0;  // 1.000 / item
        $feeSatuan   = 0;  // 1.000 / item

        foreach ($rowsToday as $row) {
            $qty  = (int)($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name, 'cuci setrika express 3kg')) { $feeSetrika += 3000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 5kg')) { $feeSetrika += 5000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 7kg')) { $feeSetrika += 7000 * $qty; continue; }

            if (str_contains($name, 'bed cover')) { $feeBedCover += 3000 * $qty; continue; }
            if (str_contains($name, 'hordeng'))   { $feeHordeng  += 3000 * $qty; continue; }
            if (str_contains($name, 'boneka'))    { $feeBoneka   += 1000 * $qty; continue; }
            if (str_contains($name, 'satuan'))    { $feeSatuan   += 1000 * $qty; continue; }

            if (str_contains($name, 'setrika'))   { $feeSetrika  += 1000 * $qty; continue; }
        }

        // === FEE LIPAT HARI INI pakai carry-over (SAMA dgn RekapController) ===
        $lipatToEnd       = $this->sumKgLipatUntil($end);
        $lipatToPrevEnd   = $this->sumKgLipatUntil($prevEnd);
        $feeLipatHariIni  = (intdiv($lipatToEnd, 7) - intdiv($lipatToPrevEnd, 7)) * 3000;

        $feeTotalHariIni = $feeLipatHariIni + $feeSetrika + $feeBedCover + $feeHordeng + $feeBoneka + $feeSatuan;

        // Pengeluaran hari ini (exclude owner draw, fee ongkir, dan gaji) untuk Pendapatan Bersih Hari Ini
        $ownerDrawWords = ['bos', 'kanjeng', 'ambil duit', 'ambil duid', 'tarik kas'];
        $feeOngkirWords = ['ongkir', 'anter jemput', 'antar jemput'];
        $gajiWords = ['gaji'];
        
        $pengeluaranHariIni = Rekap::whereBetween('created_at', [$start, $end])
            ->whereNull('service_id')
            ->where(function ($q) use ($ownerDrawWords, $feeOngkirWords, $gajiWords) {
                // Exclude owner draw
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

        // Pendapatan Bersih Hari Ini = Omzet Kotor - Fee - Pengeluaran
        $pendapatanBersihHariIni = max(0, $pendapatanHariIni - $feeTotalHariIni);

        // ====== STATUS PESANAN (KESELURUHAN, bukan hanya hari ini) ======
        // Ambil semua pesanan dengan status terakhirnya
        $allPesanan = PesananLaundry::with(['statuses' => fn($q) => $q->latest()->limit(1)])->get();
        
        // Pesanan Diproses: semua pesanan dengan status terakhir "Diproses"
        $pesananDiproses = $allPesanan->filter(function($p) {
            $lastStatus = $p->statuses->first();
            return $lastStatus && strcasecmp($lastStatus->keterangan, 'Diproses') === 0;
        })->count();
        
        // Pesanan Selesai: semua pesanan dengan status terakhir "Selesai" DAN TIDAK disembunyikan
        $pesananSelesai = $allPesanan->filter(function($p) {
            $lastStatus = $p->statuses->first();
            return !$p->is_hidden && $lastStatus && strcasecmp($lastStatus->keterangan, 'Selesai') === 0;
        })->count();

        $riwayat = PesananLaundry::with(['service', 'statuses' => fn($q) => $q->latest()])->latest()->take(5)->get();

        // ====== TOTAL CASH (akumulasi s.d. $end) ======
        $idTunai = MetodePembayaran::where('nama', 'tunai')->value('id');
        $idQris  = MetodePembayaran::where('nama', 'qris')->value('id');

        // MASUK tunai (exclude AJ)
        $cashMasukTunaiCum = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->whereHas('service', $svcNotFee)
            ->sum('total');

        // KELUAR tunai (pengeluaran)
        $cashKeluarTunaiCum = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        // BON dilunasi TUNAI (harga terkunci) – exclude AJ
        $extraCashFromBonLunasTunaiCum = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('services.is_fee_service', 0)
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->where('pesanan_laundry.created_at', '<=', $end)     // <-- disamakan dg RekapController
            ->whereNotNull('pesanan_laundry.paid_at')
            ->where('pesanan_laundry.paid_at', '<=', $end)
            ->where(function ($q) use ($idTunai) {
                $q->whereNull('rekap.id')->orWhere('rekap.metode_pembayaran_id', '<>', $idTunai);
            })
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        // ====== FEE KUMULATIF (untuk totalCash) – sama dengan RekapController ======
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
            $qty  = (int)($row->qty ?? 0);
            if ($qty <= 0) continue;
            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) { $kgLipatTotalCum += $qty; continue; }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) { $kgLipatTotalCum += 7 * $qty; continue; }

            if (str_contains($name, 'cuci setrika express 3kg')) { $feeSetrikaCum += 3000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 5kg')) { $feeSetrikaCum += 5000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 7kg')) { $feeSetrikaCum += 7000 * $qty; continue; }

            if (str_contains($name, 'bed cover')) { $feeBedCoverCum += 3000 * $qty; continue; }
            if (str_contains($name, 'hordeng'))   { $feeHordengCum  += 3000 * $qty; continue; }
            if (str_contains($name, 'boneka'))    { $feeBonekaCum   += 1000 * $qty; continue; }
            if (str_contains($name, 'satuan'))    { $feeSatuanCum   += 1000 * $qty; continue; }

            if (str_contains($name, 'setrika'))   { $feeSetrikaCum  += 1000 * $qty; continue; }
        }

        $feeLipatCum = intdiv($kgLipatTotalCum, 7) * 3000;
        $totalFeeCum = $feeLipatCum + $feeSetrikaCum + $feeBedCoverCum + $feeHordengCum + $feeBonekaCum + $feeSatuanCum;

        // CASH kumulatif murni (tunai saja)
        $totalCash = $cashMasukTunaiCum + $extraCashFromBonLunasTunaiCum - $cashKeluarTunaiCum - $totalFeeCum;

        // ====== OPENING KAS (samakan dengan RekapController) ======
        $opening = OpeningSetup::latest('id')->first();
        if ($opening && $opening->cutover_date) {
            $cut = Carbon::parse($opening->cutover_date)->endOfDay();
            if ($cut->lte($end)) {
                $totalCash += (int)$opening->init_cash;
            }
        }

        // AJ yang dibayar via QRIS (kumulatif s.d. end)
        $ajQrisCum = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idQris)
            ->where('created_at', '<=', $end)
            ->whereHas('service', fn($q) => $q->where('is_fee_service', 1))
            ->sum('total');

        // Total Cash Disesuaikan (kurangi AJ-QRIS)
        $totalCashAdj = $totalCash - $ajQrisCum;

        // ====== BULAN TERPILIH (Chart + Ringkasan) ======
        $selectedMonth = $r->query('m');
        $monthDate = $selectedMonth ? (Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()) : today()->startOfMonth();

        $nowMonth = today()->startOfMonth();
        $monthOptions = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $nowMonth->copy()->subMonthsNoOverflow($i);
            $monthOptions[] = ['value' => $m->format('Y-m'), 'label' => $m->translatedFormat('M Y')];
        }

        $prevMonthValue = $monthDate->copy()->subMonthNoOverflow()->format('Y-m');
        $canNext        = $monthDate->lt($nowMonth);
        $nextMonthValue = $canNext ? $monthDate->copy()->addMonthNoOverflow()->format('Y-m') : null;

        $monthStart = $monthDate->copy()->startOfDay();
        $monthEnd   = $monthDate->copy()->endOfMonth()->endOfDay();
        $monthLabel = $monthDate->translatedFormat('F Y');
        $selectedMonthValue = $monthDate->format('Y-m');

        // Omzet per tanggal (EXCLUDE layanan fee/AJ)
        $raw = Rekap::whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereNotNull('service_id')
            ->whereHas('service', $svcNotFee)
            ->selectRaw('DATE(created_at) as tgl, SUM(total) as omzet')
            ->groupBy('tgl')
            ->pluck('omzet', 'tgl');

        $period = CarbonPeriod::create($monthStart, '1 day', $monthEnd);
        $chartLabels = [];
        $chartData = [];
        foreach ($period as $d) {
            $key = $d->format('Y-m-d');
            $chartLabels[] = $key;
            $chartData[]   = (int)($raw[$key] ?? 0);
        }
        $omzetBulanIniGross = array_sum($chartData); // sudah exclude AJ

        // Pengeluaran (agregat) – exclude owner draw, fee ongkir, dan gaji
        $ownerDrawWords = ['bos', 'kanjeng', 'ambil duit', 'ambil duid', 'tarik kas'];
        $feeOngkirWords = ['ongkir', 'anter jemput', 'antar jemput'];
        $gajiWords = ['gaji'];
        
        $pengeluaranBulanIni = Rekap::whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereNull('service_id')
            ->where(function ($q) use ($ownerDrawWords, $feeOngkirWords, $gajiWords) {
                // Exclude owner draw
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

        // ====== FEE BULAN TERPILIH (kategori lengkap) ======
        $rowsMonth = Rekap::with('service')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereNotNull('service_id')
            ->get();

        $kgLipatMonth = 0; $feeSetrikaMonth = 0; $feeBedCoverMonth = 0; $feeHordengMonth = 0; $feeBonekaMonth = 0; $feeSatuanMonth = 0;

        foreach ($rowsMonth as $row) {
            $qty  = (int)($row->qty ?? 0);
            if ($qty <= 0) continue;
            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name, 'lipat') && str_contains($name, '/kg'))              { $kgLipatMonth += $qty; continue; }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) { $kgLipatMonth += 7 * $qty; continue; }

            if (str_contains($name, 'cuci setrika express 3kg')) { $feeSetrikaMonth += 3000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 5kg')) { $feeSetrikaMonth += 5000 * $qty; continue; }
            if (str_contains($name, 'cuci setrika express 7kg')) { $feeSetrikaMonth += 7000 * $qty; continue; }

            if (str_contains($name, 'bed cover')) { $feeBedCoverMonth += 3000 * $qty; continue; }
            if (str_contains($name, 'hordeng'))   { $feeHordengMonth  += 3000 * $qty; continue; }
            if (str_contains($name, 'boneka'))    { $feeBonekaMonth   += 1000 * $qty; continue; }
            if (str_contains($name, 'satuan'))    { $feeSatuanMonth   += 1000 * $qty; continue; }

            if (str_contains($name, 'setrika'))   { $feeSetrikaMonth  += 1000 * $qty; continue; }
        }

        $feeLipatMonth     = intdiv($kgLipatMonth, 7) * 3000;
        $totalFeeBulanIni  = $feeLipatMonth + $feeSetrikaMonth + $feeBedCoverMonth + $feeHordengMonth + $feeBonekaMonth + $feeSatuanMonth;

        $pendapatanBersihBulanIni = max(0, $omzetBulanIniGross - $pengeluaranBulanIni - $totalFeeBulanIni);

        // === Toggle tabel pengeluaran via tombol ===
        $showExpenses = $r->boolean('show_exp');
        $pengeluaranBulanDetail = null;
        if ($showExpenses) {
            $pengeluaranBulanDetail = Rekap::with('metode')
                ->whereNull('service_id')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->orderBy('created_at', 'desc')
                ->paginate(12, ['*'], 'pengeluaran_bulanan');
            $pengeluaranBulanDetail->appends(['m' => $selectedMonthValue, 'show_exp' => 1]);
        }

        return view('admin.dashboard', compact(
            // Hari ini
            'day',
            'totalPesananHariIni',
            'pendapatanHariIni',
            'pendapatanBersihHariIni',
            'feeTotalHariIni',
            'pengeluaranHariIni',
            'pesananDiproses',
            'pesananSelesai',
            'riwayat',
            'totalCashAdj',

            // Bulan terpilih
            'monthLabel',
            'selectedMonthValue',
            'monthOptions',
            'prevMonthValue',
            'nextMonthValue',
            'canNext',
            'chartLabels',
            'chartData',
            'omzetBulanIniGross',
            'pengeluaranBulanIni',
            'totalFeeBulanIni',
            'pendapatanBersihBulanIni',

            // Toggle tabel
            'showExpenses',
            'pengeluaranBulanDetail'
        ));
    }

    // ==== Helper: total KG lipat kumulatif s.d. $until (EXCLUDE bed cover) ====
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
            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) { $total += $qty; continue; }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) { $total += 7 * $qty; continue; }
            // bed cover tidak ikut lipat
        }
        return $total;
    }
}
