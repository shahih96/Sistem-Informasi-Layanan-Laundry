@extends('admin.layout')

@section('title','Dashboard Qxpress Laundry')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Total Pesanan Hari ini</div>
    <div class="mt-2 text-3xl font-bold">{{ $totalPesananHariIni }}</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-sm opacity-70">Pendapatan Hari ini</div>
    <div class="mt-2 text-3xl font-bold">Rp {{ number_format($pendapatanBersihHariIni,0,',','.') }}</div>
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

{{-- Riwayat tabel: auto-scroll horizontal di mobile --}}
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

{{-- Omzet + Ringkasan Bulan Ini --}}
<div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-4">
  {{-- KIRI: Chart Omzet Bulan Ini --}}
  <div class="bg-white p-5 rounded-xl shadow lg:col-span-2">
    <div class="font-semibold mb-3">Omzet Bulan Ini</div>
    <div class="relative h-64 md:h-72"> {{-- ⬅️ tinggi fix --}}
      <canvas id="omzetMonthlyChart" class="absolute inset-0 w-full h-full"></canvas>
    </div>
  </div>

  {{-- KANAN: 3 kartu --}}
  <div class="grid gap-4">
    <div class="grid grid-cols-2 gap-4">
      <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between">
        <div>
          <div class="text-xs uppercase tracking-wide text-gray-500">Pengeluaran Bulan Ini</div>
          <div class="mt-2 text-xl font-bold">Rp {{ number_format($pengeluaranBulanIni,0,',','.') }}</div>
        </div>
        <svg class="w-8 h-8 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-width="1.5" d="M3 7h18v10H3zM3 10h12m2 3h2"/>
        </svg>
      </div>

      <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between">
        <div>
          <div class="text-xs uppercase tracking-wide text-gray-500">
            Total Cash Laundry
            @isset($day)<span class="text-[10px] text-gray-400"> ({{ $day->format('d M Y') }})</span>@endisset
          </div>
          <div class="mt-2 text-xl font-bold">Rp {{ number_format($totalCash,0,',','.') }}</div>
        </div>
        <svg class="w-8 h-8 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-width="1.5" d="M3 6h18v12H3zM6 9h12M6 15h6"/>
        </svg>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between">
      <div>
        <div class="text-xs uppercase tracking-wide text-gray-500">Pendapatan (Bersih) Bulan Ini</div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
  const el = document.getElementById('omzetMonthlyChart');
  if (!el) return;

  const ctx = el.getContext('2d');
  const labelsRaw = @json($chartLabels ?? []);   // ['YYYY-MM-DD', ...]
  const data      = @json($chartData ?? []);     // [0, 40000, 0, ...]

  const labels = labelsRaw.map(d => (d || '').slice(8,10));

  const h = el.parentElement.clientHeight || 240;
  const gradient = ctx.createLinearGradient(0, 0, 0, h);
  gradient.addColorStop(0, 'rgba(17,24,39,0.35)');
  gradient.addColorStop(1, 'rgba(17,24,39,0.02)');

  // Jika halaman di-visit ulang via PJAX/Turbo, destroy chart lama
  if (window.omzetChart) window.omzetChart.destroy();

  window.omzetChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Omzet',
        data,
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
      maintainAspectRatio: false, // <-- hormati tinggi container
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
