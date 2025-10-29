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
        // === Tanggal konteks (opsional ?d=YYYY-MM-DD) ===
        $day   = $r->query('d') ? Carbon::parse($r->query('d')) : today();
        $start = $day->copy()->startOfDay();
        $end   = $day->copy()->endOfDay();

        // ================= PESANAN (HARI INI) =================
        $totalPesananHariIni = PesananLaundry::whereBetween('created_at', [$start, $end])->count();

        // Pendapatan hari ini
        $pendapatanHariIni = Rekap::whereNotNull('service_id')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');  
        
        $rowsToday = Rekap::with('service')
        ->whereNotNull('service_id')
        ->whereBetween('created_at', [$start, $end])
        ->get();

        $kgLipat = 0; $feeSetrika = 0;
        foreach ($rowsToday as $row) {
            $qty  = (int)($row->qty ?? 0); if ($qty <= 0) continue;
            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name,'lipat') && str_contains($name,'/kg')) { $kgLipat += $qty; continue; }
            if (str_contains($name,'cuci lipat express') && str_contains($name,'7kg')) { $kgLipat += 7 * $qty; continue; }
            if (str_contains($name,'bed cover')) { $kgLipat += 7 * $qty; continue; }

            if (str_contains($name,'cuci setrika express 3kg')) { $feeSetrika += 3000 * $qty; continue; }
            if (str_contains($name,'cuci setrika express 5kg')) { $feeSetrika += 5000 * $qty; continue; }
            if (str_contains($name,'cuci setrika express 7kg')) { $feeSetrika += 7000 * $qty; continue; }
            if (str_contains($name,'setrika')) { $feeSetrika += 1000 * $qty; }
        }
        $feeLipatHariIni = intdiv($kgLipat, 7) * 3000;
        $feeTotalHariIni = $feeLipatHariIni + $feeSetrika;

        $pendapatanBersihHariIni = max(0, $pendapatanHariIni - $feeTotalHariIni);


        // Status TERBARU yg terjadi HARI INI
        $latestToday = PesananLaundry::whereHas('statuses', fn($q) =>
                $q->whereBetween('created_at', [$start, $end])
            )
            ->with(['statuses' => fn($q) => $q->whereBetween('created_at', [$start, $end])->latest()->limit(1)])
            ->get();

        $pesananDiproses = $latestToday->filter(fn($p) =>
            strcasecmp(optional($p->statuses->first())->keterangan, 'Diproses') === 0
        )->count();

        $pesananSelesai = $latestToday->filter(fn($p) =>
            strcasecmp(optional($p->statuses->first())->keterangan, 'Selesai') === 0
        )->count();

        // Riwayat terbaru (bebas tanggal)
        $riwayat = PesananLaundry::with(['service','statuses' => fn($q)=>$q->latest()])
            ->latest()->take(5)->get();

        // ================= TOTAL CASH (AKUMULASI s.d. $end) =================
        $idTunai = MetodePembayaran::where('nama','tunai')->value('id');

        $cashMasukTunaiCum = Rekap::whereNotNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        $cashKeluarTunaiCum = Rekap::whereNull('service_id')
            ->where('metode_pembayaran_id', $idTunai)
            ->where('created_at', '<=', $end)
            ->sum('total');

        // 🔧 Perubahan: gunakan harga_satuan jika tersedia
        $extraCashFromBonLunasTunaiCum = PesananLaundry::query()
            ->leftJoin('rekap', 'rekap.pesanan_laundry_id', '=', 'pesanan_laundry.id')
            ->join('services', 'services.id', '=', 'pesanan_laundry.service_id')
            ->where('pesanan_laundry.metode_pembayaran_id', $idTunai)
            ->whereNotNull('pesanan_laundry.paid_at')
            ->where('pesanan_laundry.paid_at', '<=', $end)
            ->where(function($q) use ($idTunai) {
                $q->whereNull('rekap.id')
                ->orWhere('rekap.metode_pembayaran_id', '<>', $idTunai);
            })
            ->sum(DB::raw('GREATEST(1, IFNULL(pesanan_laundry.qty,1)) * COALESCE(pesanan_laundry.harga_satuan, services.harga_service)'));

        // Fee kumulatif s.d. $end
        $rowsToEnd = Rekap::with('service')
            ->whereNotNull('service_id')
            ->where('created_at', '<=', $end)
            ->get();

        $kgLipatTotalCum = 0; $feeSetrikaCum = 0;
        foreach ($rowsToEnd as $row) {
            $qty  = (int)($row->qty ?? 0);
            if ($qty <= 0) continue;
            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name,'lipat') && str_contains($name,'/kg')) { $kgLipatTotalCum += $qty; continue; }
            if (str_contains($name,'cuci lipat express') && str_contains($name,'7kg')) { $kgLipatTotalCum += 7 * $qty; continue; }
            if (str_contains($name,'bed cover')) { $kgLipatTotalCum += 7 * $qty; continue; }

            if (str_contains($name,'cuci setrika express 3kg')) { $feeSetrikaCum += 3000 * $qty; continue; }
            if (str_contains($name,'cuci setrika express 5kg')) { $feeSetrikaCum += 5000 * $qty; continue; }
            if (str_contains($name,'cuci setrika express 7kg')) { $feeSetrikaCum += 7000 * $qty; continue; }
            if (str_contains($name,'setrika')) { $feeSetrikaCum += $qty * 1000; }
        }

        $feeLipatCum = intdiv($kgLipatTotalCum, 7) * 3000;
        $totalFeeCum = $feeLipatCum + $feeSetrikaCum;

        // totalCash = kas masuk - keluar - fee
        $totalCash = $cashMasukTunaiCum + $extraCashFromBonLunasTunaiCum - $cashKeluarTunaiCum - $totalFeeCum;

        // ================= RINGKASAN BULAN BERJALAN =================
        $monthStart = now()->startOfMonth()->startOfDay();
        $nowEnd     = now()->endOfDay();

        // Omzet per tanggal → seri lengkap (isi 0 bila kosong)
        $raw = Rekap::whereBetween('created_at', [$monthStart, $nowEnd])
            ->whereNotNull('service_id')
            ->selectRaw('DATE(created_at) as tgl, SUM(total) as omzet')
            ->groupBy('tgl')->pluck('omzet', 'tgl');

        $period      = CarbonPeriod::create($monthStart, '1 day', $nowEnd);
        $chartLabels = []; $chartData = [];
        foreach ($period as $d) {
            $key = $d->format('Y-m-d');
            $chartLabels[] = $key;
            $chartData[]   = (int)($raw[$key] ?? 0);
        }
        $omzetBulanIniGross = array_sum($chartData);

        // ---- PENGELUARAN bulan ini (EXCLUDE "owner draw") ----
        $ownerDrawWords = ['bos','kanjeng','ambil duit','ambil duid','tarik kas','tarik'];
        $pengeluaranBulanIni = Rekap::whereBetween('created_at', [$monthStart, $nowEnd])
            ->whereNull('service_id')
            ->where(function ($q) use ($ownerDrawWords) {
                foreach ($ownerDrawWords as $w) {
                    $q->whereRaw('LOWER(COALESCE(keterangan,"")) NOT LIKE ?', ['%'.strtolower($w).'%']);
                }
            })
            ->sum('total');

        // Fee bulan ini
        $rowsMonth = Rekap::with('service')
            ->whereBetween('created_at', [$monthStart, $nowEnd])
            ->whereNotNull('service_id')
            ->get();

        $kgLipatMonth = 0; $feeSetrikaMonth = 0;
        foreach ($rowsMonth as $row) {
            $qty  = (int)($row->qty ?? 0);
            if ($qty <= 0) continue;
            $name = strtolower($row->service->nama_service ?? '');

            if (str_contains($name,'lipat') && str_contains($name,'/kg')) { $kgLipatMonth += $qty; continue; }
            if (str_contains($name,'cuci lipat express') && str_contains($name,'7kg')) { $kgLipatMonth += 7 * $qty; continue; }
            if (str_contains($name,'bed cover')) { $kgLipatMonth += 7 * $qty; continue; }

            if (str_contains($name,'cuci setrika express 3kg')) { $feeSetrikaMonth += 3000 * $qty; continue; }
            if (str_contains($name,'cuci setrika express 5kg')) { $feeSetrikaMonth += 5000 * $qty; continue; }
            if (str_contains($name,'cuci setrika express 7kg')) { $feeSetrikaMonth += 7000 * $qty; continue; }
            if (str_contains($name,'setrika')) { $feeSetrikaMonth += $qty * 1000; }
        }
        $feeLipatMonth     = intdiv($kgLipatMonth, 7) * 3000;
        $totalFeeBulanIni  = $feeLipatMonth + $feeSetrikaMonth;

        $pendapatanBersihBulanIni = max(0, $omzetBulanIniGross - $pengeluaranBulanIni - $totalFeeBulanIni);

        return view('admin.dashboard', compact(
            'day',
            'totalPesananHariIni',
            'pendapatanHariIni',
            'pendapatanBersihHariIni',
            'pesananDiproses',
            'pesananSelesai',
            'riwayat',
            'totalCash',
            'pengeluaranBulanIni',
            'pendapatanBersihBulanIni',
            'omzetBulanIniGross',
            'totalFeeBulanIni',
            'chartLabels',
            'chartData'
        ));
    }
}