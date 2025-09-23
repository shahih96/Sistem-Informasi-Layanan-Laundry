<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StatusPesanan;
use Illuminate\Http\Request;

class StatusPesananController extends Controller
{
    public function store(Request $r)
    {
        $data = $r->validate([
            'pesanan_id'  => 'required|exists:pesanan_laundry,id',
            'keterangan'  => 'required|string|max:100',
        ]);
        StatusPesanan::create($data);
        return back()->with('ok','Status ditambahkan.');
    }

    public function update(Request $r, StatusPesanan $status)
    {
        $data = $r->validate(['keterangan' => 'required|string|max:100']);
        $status->update($data);
        return back()->with('ok','Status diupdate.');
    }

    public function destroy(StatusPesanan $status)
    {
        $status->delete();
        return back()->with('ok','Status dihapus.');
    }
}
