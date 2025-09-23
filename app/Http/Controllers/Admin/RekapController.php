<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rekap;
use App\Models\Service;
use App\Models\MetodePembayaran;
use App\Models\SaldoKas;
use App\Models\Fee;
use App\Models\SaldoKartu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapController extends Controller
{
    public function index()
    {
        $omset = Rekap::with(['service','metode'])
            ->whereNotNull('service_id')
            ->latest()->paginate(10, ['*'], 'omset');

        $pengeluaran = Rekap::with('metode')
            ->whereNull('service_id') // baris pengeluaran: pakai total negatif atau tanpa service
            ->latest()->paginate(10, ['*'], 'pengeluaran');

        $piutang = Rekap::with(['service','metode'])
            ->where('total','>',0)
            ->whereHas('metode', fn($q)=>$q->where('nama','tunai')->orWhere('nama','qris'))
            ->latest()->paginate(10, ['*'], 'piutang');

        $totalCash = optional(SaldoKas::first())->saldo_kas ?? 0;
        $fee = Fee::first();
        $saldoKartu = optional(SaldoKartu::latest()->first())->saldo_baru ?? 0;

        return view('admin.rekap.index', compact('omset','pengeluaran','piutang','totalCash','fee','saldoKartu'));
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

        DB::transaction(function() use ($r){
            foreach($r->rows as $row){
                Rekap::create([
                    'service_id'            => $row['service_id'] ?? null,
                    'metode_pembayaran_id'  => $row['metode_pembayaran_id'] ?? null,
                    'qty'                   => $row['qty'],
                    'subtotal'              => $row['subtotal'],
                    'total'                 => $row['total'],
                ]);
            }
        });

        return back()->with('ok','Rekap disimpan.');
    }

    public function input()
    {
        $services = Service::all();
        $metodes = MetodePembayaran::all();

        return view('admin.rekap.input', compact('services', 'metodes'));
    }

    public function storeSaldo(Request $request)
    {
        $data = $request->validate([
            'saldo_kartu' => ['required','numeric','min:0'],
            'tap_gagal'   => ['required','integer','min:0'],
        ]);

        // Simpan/update saldo kartu harian
        SaldoKartu::updateOrCreate(
            ['tanggal' => now()->toDateString()],
            ['saldo' => $data['saldo_kartu'], 'tap_gagal' => $data['tap_gagal']]
        );

        return back()->with('ok', 'Saldo kartu berhasil disimpan.');
    }
}