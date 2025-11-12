@extends('admin.layout')
@section('title', 'Input rekap keuangan – Qxpress Laundry')

@php
    // Metode default selain "bon"
    $defaultNonBon =
        optional($metodes->first(fn($m) => strtolower($m->nama) !== 'bon'))->id ?? optional($metodes->first())->id;

    // Flag: apakah halaman ini sedang melihat hari ini?
    $isToday = isset($day) ? $day->isToday() : true;
    $isYesterday = isset($day) ? $day->isYesterday() : false;
    $isEditable = $isToday || $isYesterday;
@endphp

@section('content')
    <div class="flex items-center justify-between">
        <div class="text-xl md:text-2xl font-bold">
            {{ $isYesterday ? 'Revisi Rekap Kemarin' : 'Input rekap keuangan' }} – Qxpress Laundry
        </div>
        <a href="{{ route('admin.rekap.index', ['d' => request('d')]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-gray-300 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-100 hover:shadow transition-all duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>
    </div>

    {{-- Banner untuk H-1 (Mode Revisi) --}}
    @if ($isYesterday)
        <div class="mt-4 p-4 rounded-lg border border-orange-300 bg-orange-50 text-orange-800 text-sm font-medium flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <div class="font-bold text-base mb-1">⚠️ MODE REVISI KEMARIN (H-1)</div>
                <p>Anda sedang merevisi data tanggal: <strong>{{ $day->translatedFormat('l, d F Y') }}</strong></p>
                <p class="mt-2">
                    <strong>Perhatian:</strong> Setiap perubahan pada data kemarin akan mempengaruhi perhitungan hari ini:
                </p>
                <ul class="list-disc pl-5 mt-1 space-y-1">
                    <li>Mengubah omset/pengeluaran kemarin → Mengubah saldo kas hari ini</li>
                    <li>Mengubah fee kemarin → Mengubah saldo kas hari ini</li>
                    <li>Mengubah saldo kartu kemarin → Mengubah total tap hari ini</li>
                    <li><strong>Transaksi BON tidak dapat diubah</strong> di mode revisi (hanya di halaman rekap)</li>
                </ul>
                <p class="mt-2 font-semibold">Pastikan revisi dilakukan dengan hati-hati dan teliti!</p>
            </div>
        </div>
    @endif

    {{-- Banner untuk H-2 dan sebelumnya (Read-only) --}}
    @if (!$isEditable)
        <div class="mt-4 p-3 rounded bg-yellow-50 text-yellow-800 border border-yellow-200">
            Mode baca: Anda sedang membuka data tanggal {{ $day->translatedFormat('l, d F Y') }}.
            <strong>Input/update dinonaktifkan</strong>. Hanya data hari ini dan kemarin yang dapat diedit.
        </div>
    @endif

    {{-- ======================= OPENING KAS AWAL (SEKALI ISI) ======================= --}}
    @php
      $opening = \App\Models\OpeningSetup::latest('id')->first();
      $openingDone = $opening && $opening->locked;
    @endphp

    @if (!$openingDone)
      <section class="mt-6 bg-white rounded-2xl shadow p-5" id="form-opening-cash">
        <div class="flex items-center justify-between mb-4">
          <div class="text-lg font-bold">Opening Kas Awal (sekali isi)</div>

          {{-- Tombol kunci: sembunyikan form setelah dikunci --}}
          @if($opening && !$opening->locked)
            <form method="POST" action="{{ route('admin.rekap.lock-opening') }}"
                  onsubmit="return confirm('Kunci opening? Setelah dikunci tidak bisa diedit dari sini.')">
              @csrf
              @method('PATCH')
              <button class="px-3 py-1.5 rounded-lg bg-gray-700 text-white text-sm" {{ $isEditable ? '' : 'disabled' }}>
                Kunci
              </button>
            </form>
          @endif
        </div>

        @if (session('ok_opening'))
          <div class="mb-3 p-3 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
            {{ session('ok_opening') }}
          </div>
        @endif

        {{-- Error khusus opening --}}
        @if ($errors->has('init_cash') || $errors->has('cutover_date'))
          <div class="mb-3 p-3 rounded bg-red-50 text-red-700 border border-red-200">
            <ul class="list-disc pl-5 space-y-1">
              @error('init_cash') <li>{{ $message }}</li> @enderror
              @error('cutover_date') <li>{{ $message }}</li> @enderror
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('admin.rekap.store-opening') }}" class="grid md:grid-cols-3 gap-4">
          @csrf

          {{-- Saldo kas awal (Rp) dengan formatter bertitik --}}
          <div>
            <label class="block text-sm mb-1">Saldo Kas Awal (Rp)</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">Rp</span>
              <input id="init_cash_display" type="text" inputmode="numeric" autocomplete="off"
                     class="w-full border rounded px-3 py-2 pl-9"
                     value="{{ old('init_cash', optional($opening)->init_cash ? number_format((int) $opening->init_cash, 0, ',', '.') : '') }}"
                     {{ $isEditable ? '' : 'disabled' }}>
              <input id="init_cash" name="init_cash" type="hidden" value="{{ old('init_cash', optional($opening)->init_cash) }}">
            </div>
            @error('init_cash') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          {{-- Tanggal cutover (opsional, default hari ini / existing) --}}
          <div>
            <label class="block text-sm mb-1">Tanggal Cutover</label>
            <input name="cutover_date" type="date" class="w-full border rounded px-3 py-2"
                   value="{{ old('cutover_date', optional(optional($opening)->cutover_date)->toDateString() ?? now()->toDateString()) }}"
                   {{ $isEditable ? '' : 'disabled' }}>
            @error('cutover_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div class="flex items-end">
            <button class="px-4 py-2 rounded-lg bg-gray-800 text-white hover:brightness-110 disabled:opacity-50"
                    {{ $isEditable ? '' : 'disabled' }}>
              Simpan Opening
            </button>
          </div>
        </form>

        <p class="text-xs text-gray-500 mt-3">
          Catatan: Opening ini hanya menambah dasar kas dan <strong>tidak dihitung sebagai omzet harian</strong>.
          Setelah <strong>dikunci</strong>, blok ini akan hilang dari halaman.
        </p>
      </section>

      {{-- Script formatter rupiah untuk Opening Kas --}}
      <script>
        (function() {
          const fmt = new Intl.NumberFormat('id-ID');
          const display = document.getElementById('init_cash_display');
          const hidden  = document.getElementById('init_cash');
          if (!display || !hidden) return;

          const sanitize = s => (s || '').replace(/[^\d]/g, '');

          function render() {
            const raw = sanitize(display.value);
            if (raw.length) {
              const n = parseInt(raw, 10) || 0;
              hidden.value = n;
              display.value = fmt.format(n);
            } else {
              hidden.value = '';
              display.value = '';
            }
          }

          // init dari nilai awal
          render();

          display.addEventListener('input', () => {
            const atEnd = display.selectionStart === display.value.length;
            render();
            if (atEnd) {
              const len = display.value.length;
              display.setSelectionRange(len, len);
            }
          });
        })();
      </script>
    @endif

    {{-- ======================= FORM OMSET ======================= --}}
    <section x-data="omsetForm()" class="mt-6 bg-white rounded-2xl shadow p-5" id="form-omzet">
        <div class="text-lg font-bold mb-4">Tabel Omset</div>

        {{-- ERROR OMZET --}}
        @if ($errors->omzet->any())
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                <ul class="list-disc pl-5">
                    @foreach ($errors->omzet->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">No</th>
                        <th class="px-3 py-2 text-left">Nama Layanan</th>
                        <th class="px-3 py-2 text-left">Metode</th>
                        <th class="px-3 py-2 text-center">Kuantitas</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>

                <tbody class="
                [&_tr:nth-child(odd)]:bg-slate-50/70
                [&_tr:nth-child(even)]:bg-white
                [&_tr:hover]:bg-amber-50/40
              ">
                    @php
                        // Fungsi untuk aliasing nama layanan
                        function getServiceAliasInput($namaService) {
                            $aliases = [
                                'Cuci Self Service Max 7Kg' => 'Cuci',
                                'Cuci Setrika Regular (/Kg)' => 'CKS R',
                                'Kering Self Service Max 7Kg' => 'Kering',
                                'Cuci Lipat Express Max 7Kg' => 'CKL E',
                                'Cuci Setrika Express 3Kg' => 'CKS E 3Kg',
                                'Cuci Setrika Express 5Kg' => 'CKS E 5Kg',
                                'Cuci Setrika Express 7Kg' => 'CKS E 7Kg',
                                'Cuci Lipat Regular (/Kg)' => 'CKL R',
                            ];
                            return $aliases[$namaService] ?? $namaService;
                        }
                        
                        // Urutkan services berdasarkan abjad
                        $sortedServices = $services->sortBy('nama_service');
                    @endphp
                    
                    <template x-for="(r,idx) in rows" :key="idx">
                        <tr class="border-t">
                            <td class="px-3 py-2" x-text="idx+1"></td>

                            <!-- Layanan -->
                            <td class="px-2 py-2">
                                <select x-model="r.service_id" 
                                    class="border-2 border-gray-300 rounded-lg px-2 py-1.5 w-40 text-sm font-medium appearance-none bg-white hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                                    style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-position: right 0.5rem center; background-repeat: no-repeat; padding-right: 2rem;"
                                    {{ $isEditable ? '' : 'disabled' }}>
                                    <option value="">— Pilih —</option>
                                    @foreach ($sortedServices as $s)
                                        <option value="{{ $s->id }}">{{ getServiceAliasInput($s->nama_service) }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- Metode -->
                            <td class="px-2 py-2">
                                <select x-model="r.metode_pembayaran_id" 
                                    class="border-2 border-gray-300 rounded-lg px-3 py-1.5 w-28 text-sm font-medium appearance-none bg-white hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200"
                                    style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-position: right 0.5rem center; background-repeat: no-repeat; padding-right: 2rem;"
                                    {{ $isEditable ? '' : 'disabled' }}>
                                    @foreach ($metodes as $m)
                                        @if (strtolower($m->nama) !== 'bon')
                                            <option value="{{ $m->id }}">{{ $m->nama }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>

                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" class="h-7 w-7 rounded border" @click="dec(idx)"
                                        {{ $isEditable ? '' : 'disabled' }}>–</button>

                                    <!-- input tanpa spinner, tetap kirim rows[idx][qty] -->
                                    <input type="text" inputmode="numeric" pattern="\d*"
                                        class="w-16 text-center border rounded px-2 py-1" :name="`rows[${idx}][qty]`"
                                        x-model="r.qty_display" @input="syncQty(idx)" {{ $isEditable ? '' : 'disabled' }}>

                                    <button type="button" class="h-7 w-7 rounded border" @click="inc(idx)"
                                        {{ $isEditable ? '' : 'disabled' }}>+</button>
                                </div>
                            </td>

                            {{-- Total (qty × harga) --}}
                            <td class="px-3 py-2 text-right" x-text="formatRupiah(r.qty * unitPrice(r.service_id))"></td>

                            <td class="px-3 py-2 text-right">
                                <button type="button" class="px-3 py-1 text-xs rounded bg-red-600 text-white"
                                    @click="remove(idx)" {{ $isEditable ? '' : 'disabled' }}>hapus</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex items-center justify-between">
            <button type="button" class="text-sm underline" @click="add()" {{ $isEditable ? '' : 'disabled' }}>+ Tambah
                Baris</button>

            {{-- Submit --}}
            <form method="POST" action="{{ route('admin.rekap.store') }}">
                @csrf
                @if(request('d'))
                    <input type="hidden" name="d" value="{{ request('d') }}">
                @endif
                <template x-for="(r,idx) in rows" :key="'h' + idx">
                    <div class="hidden">
                        <input type="hidden" :name="`rows[${idx}][service_id]`" :value="r.service_id">
                        <input type="hidden" :name="`rows[${idx}][qty]`" :value="r.qty">
                        <input type="hidden" :name="`rows[${idx}][metode_pembayaran_id]`" :value="r.metode_pembayaran_id">
                        <input type="hidden" :name="`rows[${idx}][subtotal]`" :value="unitPrice(r.service_id)">
                        <input type="hidden" :name="`rows[${idx}][total]`" :value="r.qty * unitPrice(r.service_id)">
                    </div>
                </template>

                <button
                    class="inline-flex items-center rounded-lg bg-blue-600 text-white px-4 py-2 hover:brightness-110 disabled:opacity-50"
                    {{ $isEditable ? '' : 'disabled' }} title="{{ $isEditable ? '' : 'Hanya bisa input/edit di tanggal hari ini atau kemarin' }}">
                    Submit Omset
                </button>
            </form>
        </div>
    </section>

    {{-- ======================= FORM PENGELUARAN ======================= --}}
    <section x-data="pengeluaranForm()" class="mt-6 bg-white rounded-2xl shadow p-5" id="form-pengeluaran">
        <div class="text-lg font-bold mb-4">Tabel Pengeluaran</div>

        {{-- ERROR PENGELUARAN --}}
        @if ($errors->pengeluaran->any())
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                <ul class="list-disc pl-5">
                    @foreach ($errors->pengeluaran->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">No</th>
                        <th class="px-3 py-2 text-left">Nama Pengeluaran</th>
                        <th class="px-3 py-2 text-left">Harga</th>
                        <th class="px-3 py-2 text-center">Metode</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="
                [&_tr:nth-child(odd)]:bg-slate-50/70
                [&_tr:nth-child(even)]:bg-white
                [&_tr:hover]:bg-amber-50/40
              ">
                    <template x-for="(r,idx) in rows" :key="idx">
                        <tr class="border-t">
                            <td class="px-3 py-2" x-text="idx+1"></td>

                            <td class="px-3 py-2">
                                <input class="border rounded px-2 py-1 w-56" :name="`outs[${idx}][keterangan]`"
                                    x-model="r.keterangan" placeholder="Nama…" {{ $isEditable ? '' : 'disabled' }}>
                            </td>

                            {{-- Harga ala input saldo kartu: prefix Rp + bertitik --}}
                            <td class="px-3 py-2">
                                <div class="relative">
                                    <span
                                        class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-500 select-none">Rp</span>
                                    <input type="text" inputmode="numeric"
                                        class="w-32 text-right border rounded pl-7 pr-2 py-1" x-model="r.subtotal_display"
                                        @input="syncHarga(idx)" {{ $isEditable ? '' : 'disabled' }}>
                                    <input type="hidden" :name="`outs[${idx}][subtotal]`" :value="r.subtotal">
                                </div>
                            </td>

                            {{-- Metode: BON tidak tersedia (input dari halaman pesanan) --}}
                            <td class="px-3 py-2 text-center">
                                <select class="border rounded px-2 py-1" :name="`outs[${idx}][metode_pembayaran_id]`"
                                    x-model="r.metode_pembayaran_id" {{ $isEditable ? '' : 'disabled' }}>
                                    @foreach ($metodes as $m)
                                        @if (strtolower($m->nama) !== 'bon')
                                            <option value="{{ $m->id }}">{{ $m->nama }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>

                            <td class="px-3 py-2 text-right">
                                <button type="button" class="px-3 py-1 text-xs rounded bg-red-600 text-white"
                                    @click="remove(idx)" {{ $isEditable ? '' : 'disabled' }}>hapus</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex items-center justify-between">
            <button type="button" class="text-sm underline" @click="add()" {{ $isEditable ? '' : 'disabled' }}>+ Tambah
                Baris</button>
            <form method="POST" action="{{ route('admin.rekap.store-pengeluaran') }}">
                @csrf
                @if(request('d'))
                    <input type="hidden" name="d" value="{{ request('d') }}">
                @endif
                <template x-for="(r,idx) in rows" :key="'ph' + idx">
                    <div class="hidden">
                        <input type="hidden" :name="`outs[${idx}][keterangan]`" :value="r.keterangan">
                        <input type="hidden" :name="`outs[${idx}][subtotal]`" :value="r.subtotal">
                        <input type="hidden" :name="`outs[${idx}][metode_pembayaran_id]`"
                            :value="r.metode_pembayaran_id">
                        <input type="hidden" :name="`outs[${idx}][total]`" :value="r.subtotal">
                    </div>
                </template>

                <button
                    class="inline-flex items-center rounded-lg bg-blue-600 text-white px-4 py-2 hover:brightness-110 disabled:opacity-50"
                    {{ $isEditable ? '' : 'disabled' }}
                    title="{{ $isEditable ? '' : 'Hanya bisa input/edit di tanggal hari ini atau kemarin' }}">
                    Submit Pengeluaran
                </button>
            </form>
        </div>
    </section>

    {{-- ======================= MONITORING SALDO KARTU ======================= --}}
    <section class="mt-6 bg-white rounded-2xl shadow p-5" id="form-saldo">
        <div class="text-lg font-bold mb-4">Monitoring Saldo Kartu Laundry</div>

        {{-- ERROR SALDO KARTU --}}
        @if ($errors->saldo->any())
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                <ul class="list-disc pl-5">
                    @foreach ($errors->saldo->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            // misal di controller kamu sudah kirim $adaSaldoKemarin = true/false
            $adaSaldoKemarin = $adaSaldoKemarin ?? false;
        @endphp

        <form id="form-saldo-kartu" method="POST" action="{{ route('admin.rekap.store-saldo') }}"
            class="grid md:grid-cols-3 gap-3">
            @csrf
            @if(request('d'))
                <input type="hidden" name="d" value="{{ request('d') }}">
            @endif

            {{-- 1. Sisa Saldo --}}
            <div>
                <label class="text-sm">Sisa saldo kartu hari ini (Rp.)</label>
                <div class="relative mt-1">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">Rp</span>
                    <input id="saldo_kartu_display" type="text" inputmode="numeric" autocomplete="off"
                        class="w-full border rounded px-3 py-2 pl-9"
                        value="{{ old('saldo_kartu') ? number_format((int) old('saldo_kartu'), 0, ',', '.') : '' }}"
                        aria-label="Sisa saldo kartu (contoh: 4.000.000)" {{ $isEditable ? '' : 'disabled' }}>
                    <input id="saldo_kartu" name="saldo_kartu" type="hidden" value="{{ old('saldo_kartu') }}">
                </div>
                @error('saldo_kartu')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 2. Tampilkan input total tap hanya kalau belum ada saldo sebelumnya --}}
            @if (!$adaSaldoKemarin)
                <div>
                    <label class="text-sm">Total Tap Kartu Hari Ini (Jumlah)</label>
                    <input name="manual_total_tap" type="text" inputmode="numeric" pattern="\d*" autocomplete="off"
                        class="w-full border rounded px-3 py-2 mt-1" value="{{ old('manual_total_tap', 0) }}"
                        {{ $isEditable ? '' : 'disabled' }}>
                    @error('manual_total_tap', 'saldo')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- 3. Jumlah Tap Gagal --}}
            <div>
                <label class="text-sm">Jumlah Tap Gagal (Jumlah)</label>
                <input name="tap_gagal" type="text" inputmode="numeric" pattern="\d*" autocomplete="off"
                    class="w-full border rounded px-3 py-2 mt-1" value="{{ old('tap_gagal', 0) }}"
                    {{ $isEditable ? '' : 'disabled' }}>
                @error('tap_gagal')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-end">
                <button class="w-full md:w-auto px-5 py-3 rounded-lg bg-blue-600 text-white disabled:opacity-50 hover:brightness-110"
                    {{ $isEditable ? '' : 'disabled' }}
                    title="{{ $isEditable ? '' : 'Hanya bisa input/edit di tanggal hari ini atau kemarin' }}">
                    Submit Saldo Kartu
                </button>
            </div>
        </form>
    </section>


    {{-- ======================= SCRIPTS ======================= --}}
    <script>
        // Map service_id -> harga satuan
        const SERVICE_MAP = Object.fromEntries([
            @foreach ($services as $s)
                ['{{ $s->id }}', {{ (int) $s->harga_service }}],
            @endforeach
        ]);

        const toID = n => (Number(n) || 0).toLocaleString('id-ID');
        const onlyDigits = s => (s || '').replace(/\D/g, '');
        const formatRupiah = n => 'Rp ' + toID(n);

        // ---------- OMSET ----------
        function omsetForm() {
            return {
                rows: [{
                    service_id: '',
                    qty: 1,
                    qty_display: '1',
                    metode_pembayaran_id: '{{ $defaultNonBon }}'
                }],

                unitPrice(id) {
                    return SERVICE_MAP[String(id)] ?? 0;
                },
                formatRupiah,

                // ketik manual -> bersihkan & sinkronkan
                syncQty(i) {
                    const d = onlyDigits(this.rows[i].qty_display);
                    let n = parseInt(d || '1', 10);
                    if (!n || n < 1) n = 1;
                    this.rows[i].qty = n;
                    this.rows[i].qty_display = String(n);
                },

                // tombol +
                inc(i) {
                    const n = Math.max(1, (this.rows[i].qty || 1) + 1);
                    this.rows[i].qty = n;
                    this.rows[i].qty_display = String(n);
                },

                // tombol −
                dec(i) {
                    const n = Math.max(1, (this.rows[i].qty || 1) - 1);
                    this.rows[i].qty = n;
                    this.rows[i].qty_display = String(n);
                },

                add() {
                    this.rows.push({
                        service_id: '',
                        qty: 1,
                        qty_display: '1',
                        metode_pembayaran_id: '{{ $defaultNonBon }}'
                    });
                },
                remove(i) {
                    this.rows.splice(i, 1);
                }
            }
        }

        // ---------- PENGELUARAN ----------
        function pengeluaranForm() {
            return {
                rows: [{
                    keterangan: '',
                    subtotal: 0,
                    subtotal_display: '0',
                    metode_pembayaran_id: '{{ $defaultNonBon }}'
                }],

                formatID: toID,

                // format harga bertitik saat ketik
                syncHarga(i) {
                    const d = onlyDigits(this.rows[i].subtotal_display);
                    const n = parseInt(d || '0', 10);
                    this.rows[i].subtotal = n;
                    this.rows[i].subtotal_display = this.formatID(n);
                },

                add() {
                    this.rows.push({
                        keterangan: '',
                        subtotal: 0,
                        subtotal_display: '0',
                        metode_pembayaran_id: '{{ $defaultNonBon }}'
                    });
                },
                remove(i) {
                    this.rows.splice(i, 1);
                }
            }
        }

        // ---------- SALDO KARTU ----------
        (function() {
            const CAP = 5000000;
            const fmt = new Intl.NumberFormat('id-ID');
            const form = document.getElementById('form-saldo-kartu');
            const display = document.getElementById('saldo_kartu_display');
            const hidden = document.getElementById('saldo_kartu');

            if (!display) return;

            const sanitize = s => (s || '').replace(/[^\d]/g, '');

            function renderFromDisplay() {
                let digits = sanitize(display.value);
                if (digits.length) {
                    let n = parseInt(digits, 10) || 0;
                    if (n > CAP) n = CAP;
                    hidden.value = n;
                    display.value = fmt.format(n);
                } else {
                    hidden.value = '';
                    display.value = '';
                }
            }

            // init dari old()
            renderFromDisplay();

            display.addEventListener('input', () => {
                const wasEnd = display.selectionStart === display.value.length;
                renderFromDisplay();
                if (wasEnd) display.setSelectionRange(display.value.length, display.value.length);
            });

            form?.addEventListener('submit', () => {
                let n = parseInt(sanitize(display.value || ''), 10) || 0;
                if (n > CAP) n = CAP;
                hidden.value = n;
            });
        })();

        // ---------- Auto-scroll ke form yang error ----------
        document.addEventListener('DOMContentLoaded', () => {
            const flags = [{
                    hasErr: {!! $errors->omzet->any() ? 'true' : 'false' !!},
                    sel: '#form-omzet'
                },
                {
                    hasErr: {!! $errors->pengeluaran->any() ? 'true' : 'false' !!},
                    sel: '#form-pengeluaran'
                },
                {
                    hasErr: {!! $errors->saldo->any() ? 'true' : 'false' !!},
                    sel: '#form-saldo'
                },
            ];
            const t = flags.find(f => f.hasErr);
            if (t) {
                const el = document.querySelector(t.sel);
                if (el) el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    </script>
@endsection

