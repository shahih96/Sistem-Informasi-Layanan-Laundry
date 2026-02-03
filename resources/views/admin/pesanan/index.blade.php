@extends('admin.layout')
@section('title', 'Pesanan Laundry - Qxpress Laundry')

@section('content')
    <div class="bg-white p-5 rounded-xl shadow">

        @php
          // cek flag kunci migrasi bon
          $migrasiFlag = \App\Models\BonMigrasiSetup::latest('id')->first();
          $migrasiLocked = $migrasiFlag && $migrasiFlag->locked;
        @endphp

        {{-- ===================== MIGRASI BON LAMA (OPENING) ===================== --}}
        @if (!$migrasiLocked)
        <div class="mt-6 mb-8 bg-white rounded-2xl shadow p-5 border border-amber-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold">Migrasi Bon Pelanggan (Buku Lama → Sistem)</h2>

                <form method="POST" action="{{ route('admin.pesanan.lock-migrasi-bon') }}"
                      onsubmit="return confirm('Kunci migrasi bon? Setelah dikunci, form ini hilang.');">
                    @csrf
                    @method('PATCH')
                    <button class="text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:brightness-110">Kunci</button>
                </form>
            </div>

            <p class="text-sm text-gray-600 mt-1">
                Gunakan panel ini hanya untuk memindahkan <strong>bon lama</strong>. Data akan tersimpan sebagai
                <strong>pesanan dengan metode BON</strong> (tanpa membuat rekap).
                Saat pelanggan melunasi, cukup ubah metode ke Tunai/QRIS seperti biasa.
            </p>

            <form method="POST" action="{{ route('admin.pesanan.store-migrasi-bon') }}"
                  class="grid md:grid-cols-2 gap-4 mt-4">
                @csrf

                <div>
                    <label class="text-sm">Nama Pelanggan <span class="text-red-500">*</span></label>
                    <input name="nama_pelanggan" type="text" required class="mt-1 w-full border rounded px-3 py-2"
                           value="{{ old('nama_pelanggan') }}">
                    @error('nama_pelanggan') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm">Layanan <span class="text-red-500">*</span></label>
                    <select name="service_id" 
                        class="mt-1 w-full border-2 border-gray-300 rounded-lg px-3 py-2 appearance-none bg-white hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                        style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-position: right 0.75rem center; background-repeat: no-repeat; padding-right: 2.5rem;"
                        required>
                        <option value="">— Pilih —</option>
                        @php
                            // Fungsi untuk aliasing nama layanan (migrasi bon)
                            function getServiceAliasMigrasi($namaService) {
                                $aliases = [
                                    'Cuci Self Service ≤ 7Kg' => 'Cuci',
                                    'Cuci Setrika Regular (/Kg)' => 'CKS R',
                                    'Kering Self Service ≤ 7Kg' => 'Kering',
                                    'Cuci Lipat Express ≤ 7Kg' => 'CKL E',
                                    'Cuci Setrika Express ≤ 3Kg' => 'CKS E 3Kg',
                                    'Cuci Setrika Express ≤ 5Kg' => 'CKS E 5Kg',
                                    'Cuci Setrika Express ≤ 7Kg' => 'CKS E 7Kg',
                                    'Cuci Lipat Regular (/Kg)' => 'CKL R',
                                ];
                                return $aliases[$namaService] ?? $namaService;
                            }
                            
                            // Layanan yang TIDAK boleh muncul (sama seperti form pesanan utama)
                            $excludedServicesMigrasi = ['Cuci Self Service Max 7Kg', 'Kering Self Service Max 7Kg', 'Deterjen', 'Pewangi', 'Proclin', 'Plastik Asoy', 'Antar Jemput (<=3KM)', 'Antar Jemput (>3KM)'];
                            
                            // Filter: ambil yang BUKAN termasuk excluded
                            $filteredServicesMigrasi = $services->filter(function($s) use ($excludedServicesMigrasi) {
                                return !in_array($s->nama_service, $excludedServicesMigrasi);
                            });
                            
                            // Urutkan services berdasarkan abjad (migrasi bon)
                            $sortedServicesMigrasi = $filteredServicesMigrasi->sortBy('nama_service');
                        @endphp
                        @foreach ($sortedServicesMigrasi as $s)
                            @if (!$s->is_fee_service)
                                <option value="{{ $s->id }}" @selected(old('service_id') == $s->id)>
                                    {{ getServiceAliasMigrasi($s->nama_service) }} — Rp {{ number_format($s->harga_service, 0, ',', '.') }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('service_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm">Kuantitas <span class="text-red-500">*</span></label>
                    <input name="qty" type="number" min="1" value="{{ old('qty', 1) }}" required
                           class="mt-1 w-28 border rounded px-3 py-2">
                    @error('qty') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm">Antar Jemput</label>
                    <select name="antar_jemput_service_id" 
                        class="mt-1 w-full border-2 border-gray-300 rounded-lg px-3 py-2 appearance-none bg-white hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                        style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-position: right 0.75rem center; background-repeat: no-repeat; padding-right: 2.5rem;">
                        <option value="">Tidak</option>
                        @foreach ($services as $s)
                            @if ($s->is_fee_service && in_array($s->nama_service, ['Antar Jemput (<=3KM)', 'Antar Jemput (>3KM)']))
                                <option value="{{ $s->id }}" @selected(old('antar_jemput_service_id') == $s->id)>
                                    {{ $s->nama_service }} — Rp {{ number_format($s->harga_service, 0, ',', '.') }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('antar_jemput_service_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 flex items-center justify-between mt-2">
                    <div class="text-xs text-gray-500">
                        Metode otomatis <b>BON</b>. Tidak ada baris rekap yang dibuat.
                    </div>
                    <button class="px-5 py-2 rounded-lg bg-gray-800 text-white hover:brightness-110">
                        Simpan Bon Migrasi
                    </button>
                </div>
            </form>

            @error('migrasi')
              <div class="mt-3 p-3 rounded bg-red-50 text-red-700 border border-red-200">{{ $message }}</div>
            @enderror

            @if(session('ok_migrasi_bon'))
              <div class="mt-3 p-3 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                {{ session('ok_migrasi_bon') }}
              </div>
            @endif
        </div>
        @endif
        {{-- =================== END: MIGRASI BON LAMA =================== --}}

        <div class="text-xl font-bold mb-4">Form Input Data Pesanan</div>

        <form method="POST" action="{{ route('admin.pesanan.store') }}" 
              x-data="pesananForm()" @submit="prepareSubmit">
            @csrf
            <div class="grid md:grid-cols-2 gap-4">
                <!-- Nama Pelanggan -->
                <div class="relative">
                    <label class="text-sm">Nama Pelanggan <span class="text-red-500">*</span></label>
                    <input name="nama_pel" x-model="nama" @input="onNamaInput()" @blur="hideSoon('nama')"
                           @focus="showList('nama')" autocomplete="off" class="mt-1 w-full border rounded px-3 py-2 @error('nama_pel') border-red-500 @enderror" required>
                    @error('nama_pel')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <div x-show="openNama && namaSaran.length"
                         class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded shadow" @mousedown.prevent>
                        <template x-for="s in namaSaran" :key="s.key">
                            <button type="button" class="block w-full text-left px-3 py-2 hover:bg-gray-50"
                                    @click="applySuggestion(s)">
                                <div class="font-medium" x-text="s.nama"></div>
                                <div class="text-xs text-gray-500" x-text="s.hp"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Nomor HP -->
                <div class="relative">
                    <label class="text-sm">No. HP <span class="text-red-500">*</span></label>
                    <input name="no_hp_pel" x-model="hp" type="text" 
                           @input="validateHP()"
                           pattern="[0-9]{10,14}" 
                           minlength="10" 
                           maxlength="14"
                           inputmode="numeric"
                           title="Nomor HP harus 10-14 digit angka"
                           autocomplete="off" 
                           class="mt-1 w-full border rounded px-3 py-2 @error('no_hp_pel') border-red-500 @enderror" 
                           required>
                    <p class="text-xs text-gray-500 mt-1">Masukkan 10-14 digit angka</p>
                    @error('no_hp_pel')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Metode Pembayaran -->
                <div>
                    <label class="text-sm">Metode Pembayaran</label>
                    <select name="metode_pembayaran_id" 
                        class="mt-1 w-full border-2 border-gray-300 rounded-lg px-3 py-2 appearance-none bg-white hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                        style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-position: right 0.75rem center; background-repeat: no-repeat; padding-right: 2.5rem;"
                        required>
                        @foreach ($metodes as $m)
                            <option value="{{ $m->id }}" @selected(old('metode_pembayaran_id') == $m->id || (strtolower($m->nama) === 'bon' && !old('metode_pembayaran_id')))>{{ ucfirst($m->nama) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Antar Jemput -->
                <div>
                    <label class="text-sm">Antar Jemput</label>
                    <select name="antar_jemput_service_id" 
                        class="mt-1 w-full border-2 border-gray-300 rounded-lg px-3 py-2 appearance-none bg-white hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                        style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-position: right 0.75rem center; background-repeat: no-repeat; padding-right: 2.5rem;">
                        <option value="">Tidak</option>
                        @foreach ($services as $s)
                            @if ($s->is_fee_service && in_array($s->nama_service, ['Antar Jemput (<=3KM)', 'Antar Jemput (>3KM)']))
                                <option value="{{ $s->id }}" @selected(old('antar_jemput_service_id') == $s->id)>
                                    {{ $s->nama_service }} — Rp {{ number_format($s->harga_service, 0, ',', '.') }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Layanan Items (Dynamic) -->
            <div class="mt-6">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-semibold">Layanan <span class="text-red-500">*</span></label>
                    <button type="button" @click="addServiceRow" 
                            class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                        + Tambah Layanan
                    </button>
                </div>

                <div class="space-y-2">
                    <template x-for="(item, index) in serviceItems" :key="item.id">
                        <div class="flex gap-2 items-start">
                            <div class="flex-1">
                                <select :name="'services[' + index + '][service_id]'" 
                                    x-model="item.service_id"
                                    class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-sm appearance-none bg-white hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                                    style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-position: right 0.75rem center; background-repeat: no-repeat; padding-right: 2.5rem;"
                                    required>
                                    <option value="">— Pilih Layanan —</option>
                                    @php
                                        // Fungsi untuk aliasing nama layanan
                                        function getServiceAliasPesanan($namaService) {
                                            $aliases = [
                                                'Cuci Lipat Express ≤7Kg' => 'CKL E',
                                                'Cuci Setrika Express ≤3Kg' => 'CKS E 3Kg',
                                                'Cuci Setrika Express ≤5Kg' => 'CKS E 5Kg',
                                                'Cuci Setrika Express ≤7Kg' => 'CKS E 7Kg',
                                            ];
                                            return $aliases[$namaService] ?? $namaService;
                                        }
                                        
                                        // Layanan yang TIDAK boleh muncul
                                        $excludedServices = ['Cuci Self Service ≤7Kg', 'Kering Self Service ≤7Kg', 'Antar Jemput (≤3KM)', 'Antar Jemput (>3KM)'];
                                        
                                        // Filter dan sort
                                        $filteredServicesPesanan = $services->filter(function($s) use ($excludedServices) {
                                            return !in_array($s->nama_service, $excludedServices);
                                        });
                                        
                                        $sortedServicesPesanan = $filteredServicesPesanan->sortBy('nama_service');
                                    @endphp
                                    @foreach ($sortedServicesPesanan as $s)
                                        @if (!$s->is_fee_service)
                                            <option value="{{ $s->id }}">
                                                {{ getServiceAliasPesanan($s->nama_service) }} — Rp {{ number_format($s->harga_service, 0, ',', '.') }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="decQty(index)" 
                                        class="h-9 w-9 rounded border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center">
                                    <span class="text-lg font-semibold">−</span>
                                </button>
                                <input type="text" :name="'services[' + index + '][qty]'" 
                                       x-model="item.qty"
                                       inputmode="numeric"
                                       class="w-16 text-center border border-gray-300 rounded px-2 py-2 text-sm" 
                                       @input="syncQty(index)"
                                       required>
                                <button type="button" @click="incQty(index)" 
                                        class="h-9 w-9 rounded border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center">
                                    <span class="text-lg font-semibold">+</span>
                                </button>
                            </div>
                            <button type="button" @click="removeServiceRow(index)" 
                                    x-show="serviceItems.length > 1"
                                    class="h-9 w-9 rounded bg-red-600 text-white hover:bg-red-700 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="px-5 py-3 rounded-lg bg-blue-600 text-white hover:brightness-110">Simpan Pesanan</button>
            </div>
        </form>
    </div>

    <div class="mt-8 bg-white p-5 rounded-xl shadow">
        <div class="text-xl font-bold mb-4">Tabel Pesanan Laundry</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2"></th>
                        <th class="px-3 py-2 text-left">Pelanggan</th>
                        <th class="px-3 py-2 text-left">Layanan</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-center">Pembayaran</th>
                        <th class="px-3 py-2 text-left">Update Terakhir</th>
                    </tr>
                </thead>
                <tbody
                    class="
                [&_tr:nth-child(odd)]:bg-slate-50/70
                [&_tr:nth-child(even)]:bg-white
                [&_tr:hover]:bg-amber-50/40
              ">
                    @php
                        $groupedPesanan = [];
                        $processedGroups = [];
                        
                        foreach ($pesanan as $p) {
                            $groupId = $p->group_id ?? 'single-' . $p->id;
                            
                            if (!isset($groupedPesanan[$groupId])) {
                                $groupedPesanan[$groupId] = [
                                    'main' => $p,
                                    'additional' => []
                                ];
                            } else {
                                $groupedPesanan[$groupId]['additional'][] = $p;
                            }
                        }
                    @endphp
                    
                    @foreach ($groupedPesanan as $groupId => $group)
                        @php
                            $p = $group['main'];
                            $additionalServices = $group['additional'];
                            
                            // Hitung total untuk pesanan utama
                            $qty = max(1, (int) ($p->qty ?? 1));
                            $harga = (int) ($p->harga_satuan ?? ($p->service->harga_service ?? 0));
                            $totalLayanan = $qty * $harga;
                            
                            // Hitung antar jemput jika ada
                            $hargaAntarJemput = 0;
                            if ($p->antar_jemput_service_id && $p->antarJemputService) {
                                $hargaAntarJemput = (int) ($p->antarJemputService->harga_service ?? 0);
                            }
                            
                            // Hitung total dari layanan tambahan
                            $totalAdditional = 0;
                            foreach ($additionalServices as $addServ) {
                                $qtyAdd = max(1, (int) ($addServ->qty ?? 1));
                                $hargaAdd = (int) ($addServ->harga_satuan ?? ($addServ->service->harga_service ?? 0));
                                $totalAdditional += $qtyAdd * $hargaAdd;
                            }
                            
                            $total = $totalLayanan + $hargaAntarJemput + $totalAdditional;
                            
                            $metode = $p->metode->nama ?? null;
                            $isLunas = in_array($metode, ['tunai', 'qris']);
                            $currStatus = optional($p->statuses->first())->keterangan ?? 'Diproses';
                            
                            // Cek apakah pesanan ini akan segera auto-hide (hari ke-12 & 13)
                            $statusSelesai = $p->statuses()->where('keterangan', 'Selesai')->latest()->first();
                            $hariSelesai = $statusSelesai ? $statusSelesai->created_at->diffInDays(now()) : 0;
                            $willAutoHide = $statusSelesai && $hariSelesai >= 12 && $hariSelesai < 14 && !$p->is_hidden;
                        @endphp

                        <tr class="border-t {{ $p->is_hidden ? 'bg-red-50/60' : ($willAutoHide ? 'bg-orange-50/40' : '') }}">
                            <td class="px-3 py-2">
                                <div class="flex gap-1">
                                    @if (!$p->is_hidden)
                                        {{-- Tombol Hide --}}
                                        <form method="POST" action="{{ route('admin.pesanan.destroy', $p) }}"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menyembunyikan pesanan ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-600 text-white hover:bg-red-700 transition-colors shadow-sm hover:shadow-md"
                                                title="Sembunyikan">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </form>
                                        
                                        {{-- Tombol WhatsApp --}}
                                        @php
                                            // Bersihkan nomor HP (hapus karakter non-digit)
                                            $cleanHP = preg_replace('/\D/', '', $p->no_hp_pel);
                                            
                                            // Format nomor HP untuk WhatsApp (tambahkan 62 jika dimulai dengan 0)
                                            if (substr($cleanHP, 0, 1) === '0') {
                                                $waNumber = '62' . substr($cleanHP, 1);
                                            } elseif (substr($cleanHP, 0, 2) !== '62') {
                                                $waNumber = '62' . $cleanHP;
                                            } else {
                                                $waNumber = $cleanHP;
                                            }
                                            
                                            // Buat pesan WhatsApp
                                            $waMessage = "Halo kak " . $p->nama_pel . ", terima kasih sudah menggunakan layanan Qxpress Laundry 🙏\n\n";
                                            $waMessage .= "Berikut adalah rincian pesanan laundry anda:\n\n";
                                            
                                            // Layanan utama
                                            $waMessage .= "• " . ($p->service->nama_service ?? '-') . " (" . $qty . "x)\n";
                                            $waMessage .= "  Subtotal: Rp " . number_format($totalLayanan, 0, ',', '.') . "\n\n";
                                            
                                            // Layanan tambahan
                                            foreach ($additionalServices as $addServ) {
                                                $qtyAdd = max(1, (int) ($addServ->qty ?? 1));
                                                $hargaAdd = (int) ($addServ->harga_satuan ?? ($addServ->service->harga_service ?? 0));
                                                $subtotalAdd = $qtyAdd * $hargaAdd;
                                                $waMessage .= "• " . ($addServ->service->nama_service ?? '-') . " (" . $qtyAdd . "x)\n";
                                                $waMessage .= "  Subtotal: Rp " . number_format($subtotalAdd, 0, ',', '.') . "\n\n";
                                            }
                                            
                                            // Antar jemput
                                            if ($p->antar_jemput_service_id && $p->antarJemputService) {
                                                $waMessage .= "• " . $p->antarJemputService->nama_service . "\n";
                                                $waMessage .= "  Subtotal: Rp " . number_format($hargaAntarJemput, 0, ',', '.') . "\n\n";
                                            }
                                            
                                            $waMessage .= "━━━━━━━━━━━━━━━━━━━\n";
                                            $waMessage .= "*TOTAL: Rp " . number_format($total, 0, ',', '.') . "*\n\n";
                                            $waMessage .= "Untuk memantau proses cucian kakak, silakan cek status laundry melalui website kami di:\n";
                                            $waMessage .= "👉 qxpresslaundry.com/tracking";
                                            
                                            $waLink = "https://wa.me/" . $waNumber . "?text=" . urlencode($waMessage);
                                        @endphp
                                        
                                        <a href="{{ $waLink }}" target="_blank"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-600 text-white hover:bg-green-700 transition-colors shadow-sm hover:shadow-md"
                                            title="Kirim Rincian Pesanan via WhatsApp">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                            </svg>
                                        </a>
                                    @else
                                        <span class="text-[11px] text-gray-400 italic">✓</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <div>
                                    {{ $p->nama_pel }}
                                    @if ($p->is_hidden)
                                        <span
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border bg-red-100 text-red-700 border-red-200">Disembunyikan</span>
                                    @elseif ($willAutoHide)
                                        <span
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border bg-orange-100 text-orange-700 border-orange-200"
                                            title="Akan otomatis tersembunyi dalam {{ 14 - $hariSelesai }} hari">
                                            Auto-hide {{ 14 - $hariSelesai }}h
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $p->no_hp_pel }}</div>
                            </td>
                            <td class="px-3 py-2">
                                <div>{{ $p->service->nama_service ?? '-' }} ({{ $qty }}x)</div>
                                @foreach ($additionalServices as $addServ)
                                    <div class="mt-0.5">
                                        {{ $addServ->service->nama_service ?? '-' }} ({{ $addServ->qty }}x)
                                    </div>
                                @endforeach
                                @if ($p->antar_jemput_service_id && $p->antarJemputService)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $p->antarJemputService->nama_service }}</div>
                                @endif
                            </td>

                            {{-- Toggle status dengan satu klik --}}
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.status.store') }}">
                                        @csrf
                                        <input type="hidden" name="pesanan_id" value="{{ $p->id }}">
                                        @php
                                            $newStatus = ($currStatus === 'Diproses') ? 'Selesai' : 'Diproses';
                                            $btnClass = match ($currStatus) {
                                                'Diproses' => 'bg-yellow-500 text-white hover:bg-yellow-600 shadow-sm hover:shadow-md',
                                                'Selesai' => 'bg-blue-500 text-white hover:bg-blue-600 shadow-sm hover:shadow-md',
                                                default => 'bg-gray-500 text-white hover:bg-gray-600 shadow-sm hover:shadow-md',
                                            };
                                        @endphp
                                        <input type="hidden" name="keterangan" value="{{ $newStatus }}">
                                        <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-md px-3.5 py-2 text-xs transition-all duration-150 active:scale-95 {{ $btnClass }}"
                                            title="Klik untuk ubah ke {{ $newStatus }}">
                                            {{ $currStatus }}
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-75" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </form>
                                    
                                    {{-- Tombol WhatsApp Notifikasi Selesai --}}
                                    @php
                                        // Bersihkan nomor HP untuk WhatsApp
                                        $cleanHPNotif = preg_replace('/\D/', '', $p->no_hp_pel);
                                        
                                        // Format nomor HP untuk WhatsApp
                                        if (substr($cleanHPNotif, 0, 1) === '0') {
                                            $waNumberNotif = '62' . substr($cleanHPNotif, 1);
                                        } elseif (substr($cleanHPNotif, 0, 2) !== '62') {
                                            $waNumberNotif = '62' . $cleanHPNotif;
                                        } else {
                                            $waNumberNotif = $cleanHPNotif;
                                        }
                                        
                                        // Pesan notifikasi selesai
                                        $waNotifSelesai = "Halo kak " . $p->nama_pel . "\n\n";
                                        $waNotifSelesai .= "*CUCIAN ANDA SUDAH SELESAI!*\n\n";
                                        $waNotifSelesai .= "Berikut rincian pesanan Anda:\n\n";
                                        
                                        // Layanan utama
                                        $waNotifSelesai .= "• " . ($p->service->nama_service ?? '-') . " (" . $qty . "x)\n";
                                        
                                        // Layanan tambahan
                                        foreach ($additionalServices as $addServ) {
                                            $qtyAdd = max(1, (int) ($addServ->qty ?? 1));
                                            $waNotifSelesai .= "• " . ($addServ->service->nama_service ?? '-') . " (" . $qtyAdd . "x)\n";
                                        }
                                        
                                        // Antar jemput
                                        if ($p->antar_jemput_service_id && $p->antarJemputService) {
                                            $waNotifSelesai .= "• " . $p->antarJemputService->nama_service . "\n";
                                        }
                                        
                                        $waNotifSelesai .= "\n*Total: Rp " . number_format($total, 0, ',', '.') . "*\n\n";
                                        $waNotifSelesai .= "Cucian Anda sudah bisa diambil di Qxpress Laundry. ";
                                        $waNotifSelesai .= "Kami tunggu kedatangan kakak ya😊\n\n";
                                        $waNotifSelesai .= "Terima kasih sudah menggunakan layanan kami. 🙏\n\n";
                                        $waNotifSelesai .= "Cek status laundry: qxpresslaundry.com/tracking";
                                        
                                        $waLinkNotif = "https://wa.me/" . $waNumberNotif . "?text=" . urlencode($waNotifSelesai);
                                    @endphp
                                    
                                    {{-- Tombol WhatsApp hanya muncul jika status Selesai --}}
                                    @if($currStatus === 'Selesai')
                                        <a href="{{ $waLinkNotif }}" target="_blank"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-600 text-white hover:bg-green-700 transition-colors shadow-sm hover:shadow-md"
                                            title="Kirim Notifikasi Selesai via WhatsApp">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>

                            <td class="px-3 py-2 text-right">Rp {{ number_format($total, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-center">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs
              {{ $isLunas ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                    {{ $isLunas ? 'Lunas' : 'Belum Lunas' }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ optional($p->statuses->first())->created_at?->format('d/m/y H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $pesanan->links() }}</div>
    </div>

    <script>
        // Filter pelanggan dengan nomor HP yang valid
        const CUSTOMERS = [
            @foreach ($pelangganOptions as $pl)
                @php
                    $hp = trim($pl->no_hp_pel ?? '');
                    $hasValidHp = $hp !== '' && $hp !== '-' && $hp !== 'null';
                @endphp
                @if ($hasValidHp)
                    { nama: {!! json_encode($pl->nama_pel) !!}, hp: {!! json_encode($pl->no_hp_pel) !!} },
                @endif
            @endforeach
        ];
        const norm = s => (s || '').toString().trim().toLowerCase();
        const onlyDigits = s => (s || '').replace(/\D/g, '');

        function lev(a, b) {
            a = norm(a); b = norm(b);
            const m = a.length, n = b.length;
            if (!m) return n; if (!n) return m;
            const dp = Array.from({length: m+1}, (_, i) => Array(n+1).fill(0));
            for (let i=0;i<=m;i++) dp[i][0]=i;
            for (let j=0;j<=n;j++) dp[0][j]=j;
            for (let i=1;i<=m;i++){
                for (let j=1;j<=n;j++){
                    const cost = a[i-1]===b[j-1]?0:1;
                    dp[i][j]=Math.min(dp[i-1][j]+1, dp[i][j-1]+1, dp[i-1][j-1]+cost);
                }
            }
            return dp[m][n];
        }

        function rankByName(q){
            q = norm(q); if(!q) return [];
            return CUSTOMERS.map((c,i)=>{
                const n = norm(c.nama);
                const s = (n.startsWith(q)?0:n.includes(q)?1:lev(n,q));
                return {key:i, ...c, score:s};
            }).filter(x=>x.score<=3||x.score<=1).sort((a,b)=>a.score-b.score).slice(0,6);
        }

        function pesananForm(){
            return {
                // Pelanggan picker
                nama: @json(old('nama_pel','')),
                hp: @json(old('no_hp_pel','')),
                openNama:false,
                namaSaran:[],
                hideTimer:null,
                updateConfirmed: false, // Flag untuk mencegah loop konfirmasi
                
                // Service items (multiple)
                serviceItems: [
                    { id: Date.now(), service_id: '', qty: 1 }
                ],
                
                showList(w){ if(w==='nama') this.openNama = true; },
                hideSoon(w){ 
                    clearTimeout(this.hideTimer); 
                    this.hideTimer=setTimeout(()=>{ 
                        if(w==='nama') this.openNama=false; 
                    },120); 
                },
                onNamaInput(){ 
                    this.namaSaran = rankByName(this.nama); 
                    this.openNama = !!this.namaSaran.length; 
                    const e = CUSTOMERS.find(c=>norm(c.nama)===norm(this.nama)); 
                    if(e && !this.hp) this.hp = e.hp;
                },
                applySuggestion(s){ 
                    this.nama=s.nama; 
                    this.hp=s.hp; 
                    this.openNama=false;
                },
                
                // HP validation: only allow numbers
                validateHP() {
                    // Remove non-digit characters
                    this.hp = this.hp.replace(/\D/g, '');
                    // Limit to 14 digits
                    if (this.hp.length > 14) {
                        this.hp = this.hp.substring(0, 14);
                    }
                },
                
                // Service methods
                addServiceRow() {
                    this.serviceItems.push({
                        id: Date.now(),
                        service_id: '',
                        qty: 1
                    });
                },
                removeServiceRow(index) {
                    if (this.serviceItems.length > 1) {
                        this.serviceItems.splice(index, 1);
                    }
                },
                incQty(index) {
                    const currentQty = parseInt(this.serviceItems[index].qty) || 1;
                    this.serviceItems[index].qty = currentQty + 1;
                },
                decQty(index) {
                    const currentQty = parseInt(this.serviceItems[index].qty) || 1;
                    this.serviceItems[index].qty = Math.max(1, currentQty - 1);
                },
                syncQty(index) {
                    // Ambil hanya digit dari input
                    const digits = String(this.serviceItems[index].qty).replace(/\D/g, '');
                    const num = Math.max(1, parseInt(digits) || 1);
                    this.serviceItems[index].qty = num;
                },
                prepareSubmit(e) {
                    // Validasi nomor HP
                    const hpDigits = this.hp.replace(/\D/g, '');
                    if (hpDigits.length < 10 || hpDigits.length > 14) {
                        e.preventDefault();
                        alert('Nomor HP harus 10-14 digit angka!');
                        return false;
                    }
                    
                    // Validasi minimal 1 layanan terisi
                    const hasService = this.serviceItems.some(item => item.service_id);
                    if (!hasService) {
                        e.preventDefault();
                        alert('Pilih minimal 1 layanan!');
                        return false;
                    }
                    
                    // Jika sudah dikonfirmasi sebelumnya, langsung submit
                    if (this.updateConfirmed) {
                        return true; // Lanjutkan submit
                    }
                    
                    // Cek duplikasi nama pelanggan dengan nomor HP berbeda
                    const normalizedNama = norm(this.nama);
                    const existingCustomer = CUSTOMERS.find(c => norm(c.nama) === normalizedNama);
                    
                    if (existingCustomer && existingCustomer.hp !== this.hp) {
                        // Nomor HP berbeda - bisa jadi orang berbeda atau update data
                        e.preventDefault();
                        
                        const choice = confirm(
                            `⚠️ PERHATIAN!\n\n` +
                            `Nama "${this.nama}" sudah terdaftar dengan nomor HP: ${existingCustomer.hp}\n\n` +
                            `Nomor HP yang Anda input: ${this.hp}\n\n` +
                            `Pilih:\n` +
                            `• CANCEL = Ini pelanggan BARU dengan nama sama. Silakan lengkapi nama (misal: ${this.nama} 2, ${this.nama} Sukabumi, dll)\n` +
                            `• OK = Ini pelanggan SAMA, UPDATE nomor HP ke ${this.hp}`
                        );
                        
                        if (!choice) {
                            // User pilih CANCEL - minta lengkapi nama
                            alert('❌ Pesanan dibatalkan!\n\nSilakan lengkapi nama pelanggan untuk menghindari duplikasi data.\n\nContoh: ' + this.nama + ' 2, ' + this.nama + ' Sukabumi, dll.');
                            return false;
                        }
                        
                        // User pilih OK - konfirmasi sekali lagi
                        const confirmUpdate = confirm(
                            `✅ KONFIRMASI UPDATE DATA\n\n` +
                            `Anda akan mengupdate data pelanggan:\n` +
                            `Nama: ${existingCustomer.nama}\n\n` +
                            `Nomor HP lama: ${existingCustomer.hp}\n` +
                            `Nomor HP baru: ${this.hp}\n\n` +
                            `Data lama akan diganti dengan data baru ini.\n\n` +
                            `Lanjutkan?`
                        );
                        
                        if (!confirmUpdate) {
                            return false;
                        }
                        
                        // Set flag untuk update customer dan tandai sudah konfirmasi
                        this.updateConfirmed = true;
                        
                        // Set flag untuk update customer di form
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'update_customer_data';
                        input.value = '1';
                        e.target.appendChild(input);
                        
                        // Submit form secara manual
                        e.target.submit();
                        return false;
                    }
                    
                    // Jika nama dan HP sama persis, atau nama baru, lanjutkan tanpa warning
                    return true;
                }
            }
        }
    </script>
@endsection
