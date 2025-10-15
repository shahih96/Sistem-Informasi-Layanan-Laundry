@extends('admin.layout')
@section('title','Input rekap keuangan – Qxpress Laundry')

@php
  // Metode default selain "bon"
  $defaultNonBon = optional(
    $metodes->first(fn($m) => strtolower($m->nama) !== 'bon')
  )->id ?? optional($metodes->first())->id;
@endphp

@section('content')
  <div class="flex items-center justify-between">
    <div class="text-xl md:text-2xl font-bold">Input rekap keuangan – Qxpress Laundry</div>
    <a href="{{ route('admin.rekap.index') }}" class="text-sm underline">Kembali</a>
  </div>

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
            <th class="px-3 py-2 text-center">Kuantitas</th>
            <th class="px-3 py-2 text-right">Harga</th>
            <th class="px-3 py-2 text-center">Metode</th>
            <th class="px-3 py-2 text-right">Total</th>
            <th class="px-3 py-2"></th>
          </tr>
        </thead>

        <tbody>
          <template x-for="(r,idx) in rows" :key="idx">
            <tr class="border-t">
              <td class="px-3 py-2" x-text="idx+1"></td>

              <td class="px-3 py-2">
                <select class="border rounded px-2 py-1 w-56"
                        :name="`rows[${idx}][service_id]`" x-model="r.service_id">
                  <option value="">- Pilih Layanan -</option>
                  @foreach($services as $s)
                    <option value="{{ $s->id }}">{{ $s->nama_service }}</option>
                  @endforeach
                </select>
              </td>

              <td class="px-3 py-2">
                <div class="flex items-center justify-center gap-2">
                  <button type="button" class="h-7 w-7 rounded border" @click="dec(idx)">–</button>

                  <!-- input tanpa spinner, tetap kirim rows[idx][qty] -->
                  <input type="text" inputmode="numeric" pattern="\d*"
                        class="w-16 text-center border rounded px-2 py-1"
                        :name="`rows[${idx}][qty]`"
                        x-model="r.qty_display"
                        @input="syncQty(idx)">

                  <button type="button" class="h-7 w-7 rounded border" @click="inc(idx)">+</button>
                </div>
              </td>

              {{-- Harga satuan (auto dari services) --}}
              <td class="px-3 py-2 text-right"
                  x-text="formatRupiah(unitPrice(r.service_id))"></td>

              {{-- Metode --}}
              <td class="px-3 py-2 text-center">
                <select class="border rounded px-2 py-1"
                        :name="`rows[${idx}][metode_pembayaran_id]`" x-model="r.metode_pembayaran_id">
                  @foreach($metodes as $m)
                    <option value="{{ $m->id }}">{{ $m->nama }}</option>
                  @endforeach
                </select>
              </td>

              {{-- Total (qty × harga) --}}
              <td class="px-3 py-2 text-right"
                  x-text="formatRupiah(r.qty * unitPrice(r.service_id))"></td>

              <td class="px-3 py-2 text-right">
                <button type="button" class="text-xs underline text-red-600" @click="remove(idx)">hapus</button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center justify-between">
      <button type="button" class="text-sm underline" @click="add()">+ Tambah Baris</button>

      {{-- Submit --}}
      <form method="POST" action="{{ route('admin.rekap.store') }}">
        @csrf

        {{-- mirror SEMUA field yang dibutuhkan controller --}}
        <template x-for="(r,idx) in rows" :key="'h'+idx">
          <div class="hidden">
            <input type="hidden" :name="`rows[${idx}][service_id]`"            :value="r.service_id">
            <input type="hidden" :name="`rows[${idx}][qty]`"                   :value="r.qty">
            <input type="hidden" :name="`rows[${idx}][metode_pembayaran_id]`"  :value="r.metode_pembayaran_id">
            <input type="hidden" :name="`rows[${idx}][subtotal]`"              :value="unitPrice(r.service_id)">
            <input type="hidden" :name="`rows[${idx}][total]`"                 :value="r.qty * unitPrice(r.service_id)">
          </div>
        </template>

        <button class="inline-flex items-center rounded-lg bg-gray-800 text-white px-4 py-2 hover:brightness-110">
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
        <tbody>
          <template x-for="(r,idx) in rows" :key="idx">
            <tr class="border-t">
              <td class="px-3 py-2" x-text="idx+1"></td>

              <td class="px-3 py-2">
                <input class="border rounded px-2 py-1 w-56"
                       :name="`outs[${idx}][keterangan]`"
                       x-model="r.keterangan"
                       placeholder="Nama…">
              </td>

              {{-- Harga ala input saldo kartu: prefix Rp + bertitik --}}
              <td class="px-3 py-2">
                <div class="relative">
                  <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-500 select-none">Rp</span>
                  <input type="text" inputmode="numeric"
                         class="w-32 text-right border rounded pl-7 pr-2 py-1"
                         x-model="r.subtotal_display"
                         @input="syncHarga(idx)">
                  <input type="hidden" :name="`outs[${idx}][subtotal]`" :value="r.subtotal">
                </div>
              </td>

              {{-- Metode: sembunyikan opsi BON --}}
              <td class="px-3 py-2 text-center">
                <select class="border rounded px-2 py-1"
                        :name="`outs[${idx}][metode_pembayaran_id]`" x-model="r.metode_pembayaran_id">
                  @foreach($metodes as $m)
                    @if(strtolower($m->nama) !== 'bon')
                      <option value="{{ $m->id }}">{{ $m->nama }}</option>
                    @endif
                  @endforeach
                </select>
              </td>

              <td class="px-3 py-2 text-right">
                <button type="button" class="text-xs underline text-red-600" @click="remove(idx)">hapus</button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center justify-between">
      <button type="button" class="text-sm underline" @click="add()">+ Tambah Baris</button>
      <form method="POST" action="{{ route('admin.rekap.store-pengeluaran') }}">
        @csrf

        {{-- mirror SEMUA field yang dibutuhkan controller --}}
        <template x-for="(r,idx) in rows" :key="'ph'+idx">
          <div class="hidden">
            <input type="hidden" :name="`outs[${idx}][keterangan]`"           :value="r.keterangan">
            <input type="hidden" :name="`outs[${idx}][subtotal]`"             :value="r.subtotal">
            {{-- kamu sudah hilangkan tanggal di UI; biarkan kosong (nullable) --}}
            <input type="hidden" :name="`outs[${idx}][metode_pembayaran_id]`" :value="r.metode_pembayaran_id">
            <input type="hidden" :name="`outs[${idx}][total]`"                :value="r.subtotal">
          </div>
        </template>

        <button class="inline-flex items-center rounded-lg bg-gray-800 text-white px-4 py-2 hover:brightness-110">
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

    <form id="form-saldo-kartu" method="POST" action="{{ route('admin.rekap.store-saldo') }}" class="grid md:grid-cols-3 gap-3">
      @csrf
      <div>
        <label class="text-sm">Sisa saldo kartu hari ini (Rp.)</label>
        <div class="relative mt-1">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">Rp</span>
          <input id="saldo_kartu_display" type="text" inputmode="numeric" autocomplete="off"
                 class="w-full border rounded px-3 py-2 pl-9"
                 value="{{ old('saldo_kartu') ? number_format((int)old('saldo_kartu'),0,',','.') : '' }}"
                 aria-label="Sisa saldo kartu (contoh: 4.000.000)">
          <input id="saldo_kartu" name="saldo_kartu" type="hidden" value="{{ old('saldo_kartu') }}">
        </div>
        @error('saldo_kartu')
          <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label class="text-sm">Jumlah Tap Gagal</label>
        <input name="tap_gagal" type="text" inputmode="numeric" pattern="\d*"
               autocomplete="off" class="w-full border rounded px-3 py-2"
               value="{{ old('tap_gagal', 0) }}">
        @error('tap_gagal')
          <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div class="flex items-end">
        <button class="w-full md:w-auto px-5 py-3 rounded-lg bg-gray-800 text-white">Submit Saldo Kartu</button>
      </div>
    </form>
  </section>

  {{-- ======================= SCRIPTS ======================= --}}
  <script>
  // Map service_id -> harga satuan
  const SERVICE_MAP = Object.fromEntries([
    @foreach($services as $s)
      ['{{ $s->id }}', {{ (int)$s->harga_service }}],
    @endforeach
  ]);

  const toID = n => (Number(n)||0).toLocaleString('id-ID');
  const onlyDigits = s => (s||'').replace(/\D/g,'');
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

      unitPrice(id){ return SERVICE_MAP[String(id)] ?? 0; },
      formatRupiah,

      // ketik manual -> bersihkan & sinkronkan
      syncQty(i){
        const d = onlyDigits(this.rows[i].qty_display);
        let n = parseInt(d || '1', 10);
        if (!n || n < 1) n = 1;
        this.rows[i].qty = n;
        this.rows[i].qty_display = String(n);
      },

      // tombol +
      inc(i){
        const n = Math.max(1, (this.rows[i].qty || 1) + 1);
        this.rows[i].qty = n;
        this.rows[i].qty_display = String(n);
      },

      // tombol −
      dec(i){
        const n = Math.max(1, (this.rows[i].qty || 1) - 1);
        this.rows[i].qty = n;
        this.rows[i].qty_display = String(n);
      },

      add(){
        this.rows.push({
          service_id: '',
          qty: 1,
          qty_display: '1',
          metode_pembayaran_id: '{{ $defaultNonBon }}'
        });
      },
      remove(i){ this.rows.splice(i,1); }
    }
  }

  // ---------- PENGELUARAN ----------
  function pengeluaranForm() {
    return {
      rows: [{
        keterangan:'', subtotal:0, subtotal_display:'0',
        metode_pembayaran_id:'{{ $defaultNonBon }}'
      }],

      formatID: toID,

      // format harga bertitik saat ketik
      syncHarga(i){
        const d = onlyDigits(this.rows[i].subtotal_display);
        const n = parseInt(d || '0', 10);
        this.rows[i].subtotal = n;
        this.rows[i].subtotal_display = this.formatID(n);
      },

      add(){
        this.rows.push({
          keterangan:'', subtotal:0, subtotal_display:'0',
          metode_pembayaran_id:'{{ $defaultNonBon }}'
        });
      },
      remove(i){ this.rows.splice(i,1); }
    }
  }

  // ---------- SALDO KARTU ----------
  (function(){
    const CAP = 5000000;
    const fmt = new Intl.NumberFormat('id-ID');
    const form = document.getElementById('form-saldo-kartu');
    const display = document.getElementById('saldo_kartu_display');
    const hidden  = document.getElementById('saldo_kartu');

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

    form.addEventListener('submit', () => {
      let n = parseInt(sanitize(display.value || ''), 10) || 0;
      if (n > CAP) n = CAP;
      hidden.value = n;
    });
  })();

  // ---------- Auto-scroll ke form yang error ----------
  document.addEventListener('DOMContentLoaded', () => {
    const flags = [
      {hasErr: @json($errors->omzet->any()),        sel: '#form-omzet'},
      {hasErr: @json($errors->pengeluaran->any()),  sel: '#form-pengeluaran'},
      {hasErr: @json($errors->saldo->any()),        sel: '#form-saldo'},
    ];
    const t = flags.find(f => f.hasErr);
    if (t) {
      const el = document.querySelector(t.sel);
      if (el) el.scrollIntoView({behavior: 'smooth', block: 'start'});
    }
  });
  </script>
@endsection
