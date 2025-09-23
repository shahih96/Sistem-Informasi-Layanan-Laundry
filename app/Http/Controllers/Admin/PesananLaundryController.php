<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananLaundry;
use App\Models\Service;
use App\Models\StatusPesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- tambahkan ini

class PesananLaundryController extends Controller
{
    public function index()
    {
        $pesanan = PesananLaundry::with(['service','statuses'=>fn($q)=>$q->latest()])
                    ->latest()->paginate(10);
        $services = Service::orderBy('nama_service')->get();

        return view('admin.pesanan.index', compact('pesanan','services'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nama_pel'   => 'required|string|max:100',
            'no_hp_pel'  => 'required|string|max:20',
            'service_id' => 'nullable|exists:services,id',
            'status_awal'=> 'required|string|max:50'
        ]);

        $pesanan = PesananLaundry::create([
            'service_id' => $data['service_id'] ?? null,
            'nama_pel'   => $data['nama_pel'],
            'no_hp_pel'  => $data['no_hp_pel'],
            'admin_id'   => Auth::id(), // <- pakai Facade supaya Intelephense tidak protes
        ]);

        $pesanan->statuses()->create(['keterangan' => $data['status_awal']]);

        return back()->with('ok','Pesanan dibuat.');
    }

    public function update(Request $r, PesananLaundry $pesanan)
    {
        $data = $r->validate([
            'nama_pel'   => 'required|string|max:100',
            'no_hp_pel'  => 'required|string|max:20',
            'service_id' => 'nullable|exists:services,id',
        ]);

        $pesanan->update($data);
        return back()->with('ok','Pesanan diupdate.');
    }

    public function destroy(PesananLaundry $pesanan)
    {
        $pesanan->delete();
        return back()->with('ok','Pesanan dihapus.');
    }
}
