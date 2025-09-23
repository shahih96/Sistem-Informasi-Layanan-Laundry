<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function index(){ $fee = Fee::first(); return view('admin.fee.index', compact('fee')); }
    public function update(Request $r, Fee $fee){
        $data = $r->validate([
            'fee_lipat'=>'required|integer|min:0',
            'fee_setrika'=>'required|integer|min:0',
        ]);
        $fee->update($data);
        return back()->with('ok','Fee diupdate.');
    }
}