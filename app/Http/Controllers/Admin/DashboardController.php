<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananLaundry;
use App\Models\Rekap;
use App\Models\MetodePembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        // ====== HARI INI (cards) ======
        $day   = $r->query('d') ? Carbon::parse($r->query('d')) : today();
        $start = $day->copy()->startOfDay();
        $end   = $day->copy()->endOfDay();

        $totalPesananHariIni = PesananLaundry::whereBetween('created_at', [$start, $end])->count();

        $pendapatanHariIni = Rekap::whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        // ====== FEE HARI INI (dengan kategori lengkap) ======
        $rowsToday = Rekap::with('service')
            ->whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // Lipat dihitung per 7 Kg -> Rp 3.000 (Bed Cover TIDAK masuk Lipat)
        $kgLipat = 0;

        // Setrika: 3/5/7 Kg fixed, selebihnya "per kg" = 1.000
        $feeSetrika = 0;

        // Kategori tambahan (per item)
        $feeBedCover = 0;         // 3.000 / item
        $feeHordeng  = 0;         // 3.000 / item (baik kecil max 3 atau besar max 2)
        $feeBoneka   = 0;         // 1.000 / item (besar atau kecil)
        $feeSatuan   = 0;         // 1.000 / item

        foreach ($rowsToday as $row) {
            $qty  = (int)($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            // ---- LIPAT (kg) ----
            if (str_contains($name, 'lipat') && str_contains($name, '/kg')) {
                // Bed cover tidak dihitung ke lipat
                if (!str_contains($name, 'bed cover')) {
                    $kgLipat += $qty;
                    continue;
                }
            }

            // ---- LIPAT EXPRESS 7kg (fixed 7kg) ----
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) {
                $kgLipat += 7 * $qty;
                continue;
            }

            // ---- SETRIKA EXPRESS (fixed) ----
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

            // ---- BED COVER (per item) ----
            if (str_contains($name, 'bed cover')) {
                $feeBedCover += 3000 * $qty;
                continue;
            }

            // ---- HORDENG (per item) ----
            if (str_contains($name, 'hordeng')) {
                $feeHordeng += 3000 * $qty;
                continue;
            }

            // ---- BONEKA (per item) ----
            if (str_contains($name, 'boneka')) {
                $feeBoneka += 1000 * $qty;
                continue;
            }

            // ---- SATUAN (per item) ----
            if (str_contains($name, 'satuan')) {
                $feeSatuan += 1000 * $qty;
                continue;
            }

            // ---- SETRIKA (generic per kg) ----
            if (str_contains($name, 'setrika')) {
                $feeSetrika += 1000 * $qty;
                continue;
            }
        }

        $feeLipatHariIni = intdiv($kgLipat, 7) * 3000;
        $feeTotalHariIni = $feeLipatHariIni + $feeSetrika + $feeBedCover + $feeHordeng + $feeBoneka + $feeSatuan;
        $pendapatanBersihHariIni = max(0, $pendapatanHariIni - $feeTotalHariIni);

        // ====== STATUS TERBARU HARI INI ======
        $latestToday = PesananLaundry::whereHas(
            'statuses',
            fn($q) =>
            $q->whereBetween('created_at', [$start, $end])
        )
            ->with(['statuses' => fn($q) => $q->whereBetween('created_at', [$start, $end])->latest()->limit(1)])
            ->get();

        $pesananDiproses = $latestToday->filter(
            fn($p) =>
            strcasecmp(optional($p->statuses->first())->keterangan, 'Diproses') === 0
        )->count();

        $pesananSelesai = $latestToday->filter(
            fn($p) =>
            strcasecmp(optional($p->statuses->first())->keterangan, 'Selesai') === 0
        )->count();

        $riwayat = PesananLaundry::with(['service', 'statuses' => fn($q) => $q->latest()])
            ->latest()->take(5)->get();

        // ====== TOTAL CASH (akumulasi s.d. $end) ======
        $idTunai = MetodePembayaran::where('nama', 'tunai')->value('id');

        $cashMasukTunaiCum = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        $cashKeluarTunaiCum = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        $extraCashFromBonLunasTunaiCum = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->whereNotNull('pesanan_laundry.paid_at')
            ->where('pesanan_laundry.paid_at', '<=', $end)
            ->where(function ($q) use ($idTunai) {
                $q->whereNull('rekap.id')
                    ->orWhere('rekap.metode_pembayaran_id', '<>', $idTunai);
            })
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        // ====== FEE KUMULATIF (untuk totalCash) ======
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

            // Lipat kg (exclude bed cover)
            if (str_contains($name, 'lipat') && str_contains($name, '/kg') && !str_contains($name, 'bed cover')) {
                $kgLipatTotalCum += $qty;
                continue;
            }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) {
                $kgLipatTotalCum += 7 * $qty;
                continue;
            }

            // Setrika fixed
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

            // Bed cover (item)
            if (str_contains($name, 'bed cover')) {
                $feeBedCoverCum += 3000 * $qty;
                continue;
            }

            // Hordeng (item)
            if (str_contains($name, 'hordeng')) {
                $feeHordengCum += 3000 * $qty;
                continue;
            }

            // Boneka (item)
            if (str_contains($name, 'boneka')) {
                $feeBonekaCum += 1000 * $qty;
                continue;
            }

            // Satuan (item)
            if (str_contains($name, 'satuan')) {
                $feeSatuanCum += 1000 * $qty;
                continue;
            }

            // Setrika generic per kg
            if (str_contains($name, 'setrika')) {
                $feeSetrikaCum += 1000 * $qty;
                continue;
            }
        }

        $feeLipatCum = intdiv($kgLipatTotalCum, 7) * 3000;
        $totalFeeCum = $feeLipatCum + $feeSetrikaCum + $feeBedCoverCum + $feeHordengCum + $feeBonekaCum + $feeSatuanCum;

        $totalCash = $cashMasukTunaiCum + $extraCashFromBonLunasTunaiCum - $cashKeluarTunaiCum - $totalFeeCum;

        // ====== BULAN TERPILIH (Chart + Ringkasan + (opsional) tabel) ======
        $selectedMonth = $r->query('m');
        $monthDate = $selectedMonth
            ? (Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth())
            : today()->startOfMonth();

        // Dropdown: 12 bulan terakhir dari bulan SEKARANG (tidak mundur ke tahun lalu saat pilih bulan lampau)
        $nowMonth = today()->startOfMonth();
        $monthOptions = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $nowMonth->copy()->subMonthsNoOverflow($i);
            $monthOptions[] = [
                'value' => $m->format('Y-m'),
                'label' => $m->translatedFormat('M Y'),
            ];
        }

        $prevMonthValue = $monthDate->copy()->subMonthNoOverflow()->format('Y-m');
        $canNext        = $monthDate->lt($nowMonth);
        $nextMonthValue = $canNext ? $monthDate->copy()->addMonthNoOverflow()->format('Y-m') : null;

        $monthStart = $monthDate->copy()->startOfDay();
        $monthEnd   = $monthDate->copy()->endOfMonth()->endOfDay();
        $monthLabel = $monthDate->translatedFormat('F Y');
        $selectedMonthValue = $monthDate->format('Y-m');

        // Omzet per tanggal
        $raw = Rekap::whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereNotNull('service_id')
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
        $omzetBulanIniGross = array_sum($chartData);

        // Pengeluaran (agregat) – exclude owner draw
        $ownerDrawWords = ['bos', 'kanjeng', 'ambil duit', 'ambil duid', 'tarik kas', 'tarik'];
        $pengeluaranBulanIni = Rekap::whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereNull('service_id')
            ->where(function ($q) use ($ownerDrawWords) {
                foreach ($ownerDrawWords as $w) {
                    $q->whereRaw('LOWER(COALESCE(keterangan,"")) NOT LIKE ?', ['%' . strtolower($w) . '%']);
                }
            })
            ->sum('total');

        // ====== FEE BULAN TERPILIH (kategori lengkap) ======
        $rowsMonth = Rekap::with('service')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->whereNotNull('service_id')
            ->get();

        $kgLipatMonth   = 0;
        $feeSetrikaMonth = 0;
        $feeBedCoverMonth = 0;
        $feeHordengMonth  = 0;
        $feeBonekaMonth   = 0;
        $feeSatuanMonth   = 0;

        foreach ($rowsMonth as $row) {
            $qty  = (int)($row->qty ?? 0);
            if ($qty <= 0) continue;

            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name, 'lipat') && str_contains($name, '/kg') && !str_contains($name, 'bed cover')) {
                $kgLipatMonth += $qty;
                continue;
            }
            if (str_contains($name, 'cuci lipat express') && str_contains($name, '7kg')) {
                $kgLipatMonth += 7 * $qty;
                continue;
            }

            if (str_contains($name, 'cuci setrika express 3kg')) {
                $feeSetrikaMonth += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 5kg')) {
                $feeSetrikaMonth += 5000 * $qty;
                continue;
            }
            if (str_contains($name, 'cuci setrika express 7kg')) {
                $feeSetrikaMonth += 7000 * $qty;
                continue;
            }

            if (str_contains($name, 'bed cover')) {
                $feeBedCoverMonth += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'hordeng')) {
                $feeHordengMonth  += 3000 * $qty;
                continue;
            }
            if (str_contains($name, 'boneka')) {
                $feeBonekaMonth   += 1000 * $qty;
                continue;
            }
            if (str_contains($name, 'satuan')) {
                $feeSatuanMonth   += 1000 * $qty;
                continue;
            }

            if (str_contains($name, 'setrika')) {
                $feeSetrikaMonth  += 1000 * $qty;
                continue;
            }
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
            'pesananDiproses',
            'pesananSelesai',
            'riwayat',
            'totalCash',

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
}
