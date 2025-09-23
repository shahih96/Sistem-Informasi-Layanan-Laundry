<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SaldoBon;

class SaldoBonController extends Controller
{
    public function index(){ $data = SaldoBon::with(['bon.service','saldoKartu'])->latest()->paginate(10); return view('admin.saldo-bon.index',compact('data')); }
    public function show(SaldoBon $saldo_bon){ $saldo_bon->load(['bon.service','saldoKartu']); return view('admin.saldo-bon.show',compact('saldo_bon')); }
}

// SaldoKartuController.php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\SaldoKartu;
use Illuminate\Http\Request;

class SaldoKartuController extends Controller
{
    public function index(){ $data = SaldoKartu::latest()->paginate(10); return view('admin.saldo-kartu.index',compact('data')); }
    public function store(Request $r){
        $data = $r->validate([
            'saldo_awal'=>'required|integer|min:0',
            'saldo_baru'=>'required|integer|min:0',
            'tap_hari_ini'=>'required|integer|min:0',
            'tap_gagal'=>'required|integer|min:0',
        ]);
        SaldoKartu::create($data);
        return back()->with('ok','Saldo kartu dicatat.');
    }
    public function update(Request $r, SaldoKartu $saldo_kartu){
        $data = $r->validate([
            'saldo_awal'=>'required|integer|min:0',
            'saldo_baru'=>'required|integer|min:0',
            'tap_hari_ini'=>'required|integer|min:0',
            'tap_gagal'=>'required|integer|min:0',
        ]);
        $saldo_kartu->update($data);
        return back()->with('ok','Saldo kartu diupdate.');
    }
    public function destroy(SaldoKartu $saldo_kartu){ $saldo_kartu->delete(); return back()->with('ok','Hapus sukses.'); }
}
