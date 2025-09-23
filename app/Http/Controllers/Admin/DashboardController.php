<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananLaundry;
use App\Models\Rekap;
use App\Models\SaldoKas;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $totalPesananHariIni = PesananLaundry::whereDate('created_at', $today)->count();

        $pendapatanHariIni = Rekap::whereDate('created_at', $today)->sum('total');

        $pesananDiproses = PesananLaundry::whereHas('statuses', function($q){
            $q->latest();
        })->with(['statuses' => fn($q) => $q->latest()])->get()
          ->filter(fn($p) => optional($p->statuses->first())->keterangan === 'Diproses')
          ->count();

        $pesananSelesai = PesananLaundry::whereHas('statuses', function($q){
            $q->latest();
        })->with(['statuses' => fn($q) => $q->latest()])->get()
          ->filter(fn($p) => optional($p->statuses->first())->keterangan === 'Selesai')
          ->count();

        $riwayat = PesananLaundry::with(['service','statuses' => fn($q)=>$q->latest()])
                    ->latest()->take(10)->get();

        $saldoKas = optional(SaldoKas::first())->saldo_kas ?? 0;

        // chart: omzet bulan berjalan (sum total per hari)
        $startMonth = now()->startOfMonth();
        $omzetPerHari = Rekap::whereBetween('created_at', [$startMonth, now()])
            ->selectRaw('DATE(created_at) as tgl, SUM(total) as omzet')
            ->groupBy('tgl')->orderBy('tgl')->get();

        return view('admin.dashboard', compact(
            'totalPesananHariIni','pendapatanHariIni',
            'pesananDiproses','pesananSelesai','riwayat',
            'saldoKas','omzetPerHari'
        ));
    }
}
