@extends('admin.layout')
@section('title','Rekap Keuangan – Qxpress Laundry')

@section('content')

<!-- {{-- Filter Tanggal --}} -->
<div class="mb-4 flex flex-wrap items-center gap-2">
  <form method="GET" action="{{ route('admin.rekap.index') }}" class="flex items-center gap-2">
    <input
      type="date"
      name="d"
      value="{{ request('d', optional($day ?? now())->toDateString()) }}"
      class="border rounded-lg px-3 py-2"
    />
    <button class="px-4 py-2 rounded-lg bg-gray-800 text-white hover:brightness-110">
      Tampilkan
    </button>
  </form>

  <div class="flex items-center gap-2">
    <a href="{{ route('admin.rekap.index', ['d' => now()->toDateString()]) }}"
       class="px-3 py-2 text-sm rounded-lg bg-blue-600 text-white hover:brightness-110">
      Hari Ini
    </a>
  </div>

  <div class="text-sm text-gray-600 ml-auto">
    Menampilkan rekap tanggal:
    <strong>{{ \Carbon\Carbon::parse(request('d', optional($day ?? now())->toDateString()))->translatedFormat('l, d M Y') }}</strong>
  </div>
</div>

<!-- {{-- Ringkasan Keuangan (atas) --}} -->
<div class="grid md:grid-cols-4 gap-4">
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Cash Laundry (Akumulasi)</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalCash,0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Bon Pelanggan (Akumulasi)</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalPiutang ?? 0,0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Fee Karyawan Hari ini</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalFee ?? 0,0,',','.') }}</div>
    <div class="text-xs text-gray-500 mt-1">
      (Lipat {{ $kgLipatTerbayar }} Kg: Rp {{ number_format($feeLipat,0,',','.') }},
      Setrika {{ $setrikaKgTotal }} Kg: Rp {{ number_format($feeSetrika,0,',','.') }})
    </div>
    <div class="text-[11px] text-gray-500 mt-1">
    Sisa kg lipat yang dibawa ke besok:
    <strong>{{ $sisaLipatBaru ?? 0 }} Kg</strong>
    </div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Pendapatan Hari Ini</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalOmzetHariIni,0,',','.') }}</div>
  </div>
</div>
<div class="mt-4 grid md:grid-cols-3 gap-4">
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Sisa Saldo Kartu Hari Ini</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($saldoKartu,0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Tap Kartu Hari Ini</div>
    <div class="mt-2 text-3xl font-bold">{{ number_format($totalTapHariIni,0,',','.') }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Tap Gagal Hari Ini</div>
    <div class="mt-2 text-3xl font-bold">{{ number_format($tapGagalHariIni,0,',','.') }}</div>
  </div>
</div>

<!-- {{-- Tabel Omset --}} -->
<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="font-semibold mb-3">Tabel Omset Hari Ini</div>
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

<!-- {{-- Tabel Pengeluaran --}} -->
<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="font-semibold mb-3">Tabel Pengeluaran Hari Ini</div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2">No</th>
          <th class="px-3 py-2 text-left">Nama</th>
          <th class="px-3 py-2 text-center">Metode</th>
          <th class="px-3 py-2 text-right">Harga</th>
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
          <th class="px-3 py-2 text-center">Metode</th>   {{-- baru --}}
          <th class="px-3 py-2 text-center">Pembayaran</th>
        </tr>
      </thead>
      <tbody>
      @forelse($bon as $i => $p)
        @php
        $asOfStart = ($day ?? now())->copy()->startOfDay();
        $asOfEnd   = ($day ?? now())->copy()->endOfDay();

        $qty   = max(1, (int)($p->qty ?? 1));
        $harga = (int)($p->service->harga_service ?? 0);
        $total = $qty * $harga;

        $metodeNow = strtolower($p->metode->nama ?? 'bon');

        // MASIH BON per akhir hari terpilih jika:
        // - sekarang masih bon, ATAU
        // - sekarang sudah lunas tapi updated_at > asOfEnd (artinya pada tanggal yang dilihat masih bon)
        $isBonAsOfEnd = ($metodeNow === 'bon')
            || (($metodeNow !== 'bon') && optional($p->updated_at)->gt($asOfEnd));

        // Baru dilunasi pada tanggal terpilih?
        $dibayarHariIni = ($metodeNow !== 'bon')
            && optional($p->updated_at)->between($asOfStart, $asOfEnd);
        @endphp
        <tr class="border-t">
          <td class="px-3 py-2">{{ ($bon->currentPage()-1)*$bon->perPage() + $loop->iteration }}</td>

          <td class="px-3 py-2">
            <div class="font-medium">{{ $p->nama_pel }}</div>
            <div class="text-xs text-gray-500">{{ $p->no_hp_pel }}</div>
          </td>

          <td class="px-3 py-2">{{ $p->service->nama_service ?? '-' }}</td>
          <td class="px-3 py-2 text-center">{{ $qty }}</td>
          <td class="px-3 py-2 text-right">Rp {{ number_format($total,0,',','.') }}</td>
          <td class="px-3 py-2 text-center">{{ optional($p->created_at)->format('d/m/Y H:i') }}</td>

          {{-- Metode (dropdown) --}}
          <td class="px-3 py-2 text-center">
            <form method="POST" action="{{ route('admin.rekap.update-bon', $p) }}">
              @csrf
              @method('PATCH')
              <select name="metode" class="border rounded px-2 py-1 text-xs" onchange="this.form.submit()">
                <option value="bon"   {{ $metodeNow==='bon'   ? 'selected' : '' }}>Bon</option>
                <option value="tunai" {{ $metodeNow==='tunai' ? 'selected' : '' }}>Tunai</option>
                <option value="qris"  {{ $metodeNow==='qris'  ? 'selected' : '' }}>QRIS</option>
              </select>
            </form>
          </td>

          {{-- Pembayaran badge --}}
          <td class="px-3 py-2 text-center">
            @if($isBonAsOfEnd)
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-50 text-yellow-700 border border-yellow-200">
                Belum Lunas
              </span>
            @else
              <div class="flex flex-col items-center gap-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-50 text-green-700 border border-green-200">
                  Lunas
                </span>
                @if($dibayarHariIni)
                  <p class="text-[11px] text-gray-500">dibayar hari ini</p>
                @endif
              </div>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8" class="px-3 py-4 text-center text-gray-500">Tidak ada data bon pelanggan.</td></tr>
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
