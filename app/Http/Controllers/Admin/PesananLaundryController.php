<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesananLaundry;
use App\Models\Service;
use App\Models\MetodePembayaran;
use App\Models\Rekap;
use App\Models\BonMigrasiSetup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PesananLaundryController extends Controller
{
    public function index()
    {
        $pesanan = PesananLaundry::with([
            'service',
            'antarJemputService',
            'metode',
            'statuses' => fn($q) => $q->latest(),
        ])
        ->get()
        ->sort(function($a, $b) {
            // Ambil status terakhir
            $statusA = optional($a->statuses->first())->keterangan ?? 'Diproses';
            $statusB = optional($b->statuses->first())->keterangan ?? 'Diproses';
            
            // Tentukan prioritas untuk setiap pesanan
            // Prioritas 1 (Paling Atas): Diproses & tidak hidden
            // Prioritas 2: Selesai & tidak hidden
            // Prioritas 3: Diproses & hidden
            // Prioritas 4 (Paling Bawah): Selesai & hidden
            
            $getPriority = function($p, $status) {
                if (!$p->is_hidden) {
                    return (strcasecmp($status, 'Diproses') === 0) ? 1 : 2;
                } else {
                    return (strcasecmp($status, 'Diproses') === 0) ? 3 : 4;
                }
            };
            
            $priorityA = $getPriority($a, $statusA);
            $priorityB = $getPriority($b, $statusB);
            
            // Jika prioritas berbeda, urutkan berdasarkan prioritas
            if ($priorityA !== $priorityB) {
                return $priorityA - $priorityB;
            }
            
            // Jika prioritas sama, urutkan berdasarkan tanggal update terakhir (terbaru ke terlama)
            $dateA = optional($a->statuses->first())->created_at ?? $a->created_at;
            $dateB = optional($b->statuses->first())->created_at ?? $b->created_at;
            
            return $dateB <=> $dateA; // Descending (terbaru dulu)
        })
        ->values(); // Reset keys setelah sort

        // Paginate manual
        $perPage = 15;
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        
        $paginatedPesanan = new \Illuminate\Pagination\LengthAwarePaginator(
            $pesanan->slice($offset, $perPage),
            $pesanan->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $services = Service::orderBy('nama_service')->get();
        $metodes  = MetodePembayaran::orderBy('id')->get();
        $pelangganOptions = PesananLaundry::select('nama_pel', 'no_hp_pel')
            ->groupBy('nama_pel', 'no_hp_pel')
            ->orderBy('nama_pel')
            ->limit(500)
            ->get();

        return view('admin.pesanan.index', compact('paginatedPesanan', 'services', 'metodes', 'pelangganOptions'))
            ->with('pesanan', $paginatedPesanan);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nama_pel'             => 'required|string|max:100',
            'no_hp_pel'            => ['required', 'string', 'min:10', 'max:14', 'regex:/^[0-9]+$/'],
            'services'             => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.qty'       => 'required|integer|min:1',
            'metode_pembayaran_id' => 'required|exists:metode_pembayaran,id',
            'antar_jemput_service_id' => 'nullable|exists:services,id',
            'update_customer_data' => 'nullable|string',
        ], [
            'no_hp_pel.required' => 'Nomor HP wajib diisi',
            'no_hp_pel.string'   => 'Nomor HP harus berupa teks',
            'no_hp_pel.min'      => 'Nomor HP minimal 10 digit',
            'no_hp_pel.max'      => 'Nomor HP maksimal 14 digit',
            'no_hp_pel.regex'    => 'Nomor HP hanya boleh berisi angka (0-9)',
            'services.required'  => 'Pilih minimal 1 layanan',
            'services.*.service_id.required' => 'Layanan harus dipilih',
            'services.*.qty.required' => 'Kuantitas harus diisi',
        ]);

        DB::transaction(function () use ($data, $r) {
            // Jika ada flag update_customer_data, update semua pesanan lama dengan nama sama
            if ($r->has('update_customer_data') && $r->input('update_customer_data') == '1') {
                // Normalisasi nama untuk pencarian (case-insensitive, trim spaces)
                $normalizedNama = trim(strtolower($data['nama_pel']));
                
                // Update semua pesanan lama dengan nama yang sama (case-insensitive)
                PesananLaundry::whereRaw('LOWER(TRIM(nama_pel)) = ?', [$normalizedNama])
                    ->update([
                        'nama_pel' => $data['nama_pel'], // Update dengan kapitalisasi baru
                        'no_hp_pel' => $data['no_hp_pel'], // Update dengan nomor HP baru
                    ]);
            }
            
            // Generate unique group_id untuk pesanan batch ini
            $groupId = uniqid('ORD-', true);
            
            // Gabungkan layanan yang sama (aggregate by service_id)
            $aggregatedServices = [];
            foreach ($data['services'] as $serviceData) {
                $serviceId = $serviceData['service_id'];
                $qty = (int) $serviceData['qty'];
                
                if (isset($aggregatedServices[$serviceId])) {
                    // Jika layanan sudah ada, tambahkan qty-nya
                    $aggregatedServices[$serviceId]['qty'] += $qty;
                } else {
                    // Jika layanan belum ada, buat entry baru
                    $aggregatedServices[$serviceId] = [
                        'service_id' => $serviceId,
                        'qty' => $qty
                    ];
                }
            }
            
            // Loop untuk setiap layanan yang sudah digabungkan
            foreach ($aggregatedServices as $serviceData) {
                $hargaSekarang = (int) Service::whereKey($serviceData['service_id'])->value('harga_service');

                $pesanan = PesananLaundry::create([
                    'group_id'             => $groupId,
                    'service_id'           => $serviceData['service_id'],
                    'antar_jemput_service_id' => $data['antar_jemput_service_id'] ?? null,
                    'nama_pel'             => $data['nama_pel'],
                    'no_hp_pel'            => $data['no_hp_pel'],
                    'qty'                  => $serviceData['qty'],
                    'admin_id'             => Auth::id(),
                    'metode_pembayaran_id' => $data['metode_pembayaran_id'],
                    'harga_satuan'         => $hargaSekarang,
                ]);

                // Status otomatis "Diproses"
                $pesanan->statuses()->create([
                    'keterangan' => 'Diproses',
                ]);

                // Buat rekap untuk layanan utama
                Rekap::create([
                    'pesanan_laundry_id'   => $pesanan->id,
                    'service_id'           => $pesanan->service_id,
                    'metode_pembayaran_id' => $pesanan->metode_pembayaran_id,
                    'qty'                  => $pesanan->qty,
                    'harga_satuan'         => $pesanan->harga_satuan,
                    'subtotal'             => $pesanan->harga_satuan,
                    'total'                => $pesanan->harga_satuan * $pesanan->qty,
                    'keterangan'           => 'Omset dari pesanan',
                ]);
            }
            
            // Buat rekap untuk antar jemput jika ada (hanya sekali)
            if (!empty($data['antar_jemput_service_id']) && count($aggregatedServices) > 0) {
                $firstPesanan = PesananLaundry::where('group_id', $groupId)->first();
                if ($firstPesanan) {
                    $hargaAntarJemput = (int) Service::whereKey($data['antar_jemput_service_id'])->value('harga_service');
                    
                    Rekap::create([
                        'pesanan_laundry_id'   => $firstPesanan->id,
                        'service_id'           => $data['antar_jemput_service_id'],
                        'metode_pembayaran_id' => $firstPesanan->metode_pembayaran_id,
                        'qty'                  => 1,
                        'harga_satuan'         => $hargaAntarJemput,
                        'subtotal'             => $hargaAntarJemput,
                        'total'                => $hargaAntarJemput,
                        'keterangan'           => 'Antar Jemput',
                    ]);
                }
            }
        });

        return back()->with('ok', 'Pesanan & rekap berhasil dibuat.');
    }

    public function update(Request $r, PesananLaundry $pesanan)
    {
        $data = $r->validate([
            'nama_pel'             => 'required|string|max:100',
            'no_hp_pel'            => 'required|string|max:20',
            'service_id'           => 'required|exists:services,id',
            'qty'                  => 'required|integer|min:1',
            'metode_pembayaran_id' => 'required|exists:metode_pembayaran,id',
        ]);

        $pesanan->update([
            'nama_pel'             => $data['nama_pel'],
            'no_hp_pel'            => $data['no_hp_pel'],
            'service_id'           => $data['service_id'],
            'qty'                  => (int) $data['qty'],
            'metode_pembayaran_id' => $data['metode_pembayaran_id'],
        ]);

        return back()->with('ok', 'Pesanan berhasil diperbarui.');
    }

    public function destroy(PesananLaundry $pesanan)
    {
        $pesanan->update(['is_hidden' => true]);
        return back()->with('ok', 'Pesanan disembunyikan dari halaman tracking.');
    }

    /**
     * Migrasi bon lama → buat pesanan BON tanpa membuat rekap.
     * Form: nama_pelanggan, service_id, qty (sederhana).
     * - Disimpan ke kolom: nama_pel, no_hp_pel (diisi '-' agar tidak NULL), dst.
     * - Ditolak kalau migrasi sudah dikunci.
     */
    public function storeMigrasiBon(Request $r)
    {
        // stop jika sudah dikunci
        $flag = BonMigrasiSetup::latest('id')->first();
        if ($flag && $flag->locked) {
            return back()->withErrors(['migrasi' => 'Migrasi bon sudah dikunci. Form ini tidak bisa dipakai lagi.']);
        }

        $data = $r->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'service_id'     => [
                'required',
                Rule::exists('services', 'id')->where(fn($q) => $q->where('is_fee_service', 0))
            ],
            'qty'            => ['required', 'integer', 'min:1', 'max:9999'],
            'antar_jemput_service_id' => ['nullable', 'exists:services,id'],
        ]);

        $svc = Service::findOrFail($data['service_id']);
        $hargaSatuan = (int) $svc->harga_service; // versi simpel (tanpa harga_custom)

        $idBon = MetodePembayaran::whereRaw('LOWER(nama)=?', ['bon'])->value('id');
        if (!$idBon) {
            return back()->withErrors(['migrasi' => 'Metode BON belum dikonfigurasi. Tambahkan metode "bon" dulu di master Metode Pembayaran.']);
        }

        $createdAt = now();

        DB::transaction(function () use ($data, $idBon, $hargaSatuan, $createdAt) {
            $p = PesananLaundry::create([
                'service_id'           => $data['service_id'],
                'antar_jemput_service_id' => $data['antar_jemput_service_id'] ?? null,
                'nama_pel'             => $data['nama_pelanggan'],
                'no_hp_pel'            => '-',                 // ⬅️ anti-NULL supaya lolos constraint
                'qty'                  => (int)$data['qty'],
                'admin_id'             => Auth::id(),
                'metode_pembayaran_id' => $idBon,              // selalu BON saat migrasi
                'harga_satuan'         => $hargaSatuan,        // kunci harga
                'paid_at'              => null,                // masih piutang
                'created_at'           => $createdAt,
                'updated_at'           => $createdAt,
            ]);

            // status penanda
            $p->statuses()->create([
                'keterangan' => 'BON (Migrasi)',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        });

        return back()->with('ok_migrasi_bon', 'Bon lama berhasil dimigrasikan sebagai pesanan BON. Saat lunas, ubah metodenya ke Tunai/QRIS.');
    }

    /**
     * Kunci migrasi bon -> setelah ini form hilang & submit ditolak.
     */
    public function lockMigrasiBon(Request $r)
    {
        $row = BonMigrasiSetup::latest('id')->first();
        if (!$row) {
            BonMigrasiSetup::create(['locked' => true]);
        } else if (!$row->locked) {
            $row->update(['locked' => true]);
        }
        return back()->with('ok_migrasi_bon', 'Migrasi bon dikunci. Form disembunyikan.');
    }
}
