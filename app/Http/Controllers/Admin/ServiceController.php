<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('nama_service')->paginate(20);
        $adminPassword = 'ngubahdoang'; // Password untuk proteksi edit/hapus layanan
        return view('admin.services.index', compact('services', 'adminPassword'));
    }

    public function store(Request $request)
    {
        $namaInput  = trim($request->input('nama_service'));
        $hargaInput = $request->input('harga_service', $request->input('harga'));
        $harga      = (int) preg_replace('/[^\d]/', '', (string) $hargaInput);

        $request->validate([
            'nama_service'  => 'required|string|max:150',
            'harga_service' => 'required',
        ]);

        if ($harga <= 0) {
            return back()->withInput()->with('error', 'Harga tidak valid.');
        }

        $exists = Service::whereRaw('LOWER(nama_service) = ?', [mb_strtolower($namaInput)])->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Nama layanan sudah ada, gunakan nama lain.');
        }

        try {
            DB::beginTransaction();

            Service::create([
                'nama_service'  => $namaInput,
                'harga_service' => $harga,
            ]);

            DB::commit();
            return back()->with('success', 'Layanan berhasil ditambahkan.');
        } catch (QueryException $e) {
            DB::rollBack();

            if ($e->getCode() === '23000') {
                return back()->withInput()->with('error', 'Nama layanan sudah terdaftar.');
            }

            return back()->withInput()->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    public function update(Request $request, Service $service)
    {
        $namaInput  = trim($request->input('nama_service'));
        $hargaInput = $request->input('harga_service', $request->input('harga'));
        $harga      = (int) preg_replace('/[^\d]/', '', (string) $hargaInput);

        $request->validate([
            'nama_service'  => 'required|string|max:150',
            'harga_service' => 'required',
        ]);

        if ($harga <= 0) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Harga tidak valid.'], 422);
            }
            return back()->withInput()->with('error', 'Harga tidak valid.');
        }

        $exists = Service::whereRaw('LOWER(nama_service) = ?', [mb_strtolower($namaInput)])
            ->where('id', '!=', $service->id)
            ->exists();
        if ($exists) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Nama layanan sudah ada, gunakan nama lain.'], 422);
            }
            return back()->with('error', 'Nama layanan sudah ada, gunakan nama lain.');
        }

        try {
            DB::beginTransaction();

            $service->update([
                'nama_service'  => $namaInput,
                'harga_service' => $harga,
            ]);

            DB::commit();
            
            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Layanan berhasil diperbarui.']);
            }
            return back()->with('success', 'Layanan berhasil diperbarui.');
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() === '23000') {
                if (request()->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Nama layanan sudah terdaftar.'], 422);
                }
                return back()->with('error', 'Nama layanan sudah terdaftar.');
            }
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Terjadi kesalahan. Silakan coba lagi.'], 422);
            }
            return back()->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    public function destroy(Service $service)
    {
        $service->delete();
        
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Layanan berhasil dihapus.']);
        }
        return back()->with('ok', 'Layanan dihapus.');
    }
}