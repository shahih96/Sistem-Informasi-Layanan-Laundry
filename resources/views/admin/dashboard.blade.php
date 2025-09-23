@extends('admin.layout')

@section('title','Dashboard Qxpress Laundry')

@section('content')
<div class="grid md:grid-cols-4 gap-4">
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Pesanan Hari ini</div>
    <div class="mt-2 text-3xl font-bold">{{ $totalPesananHariIni }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Pendapatan Hari ini</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($pendapatanHariIni,0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Pesanan Diproses</div>
    <div class="mt-2 text-3xl font-bold">{{ $pesananDiproses }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Pesanan Selesai</div>
    <div class="mt-2 text-3xl font-bold">{{ $pesananSelesai }}</div>
  </div>
</div>

<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="font-semibold mb-3">Riwayat Pesanan Terbaru</div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">Pelanggan</th>
          <th class="px-3 py-2 text-left">Service</th>
          <th class="px-3 py-2 text-left">Status Terakhir</th>
          <th class="px-3 py-2 text-left">Update</th>
        </tr>
      </thead>
      <tbody>
        @foreach($riwayat as $r)
        <tr class="border-t">
          <td class="px-3 py-2">{{ $r->nama_pel }}</td>
          <td class="px-3 py-2">{{ $r->service->nama_service ?? '-' }}</td>
          <td class="px-3 py-2">{{ optional($r->statuses->first())->keterangan ?? '-' }}</td>
          <td class="px-3 py-2">{{ optional($r->statuses->first())->created_at?->format('d/m/y H:i') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- Ringkasan Keuangan --}}
<div class="mt-8 grid md:grid-cols-3 gap-4">
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Cash Laundry</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($saldoKas,0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow col-span-2">
    <div class="font-semibold mb-2">Omzet Bulan Ini (ringkas)</div>
    <div class="text-sm text-gray-500">Total hari: {{ $omzetPerHari->count() }}, total: Rp {{ number_format($omzetPerHari->sum('omzet'),0,',','.') }}</div>
  </div>
</div>
@endsection
