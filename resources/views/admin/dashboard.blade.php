@extends('admin.layout')

@section('title','Dashboard Qxpress Laundry')

@section('content')
{{-- ===== CARDS: HARI INI ===== --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Pesanan Hari ini</div>
    <div class="mt-2 text-3xl font-bold">{{ $totalPesananHariIni }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Pendapatan Hari ini (Kotor)</div>
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

{{-- ===== RIWAYAT TERBARU ===== --}}
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
      <tbody class="
      [&_tr:nth-child(odd)]:bg-slate-50/70
      [&_tr:nth-child(even)]:bg-white
      [&_tr:hover]:bg-amber-50/40
    ">
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

{{-- ===== OMZET + RINGKASAN BULAN TERPILIH ===== --}}
<div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-4">
  {{-- Kiri: Chart --}}
  <div class="bg-white p-5 rounded-xl shadow lg:col-span-2">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
      <div class="font-semibold">Omzet Bulan {{ $monthLabel }}</div>

      <div class="flex items-center gap-2">
        {{-- Prev --}}
        <a href="{{ route('dashboard', array_filter(['m'=>$prevMonthValue,'show_exp'=>request('show_exp')?1:null])) }}"
           class="px-3 py-1.5 rounded-lg border hover:bg-gray-50"
           title="Bulan sebelumnya">
          ‹
        </a>

        {{-- Chip label bulan --}}
        <span class="px-3 py-1.5 rounded-full border bg-white select-none text-sm">
          {{ $monthLabel }}
        </span>

        {{-- Next (hanya jika <= bulan sekarang) --}}
        @if($canNext)
          <a href="{{ route('dashboard', array_filter(['m'=>$nextMonthValue,'show_exp'=>request('show_exp')?1:null])) }}"
             class="px-3 py-1.5 rounded-lg border hover:bg-gray-50"
             title="Bulan berikutnya">
            ›
          </a>
        @else
          <button class="px-3 py-1.5 rounded-lg border opacity-40 cursor-not-allowed" title="Sudah bulan terbaru">›</button>
        @endif>

        {{-- Dropdown "lompat ke bulan" --}}
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
          <select name="m"
                  class="border rounded-lg px-3 py-1.5 appearance-none pr-8 bg-white bg-[right_0.6rem_center] bg-no-repeat"
                  style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2220%22 height=%2220%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>');">
            @foreach($monthOptions as $opt)
              <option value="{{ $opt['value'] }}" {{ $opt['value'] === $selectedMonthValue ? 'selected' : '' }}>
                {{ $opt['label'] }}
              </option>
            @endforeach
          </select>
          @if(request('show_exp')) <input type="hidden" name="show_exp" value="1">@endif
          <button class="px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:brightness-110">Terapkan</button>
        </form>
      </div>
    </div>

    <div class="relative h-64 md:h-72">
      <canvas id="omzetMonthlyChart" class="absolute inset-0 w-full h-full"></canvas>
    </div>
  </div>

  {{-- Kanan: 3 kartu --}}
  <div class="grid gap-4">
    <div class="grid grid-cols-2 gap-4">
      <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between">
        <div>
          <div class="text-xs uppercase tracking-wide text-gray-500">Pengeluaran Bulan {{ $monthLabel }}</div>
          <div class="mt-2 text-xl font-bold">Rp {{ number_format($pengeluaranBulanIni,0,',','.') }}</div>
        </div>
        <svg class="w-8 h-8 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-width="1.5" d="M3 7h18v10H3zM3 10h12m2 3h2"/>
        </svg>
      </div>

      <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between">
        <div>
          <div class="text-xs uppercase tracking-wide text-gray-500">Total Cash (s.d. hari ini)</div>
          <div class="mt-2 text-xl font-bold">Rp {{ number_format($totalCashAdj,0,',','.') }}</div>
        </div>
        <svg class="w-8 h-8 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-width="1.5" d="M3 6h18v12H3zM6 9h12M6 15h6"/>
        </svg>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between">
      <div>
        <div class="text-xs uppercase tracking-wide text-gray-500">Pendapatan (Bersih) Bulan {{ $monthLabel }}</div>
        <div class="mt-2 text-2xl font-bold">Rp {{ number_format($pendapatanBersihBulanIni,0,',','.') }}</div>
        <div class="text-[11px] text-gray-500 mt-1">
          Kotor: Rp {{ number_format($omzetBulanIniGross,0,',','.') }}
          &nbsp;•&nbsp; Fee: Rp {{ number_format($totalFeeBulanIni,0,',','.') }}
          &nbsp;•&nbsp; Pengeluaran: Rp {{ number_format($pengeluaranBulanIni,0,',','.') }}
        </div>
      </div>
      <svg class="w-8 h-8 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-width="1.5" d="M4 17l6-6 4 4 6-8M3 12v9h18v-2"/>
      </svg>
    </div>
  </div>
</div>

{{-- ===== TOGGLE TABEL PENGELUARAN ===== --}}
<div class="mt-6 flex items-center justify-end gap-2">
  @if($showExpenses)
    <a href="{{ route('dashboard', ['m'=>$selectedMonthValue]) }}"
       class="px-4 py-2 rounded-lg border hover:bg-gray-50">Sembunyikan Tabel Pengeluaran</a>
  @else
    <a href="{{ route('dashboard', ['m'=>$selectedMonthValue,'show_exp'=>1]) }}"
       class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:brightness-110">Tampilkan Tabel Pengeluaran Bulan Ini</a>
  @endif
</div>

{{-- ===== TABEL PENGELUARAN (opsional) ===== --}}
@if($showExpenses)
  <div class="mt-4 bg-white p-5 rounded-xl shadow">
    <div class="font-semibold mb-3">Pengeluaran Bulan {{ $monthLabel }}</div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left">Tanggal</th>
            <th class="px-3 py-2 text-left">Keterangan</th>
            <th class="px-3 py-2 text-center">Metode</th>
            <th class="px-3 py-2 text-right">Total</th>
          </tr>
        </thead>
        <tbody class="
        [&_tr:nth-child(odd)]:bg-slate-50/70
        [&_tr:nth-child(even)]:bg-white
        [&_tr:hover]:bg-amber-50/40
      ">
          @forelse($pengeluaranBulanDetail as $row)
            @php
              $isOwnerDraw = str($row->keterangan ?? '')->lower()->contains(['bos','kanjeng','ambil duit','ambil duid','tarik kas','tarik']);
            @endphp
            <tr class="border-t">
              <td class="px-3 py-2">{{ optional($row->created_at)->format('d/m/Y H:i') }}</td>
              <td class="px-3 py-2">
                {{ $row->keterangan ?? '-' }}
                @if($isOwnerDraw)
                  <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[11px] bg-blue-50 text-blue-700 border border-blue-200" title="Tidak dihitung ke agregat pengeluaran">
                    Tarik Kas
                  </span>
                @endif
              </td>
              <td class="px-3 py-2 text-center">{{ $row->metode->nama ?? '-' }}</td>
              <td class="px-3 py-2 text-right">Rp {{ number_format($row->total,0,',','.') }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="px-3 py-4 text-center text-gray-500">Belum ada pengeluaran di bulan ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-2">{{ $pengeluaranBulanDetail->links('pagination::tailwind') }}</div>
  </div>
@endif

{{-- ===== SCRIPTS ===== --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
  const el = document.getElementById('omzetMonthlyChart');
  if (!el) return;

  const ctx = el.getContext('2d');
  const labelsRaw = @json($chartLabels ?? []);  // ['YYYY-MM-DD', ...]
  const series    = @json($chartData ?? []);    // [0, 40000, 0, ...]

  const labels = labelsRaw.map(d => (d || '').slice(8,10));

  const h = el.parentElement ? (el.parentElement.clientHeight || 240) : 240;
  const gradient = ctx.createLinearGradient(0, 0, 0, h);
  gradient.addColorStop(0, 'rgba(17,24,39,0.35)');
  gradient.addColorStop(1, 'rgba(17,24,39,0.02)');

  if (window.omzetChart) window.omzetChart.destroy();

  window.omzetChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Omzet',
        data: series,
        fill: true,
        backgroundColor: gradient,
        borderColor: '#111827',
        borderWidth: 1.5,
        tension: 0.35,
        pointRadius: 2,
        pointHoverRadius: 4,
        spanGaps: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: { grid: { display: false } },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.08)' },
          ticks: { callback: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v) }
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: { label: c => 'Rp ' + new Intl.NumberFormat('id-ID').format(c.parsed.y) }
        }
      }
    }
  });
})();
</script>
@endsection
