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
    <div class="text-sm opacity-70">Total Piutang Pelanggan</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalPiutang ?? 0,0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Fee Karyawan</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format((optional($fee)->fee_lipat + optional($fee)->fee_setrika),0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Sisa Saldo Kartu</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($saldoKartu,0,',','.') }}</div>
  </div>
</div>

{{-- Tombol Input Rekap --}}
<div class="mt-6">
  <a href="{{ route('admin.rekap.input') }}"
     class="inline-flex items-center gap-2 rounded-lg bg-gray-800 text-white px-4 py-2 hover:brightness-110">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Input & Update Rekap
  </a>
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
        </tr>
      </thead>
      <tbody>
        @foreach($omset as $i => $r)
          <tr class="border-t">
            <td class="px-3 py-2">{{ $omset->firstItem() + $i }}</td>
            <td class="px-3 py-2">{{ $r->service->nama_service ?? '-' }}</td>
            <td class="px-3 py-2 text-center">{{ $r->qty }}</td>
            <td class="px-3 py-2 text-right">Rp {{ number_format($r->subtotal,0,',','.') }}</td>
            <td class="px-3 py-2 text-center">{{ $r->metode->nama ?? '-' }}</td>
            <td class="px-3 py-2 text-right">Rp {{ number_format($r->total,0,',','.') }}</td>
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
          <th class="px-3 py-2 text-right">Harga</th>
          <th class="px-3 py-2 text-center">Tanggal</th>
          <th class="px-3 py-2 text-center">Metode</th>
          <th class="px-3 py-2 text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($pengeluaran as $i => $r)
          <tr class="border-t">
            <td class="px-3 py-2">{{ $pengeluaran->firstItem() + $i }}</td>
            <td class="px-3 py-2">{{ $r->keterangan ?? 'Lain-lain' }}</td>
            <td class="px-3 py-2 text-right">Rp {{ number_format($r->subtotal,0,',','.') }}</td>
            <td class="px-3 py-2 text-center">{{ $r->created_at?->format('d/m/Y') }}</td>
            <td class="px-3 py-2 text-center">{{ $r->metode->nama ?? '-' }}</td>
            <td class="px-3 py-2 text-right">Rp {{ number_format($r->total,0,',','.') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-2">{{ $pengeluaran->links('pagination::tailwind') }}</div>
</div>
@endsection
