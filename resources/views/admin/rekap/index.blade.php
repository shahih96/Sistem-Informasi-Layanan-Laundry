@extends('admin.layout')
@section('title','Rekap Keuangan – Qxpress Laundry')

@section('content')
{{-- Ringkasan Keuangan (atas) --}}
<div class="grid md:grid-cols-4 gap-4">
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Cash Laundry</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalCash,0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Bon Pelanggan</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalPiutang ?? 0,0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Fee Karyawan</div>
    <div class="mt-2 text-3xl font-bold"> Rp {{ number_format($totalFee ?? 0, 0, ',', '.') }}</div>
    <div class="text-xs text-gray-500 mt-1">
      (Lipat: Rp {{ number_format($feeLipat,0,',','.') }},
      Setrika: Rp {{ number_format($feeSetrika,0,',','.') }})
    </div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Sisa Saldo Kartu</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($saldoKartu,0,',','.') }}</div>
  </div>
</div>

{{-- Tabel Omset --}}
<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="font-semibold mb-3">Tabel Omset</div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2">No</th>
          <th class="px-3 py-2 text-left">Nama Layanan</th>
          <th class="px-3 py-2 text-center">Kuantitas</th>
          <th class="px-3 py-2 text-right">Harga</th>
          <th class="px-3 py-2 text-center">Metode</th>
          <th class="px-3 py-2 text-right">Total</th>
          <th class="px-3 py-2 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach($omset as $i => $r)
          <tr class="border-t">
            <td class="px-3 py-2">{{ ($omset->currentPage()-1)*$omset->perPage() + $loop->iteration }}</td>
            <td class="px-3 py-2">{{ $r->service->nama_service ?? '-' }}</td>
            <td class="px-3 py-2 text-center">{{ $r->qty }}</td>
            <td class="px-3 py-2 text-right">Rp {{ number_format($r->subtotal,0,',','.') }}</td>
            <td class="px-3 py-2 text-center">{{ $r->metode->nama ?? '-' }}</td>
            <td class="px-3 py-2 text-right">Rp {{ number_format($r->total,0,',','.') }}</td>
            <td class="px-3 py-2 text-right">
              {{-- Hapus 1 grup omzet: service_id + metode_pembayaran_id --}}
              <form method="POST" action="{{ route('admin.rekap.destroy-group') }}"
                    onsubmit="return confirm('Hapus seluruh baris pada grup ini?')">
                @csrf @method('DELETE')
                <input type="hidden" name="service_id" value="{{ $r->service_id }}">
                <input type="hidden" name="metode_pembayaran_id" value="{{ $r->metode_pembayaran_id }}">
                {{-- Jika ingin batasi per tanggal, kirimkan juga hidden "tanggal" --}}
                {{-- <input type="hidden" name="tanggal" value="{{ optional($r->created_at)->toDateString() }}"> --}}
                <button class="text-xs underline text-red-600">Hapus</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-2">{{ $omset->links('pagination::tailwind') }}</div>
</div>

{{-- Tabel Pengeluaran --}}
<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="font-semibold mb-3">Tabel Pengeluaran</div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2">No</th>
          <th class="px-3 py-2 text-left">Nama</th>
          <th class="px-3 py-2 text-center">Metode</th>
          <th class="px-3 py-2 text-right">Harga</th>
          <th class="px-3 py-2 text-center">Tanggal</th>
          <th class="px-3 py-2 text-right">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @foreach($pengeluaran as $i => $r)
          <tr class="border-t">
            <td class="px-3 py-2">{{ $pengeluaran->firstItem() + $i }}</td>
            <td class="px-3 py-2">{{ $r->keterangan ?? '-' }}</td>
            <td class="px-3 py-2 text-center">{{ $r->metode->nama ?? '-' }}</td>
            <td class="px-3 py-2 text-right">Rp {{ number_format($r->total,0,',','.') }}</td>
            <td class="px-3 py-2 text-center">{{ optional($r->created_at)->format('d/m/Y') }}</td>
            <td class="px-3 py-2 text-right">
              <form method="POST" action="{{ route('admin.rekap.destroy', $r->id) }}"
                    onsubmit="return confirm('Hapus baris ini?')">
                @csrf @method('DELETE')
                <button class="text-xs underline text-red-600">Hapus</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-2">{{ $pengeluaran->links('pagination::tailwind') }}</div>
</div>

<!-- {{-- Tabel Bon Pelanggan --}} -->
<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="font-semibold mb-3">Tabel Bon Pelanggan</div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2">No</th>
          <th class="px-3 py-2 text-left">Nama Pelanggan</th>
          <th class="px-3 py-2 text-left">Layanan</th>
          <th class="px-3 py-2 text-center">Kuantitas</th>
          <th class="px-3 py-2 text-right">Total</th>
          <th class="px-3 py-2 text-center">Tanggal Masuk</th>
          <th class="px-3 py-2 text-center">Pembayaran</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bon as $i => $p)
          @php
            $qty   = max(1, (int)($p->qty ?? 1));
            $harga = (int)($p->service->harga_service ?? 0);   // harga satuan dari layanan
            $total = $qty * $harga;
          @endphp
          <tr class="border-t">
            <td class="px-3 py-2">
              {{ ($bon->currentPage()-1)*$bon->perPage() + $loop->iteration }}
            </td>
            <td class="px-3 py-2">
              <div class="font-medium">{{ $p->nama_pel }}</div>
              <div class="text-xs text-gray-500">{{ $p->no_hp_pel }}</div>
            </td>
            <td class="px-3 py-2">{{ $p->service->nama_service ?? '-' }}</td>

            <td class="px-3 py-2 text-center">{{ $qty }}</td>
            <td class="px-3 py-2 text-right">Rp {{ number_format($total,0,',','.') }}</td>

            <td class="px-3 py-2 text-center">{{ optional($p->created_at)->format('d/m/Y H:i') }}</td>
            <td class="px-3 py-2 text-center">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-50 text-yellow-700 border border-yellow-200">
                Belum Lunas
              </span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="px-3 py-4 text-center text-gray-500">Tidak ada data bon pelanggan.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="mt-2">{{ $bon->links('pagination::tailwind') }}</div>
</div>

<!-- {{-- Tombol Input Rekap --}} -->
<div class="mt-6 text-right">
  <a href="{{ route('admin.rekap.input') }}"
     class="inline-flex items-center gap-2 rounded-lg bg-gray-800 text-white px-4 py-2 hover:brightness-110">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Input & Update Rekap
  </a>
</div>

@endsection
