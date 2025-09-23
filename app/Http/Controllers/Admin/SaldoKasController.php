<?php 

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SaldoKas;
use Illuminate\Http\Request;

class SaldoKasController extends Controller
{
    public function index(){ $saldo = SaldoKas::first(); return view('admin.saldo-kas.index', compact('saldo')); }
    public function update(Request $r, SaldoKas $saldo_ka){
        $data = $r->validate(['saldo_kas'=>'required|integer']);
        $saldo_ka->update($data);
        return back()->with('ok','Saldo kas diupdate.');
    }
}