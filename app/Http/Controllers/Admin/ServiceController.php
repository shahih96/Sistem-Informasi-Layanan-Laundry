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
        return view('admin.services.index', compact('services'));
    }

    public function store(Request $request)
    {
        // Normalisasi input
        $nama = trim($request->input('nama_service'));
        $harga = (int) preg_replace('/\D+/', '', (string) $request->input('harga_service'));

        // Validasi: unik nama_service (case-insensitive praktis di app level)
        $request->validate([
            'nama_service'  => 'required|string|max:150',
            'harga_service' => 'required',
        ]);

        // Cek manual (case-insensitive)
        $exists = Service::whereRaw('LOWER(nama_service) = ?', [mb_strtolower($nama)])->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Nama layanan sudah ada, gunakan nama lain.');
        }

        try {
            DB::beginTransaction();

            Service::create([
                'nama_service'  => $nama,
                'harga_service' => $harga,
            ]);

            DB::commit();
            return back()->with('success', 'Layanan berhasil ditambahkan.');
        } catch (QueryException $e) {
            DB::rollBack();

            // 23000 = integrity constraint violation (mis. unique index)
            if ($e->getCode() === '23000') {
                return back()->withInput()->with('error', 'Nama layanan sudah terdaftar.');
            }

            return back()->withInput()->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    public function update(Request $request, Service $service)
    {
        $nama  = trim($request->input('nama_service'));
        $harga = (int) preg_replace('/\D+/', '', (string) $request->input('harga_service'));

        $request->validate([
            'nama_service'  => 'required|string|max:150',
            'harga_service' => 'required',
        ]);

        // Cek manual unik selain dirinya sendiri (case-insensitive)
        $exists = Service::whereRaw('LOWER(nama_service) = ?', [mb_strtolower($nama)])
            ->where('id', '!=', $service->id)
            ->exists();
        if ($exists) {
            return back()->with('error', 'Nama layanan sudah ada, gunakan nama lain.');
        }

        try {
            DB::beginTransaction();

            $service->update([
                'nama_service'  => $nama,
                'harga_service' => $harga,
            ]);

            DB::commit();
            return back()->with('success', 'Layanan berhasil diperbarui.');
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() === '23000') {
                return back()->with('error', 'Nama layanan sudah terdaftar.');
            }
            return back()->with('error', 'Terjadi kesalahan. Silakan coba lagi.');
        }
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return back()->with('ok','Layanan dihapus.');
    }
}
