@extends('admin.layout')
@section('title','Input rekap keuangan – Qxpress Laundry')

@section('content')
  <div class="flex items-center justify-between">
    <div class="text-xl md:text-2xl font-bold">Input rekap keuangan – Qxpress Laundry</div>
    <a href="{{ route('admin.rekap.index') }}" class="text-sm underline">Kembali</a>
  </div>

  {{-- Form Input Omset --}}
  <section x-data="omsetForm()" class="mt-6 bg-white rounded-2xl shadow p-5">
    <div class="text-lg font-bold mb-4">Tabel Omset</div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-3 py-2 text-left">No</th>
            <th class="px-3 py-2 text-left">Nama Layanan</th>
            <th class="px-3 py-2 text-center">Kuantitas</th>
            <th class="px-3 py-2 text-right">Harga</th>    {{-- auto dari services --}}
            <th class="px-3 py-2 text-center">Metode</th>
            <th class="px-3 py-2 text-right">Total</th>    {{-- qty x harga --}}
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
                  <button type="button" class="h-7 w-7 rounded border"
                          @click="r.qty = Math.max(1, r.qty-1)">–</button>
                  <input type="number" min="1" class="w-16 text-center border rounded px-2 py-1"
                        :name="`rows[${idx}][qty]`" x-model.number="r.qty">
                  <button type="button" class="h-7 w-7 rounded border"
                          @click="r.qty++">+</button>
                </div>
              </td>

              {{-- HARGA (auto) --}}
              <td class="px-3 py-2 text-right"
                  x-text="formatRupiah(unitPrice(r.service_id))"></td>

              <td class="px-3 py-2 text-center">
                <select class="border rounded px-2 py-1"
                        :name="`rows[${idx}][metode_pembayaran_id]`" x-model="r.metode_pembayaran_id">
                  @foreach($metodes as $m)
                    <option value="{{ $m->id }}">{{ $m->nama }}</option>
                  @endforeach
                </select>
              </td>

              {{-- TOTAL (qty × harga) --}}
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

        {{-- sinkronkan nilai ke hidden input agar controller tetap menerima subtotal & total --}}
        <template x-for="(r,idx) in rows" :key="'h'+idx">
          <div class="hidden">
            <input type="hidden" :name="`rows[${idx}][service_id]`" :value="r.service_id">
            <input type="hidden" :name="`rows[${idx}][qty]`" :value="r.qty">
            {{-- subtotal = HARGA SATUAN dari services --}}
            <input type="hidden" :name="`rows[${idx}][subtotal]`" :value="unitPrice(r.service_id)">
            <input type="hidden" :name="`rows[${idx}][metode_pembayaran_id]`" :value="r.metode_pembayaran_id">
            {{-- total = qty × harga --}}
            <input type="hidden" :name="`rows[${idx}][total]`" :value="r.qty * unitPrice(r.service_id)">
          </div>
        </template>

        <button class="inline-flex items-center rounded-lg bg-gray-800 text-white px-4 py-2 hover:brightness-110">
          Submit Omset
        </button>
      </form>
    </div>
  </section>


  {{-- Form Input Pengeluaran --}}
  <section x-data="pengeluaranForm()" class="mt-6 bg-white rounded-2xl shadow p-5">
    <div class="text-lg font-bold mb-4">Tabel Pengeluaran</div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-3 py-2 text-left">No</th>
            <th class="px-3 py-2 text-left">Nama Pengeluaran</th>
            <th class="px-3 py-2 text-left">Harga</th>
            <th class="px-3 py-2 text-center">Tanggal</th>
            <th class="px-3 py-2 text-center">Metode</th>
            <th class="px-3 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <template x-for="(r,idx) in rows" :key="idx">
            <tr class="border-t">
              <td class="px-3 py-2" x-text="idx+1"></td>
              <td class="px-3 py-2">
                <input class="border rounded px-2 py-1 w-56" :name="`outs[${idx}][keterangan]`" x-model="r.keterangan" placeholder="Nama…">
              </td>
              <td class="px-3 py-2">
                <input type="number" class="w-28 text-right border rounded px-2 py-1" :name="`outs[${idx}][subtotal]`" x-model.number="r.subtotal">
              </td>
              <td class="px-3 py-2 text-center">
                <input type="date" class="border rounded px-2 py-1" :name="`outs[${idx}][tanggal]`" x-model="r.tanggal">
              </td>
              <td class="px-3 py-2 text-center">
                <select class="border rounded px-2 py-1" :name="`outs[${idx}][metode_pembayaran_id]`" x-model="r.metode_pembayaran_id">
                  @foreach($metodes as $m)
                    <option value="{{ $m->id }}">{{ $m->nama }}</option>
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
        <template x-for="(r,idx) in rows" :key="'ph'+idx">
          <div class="hidden">
            <input type="hidden" :name="`outs[${idx}][keterangan]`" :value="r.keterangan">
            <input type="hidden" :name="`outs[${idx}][subtotal]`" :value="r.subtotal">
            <input type="hidden" :name="`outs[${idx}][tanggal]`" :value="r.tanggal">
            <input type="hidden" :name="`outs[${idx}][metode_pembayaran_id]`" :value="r.metode_pembayaran_id">
            <input type="hidden" :name="`outs[${idx}][total]`" :value="r.subtotal">
          </div>
        </template>
        <button class="inline-flex items-center rounded-lg bg-gray-800 text-white px-4 py-2 hover:brightness-110">
          Submit Pengeluaran
        </button>
      </form>
    </div>
  </section>

  {{-- Monitoring saldo kartu --}}
  <section class="mt-6 bg-white rounded-2xl shadow p-5">
    <div class="text-lg font-bold mb-4">Monitoring Saldo Kartu Laundry</div>
    <form method="POST" action="{{ route('admin.rekap.store-saldo') }}" class="grid md:grid-cols-3 gap-4">
      @csrf
      <div>
        <label class="text-sm">Sisa saldo kartu hari ini (Rp.)</label>
        <input name="saldo_kartu" type="number" class="w-full border rounded px-3 py-2" value="{{ old('saldo_kartu',) }}">
      </div>
      <div>
        <label class="text-sm">Jumlah Tap Gagal</label>
        <input name="tap_gagal" type="number" class="w-full border rounded px-3 py-2" value="{{ old('tap_gagal',) }}">
      </div>
      <div class="flex items-end">
        <button class="w-full md:w-auto px-5 py-3 rounded-lg bg-gray-800 text-white">Submit Saldo Kartu</button>
      </div>
    </form>
  </section>


  <script>
    // siapkan data service -> harga dari PHP ke JS
    const SERVICE_MAP = Object.fromEntries([
      @foreach($services as $s)
        ['{{ $s->id }}', {{ (int)$s->harga_service }}],
      @endforeach
    ]);

    function formatRupiah(n){
      n = Number(n||0);
      return 'Rp ' + n.toLocaleString('id-ID');
    }

    function omsetForm() {
      return {
        rows: [{
          service_id: '',
          qty: 1,
          metode_pembayaran_id: '{{ optional($metodes->first())->id }}'
        }],
        unitPrice(id){ return SERVICE_MAP[String(id)] ?? 0; },
        formatRupiah,
        add(){
          this.rows.push({
            service_id: '',
            qty: 1,
            metode_pembayaran_id: '{{ optional($metodes->first())->id }}'
          });
        },
        remove(i){ this.rows.splice(i,1); }
      }
    }


    function pengeluaranForm() {
      return {
        rows: [{
          keterangan:'',
          subtotal:0,
          tanggal:new Date().toISOString().slice(0,10),
          metode_pembayaran_id:'{{ optional($metodes->first())->id }}'
        }],
        add(){
          this.rows.push({
            keterangan:'',
            subtotal:0,
            tanggal:new Date().toISOString().slice(0,10),
            metode_pembayaran_id:'{{ optional($metodes->first())->id }}'
          });
        },
        remove(i){ this.rows.splice(i,1); }
      }
    }
  </script>

@endsection
