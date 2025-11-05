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
                    <select name="service_id" class="mt-1 w-full border rounded px-3 py-2" required>
                        <option value="">- Pilih Layanan -</option>
                        @foreach ($services as $s)
                            @if (!$s->is_fee_service)
                                <option value="{{ $s->id }}" @selected(old('service_id') == $s->id)>
                                    {{ $s->nama_service }} — Rp {{ number_format($s->harga_service, 0, ',', '.') }}
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

        <form method="POST" action="{{ route('admin.pesanan.store') }}" class="grid md:grid-cols-2 gap-4"
              x-data="pelangganPicker()">
            @csrf
            <!-- Nama Pelanggan -->
            <div class="relative">
                <label class="text-sm">Nama Pelanggan</label>
                <input name="nama_pel" x-model="nama" @input="onNamaInput()" @blur="hideSoon('nama')"
                       @focus="showList('nama')" autocomplete="off" class="mt-1 w-full border rounded px-3 py-2" required>
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
                <label class="text-sm">No. HP</label>
                <input name="no_hp_pel" x-model="hp" @input="onHpInput()" @blur="hideSoon('hp')" @focus="showList('hp')"
                       autocomplete="off" class="mt-1 w-full border rounded px-3 py-2" required>
                <div x-show="openHp && hpSaran.length"
                     class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded shadow" @mousedown.prevent>
                    <template x-for="s in hpSaran" :key="s.key">
                        <button type="button" class="block w-full text-left px-3 py-2 hover:bg-gray-50"
                                @click="applySuggestion(s)">
                            <div class="font-medium" x-text="s.hp"></div>
                            <div class="text-xs text-gray-500" x-text="s.nama"></div>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Pilih Layanan -->
            <div>
                <label class="text-sm">Pilih Layanan</label>
                <select name="service_id" class="mt-1 w-full border rounded px-3 py-2" required>
                    <option value="">- Pilih Layanan -</option>
                    @foreach ($services as $s)
                        @if (!$s->is_fee_service)
                            <option value="{{ $s->id }}" @selected(old('service_id') == $s->id)>
                                {{ $s->nama_service }} — Rp {{ number_format($s->harga_service, 0, ',', '.') }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <!-- Kuantitas -->
            <div>
                <label class="text-sm">Kuantitas</label>
                <input name="qty" type="number" min="1" value="{{ old('qty', 1) }}"
                       class="mt-1 w-full border rounded px-3 py-2" required>
            </div>

            <!-- Metode Pembayaran -->
            <div>
                <label class="text-sm">Metode Pembayaran</label>
                <select name="metode_pembayaran_id" class="mt-1 w-full border rounded px-3 py-2" required>
                    @foreach ($metodes as $m)
                        <option value="{{ $m->id }}" @selected(old('metode_pembayaran_id') == $m->id || (strtolower($m->nama) === 'bon' && !old('metode_pembayaran_id')))>{{ ucfirst($m->nama) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Awal -->
            <div>
                <label class="text-sm">Status Pesanan</label>
                <select name="status_awal" class="mt-1 w-full border rounded px-3 py-2" required>
                    <option @selected(old('status_awal') === 'Diproses')>Diproses</option>
                    <option @selected(old('status_awal') === 'Selesai')>Selesai</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <button class="px-5 py-3 rounded-lg bg-blue-600 text-white hover:brightness-110">Simpan Pesanan</button>
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
                        <th class="px-3 py-2 text-center">Qty</th>
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
                    @foreach ($pesanan as $p)
                        @php
                            $qty = max(1, (int) ($p->qty ?? 1));
                            $harga = (int) ($p->harga_satuan ?? ($p->service->harga_service ?? 0));
                            $total = $qty * $harga;
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
                                @if (!$p->is_hidden)
                                    <form method="POST" action="{{ route('admin.pesanan.destroy', $p) }}"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menyembunyikan pesanan ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center hover:bg-red-700 transition-colors"
                                            title="Sembunyikan">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-gray-400 italic">✓</span>
                                @endif
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
                            <td class="px-3 py-2">{{ $p->service->nama_service ?? '-' }}</td>

                            {{-- Toggle status dengan satu klik --}}
                            <td class="px-3 py-2">
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
                            </td>

                            <td class="px-3 py-2 text-center">{{ $qty }}</td>
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

        function rankByHp(q){
            q = onlyDigits(q); if(!q) return [];
            return CUSTOMERS.map((c,i)=>{
                const h = onlyDigits(c.hp);
                const s = (h.startsWith(q)?0:h.includes(q)?1:lev(h,q));
                return {key:i, ...c, score:s};
            }).filter(x=>x.score<=3||x.score<=1).sort((a,b)=>a.score-b.score).slice(0,6);
        }

        function pelangganPicker(){
            return {
                nama: @json(old('nama_pel','')),
                hp: @json(old('no_hp_pel','')),
                openNama:false, openHp:false,
                namaSaran:[], hpSaran:[],
                hideTimer:null,
                showList(w){ if(w==='nama') this.openNama = true; else this.openHp = true; },
                hideSoon(w){ clearTimeout(this.hideTimer); this.hideTimer=setTimeout(()=>{ if(w==='nama') this.openNama=false; else this.openHp=false; },120); },
                onNamaInput(){ this.namaSaran = rankByName(this.nama); this.openNama = !!this.namaSaran.length; const e = CUSTOMERS.find(c=>norm(c.nama)===norm(this.nama)); if(e && !this.hp) this.hp = e.hp; },
                onHpInput(){ this.hpSaran = rankByHp(this.hp); this.openHp = !!this.hpSaran.length; const e = CUSTOMERS.find(c=>onlyDigits(c.hp)===onlyDigits(this.hp)); if(e && !this.nama) this.nama = e.nama; },
                applySuggestion(s){ this.nama=s.nama; this.hp=s.hp; this.openNama=false; this.openHp=false; }
            }
        }
    </script>
@endsection
