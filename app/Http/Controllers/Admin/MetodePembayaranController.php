<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetodePembayaran;
use Illuminate\Http\Request;

class MetodePembayaranController extends Controller
{
    public function index()
    {
        $metode = MetodePembayaran::orderBy('nama')->get();
        return view('admin.metode.index', compact('metode'));
    }

    public function store(Request $r)
    {
        $data = $r->validate(['nama'=>'required|in:tunai,qris']);
        MetodePembayaran::firstOrCreate($data);
        return back()->with('ok','Metode tersimpan.');
    }

    public function update(Request $r, MetodePembayaran $metode)
    {
        $data = $r->validate(['nama'=>'required|in:tunai,qris']);
        $metode->update($data);
        return back()->with('ok','Metode diupdate.');
    }

    public function destroy(MetodePembayaran $metode)
    {
        $metode->delete();
        return back()->with('ok','Metode dihapus.');
    }
}
