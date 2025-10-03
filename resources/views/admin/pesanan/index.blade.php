@extends('admin.layout')
@section('title','Pesanan Laundry - Qxpress Laundry')

@section('content')
<div class="bg-white p-5 rounded-xl shadow">
  <div class="text-xl font-bold mb-4">Form Input Data Pesanan</div>

  <form method="POST" action="{{ route('admin.pesanan.store') }}" class="grid md:grid-cols-2 gap-4" x-data="pelangganPicker()">
    @csrf
    <!-- {{-- NAMA (dengan datalist) --}} -->
    <div>
      <label class="text-sm">Nama Pelanggan</label>
      <input name="nama_pel"
            x-model="nama"
            @change="onNamaPicked()"
            list="pelanggan-nama-list"
            autocomplete="off"
            class="mt-1 w-full border rounded px-3 py-2" required>
      <datalist id="pelanggan-nama-list">
        @foreach($pelangganOptions as $pl)
          <option value="{{ $pl->nama_pel }}"></option>
        @endforeach
      </datalist>
    </div>

    <!-- {{-- NO HP (dengan datalist) --}} -->
    <div>
      <label class="text-sm">No. HP</label>
      <input name="no_hp_pel"
            x-model="hp"
            @change="onHpPicked()"
            list="pelanggan-hp-list"
            autocomplete="off"
            class="mt-1 w-full border rounded px-3 py-2" required>
      <datalist id="pelanggan-hp-list">
        @foreach($pelangganOptions as $pl)
          <option value="{{ $pl->no_hp_pel }}"></option>
        @endforeach
      </datalist>
    </div>

    <!-- Pilih Layanan -->
    <div>
      <label class="text-sm">Pilih Layanan</label>
      <select name="service_id" class="mt-1 w-full border rounded px-3 py-2" required>
        <option value="">-</option>
        @foreach($services as $s)
          <option value="{{ $s->id }}" @selected(old('service_id')==$s->id)>
            {{ $s->nama_service }} (Rp {{ number_format($s->harga_service,0,',','.') }})
          </option>
        @endforeach
      </select>
    </div>

    <!-- {{-- KUANTITAS --}} -->
    <div>
      <label class="text-sm">Kuantitas</label>
      <input name="qty" type="number" min="1" value="{{ old('qty',1) }}" class="mt-1 w-full border rounded px-3 py-2" required>
    </div>

    <!-- {{-- METODE PEMBAYARAN --}} -->
    <div>
      <label class="text-sm">Metode Pembayaran</label>
      <select name="metode_pembayaran_id" class="mt-1 w-full border rounded px-3 py-2" required>
        @foreach($metodes as $m)
          <option value="{{ $m->id }}" @selected(old('metode_pembayaran_id')==$m->id)>{{ ucfirst($m->nama) }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-sm">Status Pesanan</label>
      <select name="status_awal" class="mt-1 w-full border rounded px-3 py-2" required>
        <option @selected(old('status_awal')==='Diproses')>Diproses</option>
        <option @selected(old('status_awal')==='Selesai')>Selesai</option>
      </select>
    </div>

    <div class="md:col-span-2">
      <button class="px-5 py-3 rounded-lg bg-gray-800 text-white">Simpan Pesanan</button>
    </div>
  </form>
</div>

<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="text-xl font-bold mb-4">Tabel Pesanan Laundry</div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">Pelanggan</th>
          <th class="px-3 py-2 text-left">No HP</th>
          <th class="px-3 py-2 text-left">Layanan</th>
          <th class="px-3 py-2 text-center">Qty</th>
          <th class="px-3 py-2 text-right">Total</th>
          <th class="px-3 py-2 text-center">Pembayaran</th> {{-- ditentukan dari metode --}}
          <th class="px-3 py-2 text-left">Status</th>
          <th class="px-3 py-2 text-left">Update Terakhir</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        @foreach($pesanan as $p)
          @php
            $qty     = max(1, (int)($p->qty ?? 1));
            $harga   = (int)($p->service->harga_service ?? 0);
            $total   = $qty * $harga;
            $metode  = $p->metode->nama ?? null; // relasi ke metode_pembayaran
            $isLunas = in_array($metode, ['tunai','qris']);
          @endphp
          <tr class="border-t">
            <td class="px-3 py-2">{{ $p->nama_pel }}</td>
            <td class="px-3 py-2">{{ $p->no_hp_pel }}</td>
            <td class="px-3 py-2">{{ $p->service->nama_service ?? '-' }}</td>

            {{-- Qty --}}
            <td class="px-3 py-2 text-center">{{ $qty }}</td>

            {{-- Total = harga_service * qty --}}
            <td class="px-3 py-2 text-right">Rp {{ number_format($total,0,',','.') }}</td>

            {{-- Status Pembayaran dari metode --}}
            <td class="px-3 py-2 text-center">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                {{ $isLunas ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-yellow-50 text-yellow-700 border border-yellow-200' }}">
                {{ $isLunas ? 'Lunas' : 'Belum Lunas' }}
              </span>
            </td>

            <td class="px-3 py-2">
              <form method="POST" action="{{ route('admin.status.store') }}" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="pesanan_id" value="{{ $p->id }}">
                <select name="keterangan" class="border rounded px-2 py-1 text-xs">
                  @php $ops = ['Diproses','Selesai']; @endphp
                  @foreach($ops as $op)
                    <option @selected(optional($p->statuses->first())->keterangan === $op)>{{ $op }}</option>
                  @endforeach
                </select>
                <button class="text-xs px-2 py-1 rounded bg-gray-800 text-white">Ubah</button>
              </form>
            </td>

            <td class="px-3 py-2">
              {{ optional($p->statuses->first())->created_at?->format('d/m/y H:i') }}
            </td>
            <td class="px-3 py-2 text-right">
              <form method="POST" action="{{ route('admin.pesanan.destroy',$p) }}">
                @csrf @method('DELETE')
                <button class="text-xs underline text-red-600">Sembunyikan</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-4">{{ $pesanan->links() }}</div>
</div>

<script>
  // Map dari histori: nama -> hp, hp -> nama
  const NAME_TO_HP = {
    @foreach($pelangganOptions as $pl)
      {!! json_encode($pl->nama_pel) !!}: {!! json_encode($pl->no_hp_pel) !!},
    @endforeach
  };
  const HP_TO_NAME = {
    @foreach($pelangganOptions as $pl)
      {!! json_encode($pl->no_hp_pel) !!}: {!! json_encode($pl->nama_pel) !!},
    @endforeach
  };

  function pelangganPicker() {
    return {
      nama: @json(old('nama_pel','')),
      hp:   @json(old('no_hp_pel','')),
      onNamaPicked(){
        // kalau nama dikenal & HP masih kosong, auto isi
        if (this.nama && !this.hp && NAME_TO_HP[this.nama]) {
          this.hp = NAME_TO_HP[this.nama];
        }
      },
      onHpPicked(){
        // kalau HP dikenal & nama masih kosong, auto isi
        if (this.hp && !this.nama && HP_TO_NAME[this.hp]) {
          this.nama = HP_TO_NAME[this.hp];
        }
      }
    }
  }
</script>

@endsection
