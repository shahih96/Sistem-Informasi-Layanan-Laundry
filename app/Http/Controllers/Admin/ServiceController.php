<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('nama_service')->paginate(20);
        return view('admin.services.index', compact('services'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nama_service'  => 'required|string|max:100',
            'harga_service' => 'required|integer|min:0',
        ]);
        Service::create($data);
        return back()->with('ok','Layanan ditambahkan.');
    }

    public function update(Request $r, Service $service)
    {
        $data = $r->validate([
            'nama_service'  => 'required|string|max:100',
            'harga_service' => 'required|integer|min:0',
        ]);
        $service->update($data);
        return back()->with('ok','Layanan diupdate.');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return back()->with('ok','Layanan dihapus.');
    }
}
