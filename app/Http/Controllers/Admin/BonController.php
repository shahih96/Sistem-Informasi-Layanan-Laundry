<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Bon;
use App\Models\Service;
use Illuminate\Http\Request;

class BonController extends Controller
{
    public function index(){
        $bon = Bon::with('service','saldoBon')->latest()->paginate(10);
        $services = Service::orderBy('nama_service')->get();
        return view('admin.bon.index', compact('bon','services'));
    }
    public function store(Request $r){
        $data = $r->validate([
            'service_id'=>'required|exists:services,id',
            'nama_pel'=>'required|string|max:100',
            'status_bon'=>'required|in:dibuat,dibayar,batal'
        ]);
        Bon::create($data);
        return back()->with('ok','Bon dibuat.');
    }
    public function update(Request $r, Bon $bon){
        $data = $r->validate([
            'service_id'=>'required|exists:services,id',
            'nama_pel'=>'required|string|max:100',
            'status_bon'=>'required|in:dibuat,dibayar,batal'
        ]);
        $bon->update($data);
        return back()->with('ok','Bon diupdate.');
    }
    public function destroy(Bon $bon){ $bon->delete(); return back()->with('ok','Bon dihapus.'); }
}