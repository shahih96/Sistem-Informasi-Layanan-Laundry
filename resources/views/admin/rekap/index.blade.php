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
</div>

<div id="rekap-sheet" class="mt-2 p-2">
  <div class="text-sm text-gray-600 ml-auto pb-2">
      Menampilkan rekap tanggal:
      <strong>{{ \Carbon\Carbon::parse(request('d', optional($day ?? now())->toDateString()))->translatedFormat('l, d M Y') }}</strong>
    </div>

  <!-- {{-- Ringkasan Keuangan (atas) --}} -->
  <div class="grid md:grid-cols-4 gap-4 capture-desktop-4">
    <div class="bg-white p-5 rounded-xl shadow">
      <div class="text-sm opacity-70">Total Cash Laundry (Akumulasi)</div>
      <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalCash,0,',','.') }}</div>
      <div class="text-xs text-gray-500 mt-1">Saldo kemarin: Rp {{ number_format($saldoCashKemarin,0,',','.') }}</div>
      <div class="text-xs text-gray-500 mt-1">
        @if(($penjualanTunaiHariIni ?? 0) != 0)
          <div>+ Tunai hari ini: Rp {{ number_format($penjualanTunaiHariIni,0,',','.') }}</div>
        @endif
        @if(($pelunasanBonTunaiHariIni ?? 0) != 0)
          <div>+ Pelunasan bon (tunai): Rp {{ number_format($pelunasanBonTunaiHariIni,0,',','.') }}</div>
        @endif
        @if(($pengeluaranTunaiHariIni ?? 0) != 0)
          <div>– Pengeluaran tunai: Rp {{ number_format($pengeluaranTunaiHariIni,0,',','.') }}</div>
        @endif
        @if(($totalFee ?? 0) != 0)
          <div>– Fee hari ini: Rp {{ number_format($totalFee,0,',','.') }}</div>
        @endif
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow">
      <div class="text-sm opacity-70">Total Bon Pelanggan (Akumulasi)</div>
      <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalPiutang ?? 0,0,',','.') }}</div>
      <div class="text-xs text-gray-500 mt-1">Bon kemarin: Rp {{ number_format($bonKemarin,0,',','.') }}</div>
      <div class="text-xs text-gray-500 mt-1">
        @if(($bonMasukHariIni ?? 0) != 0)
          <div>+ Bon masuk hari ini: Rp {{ number_format($bonMasukHariIni,0,',','.') }}</div>
        @endif
        @if(($bonDilunasiHariIni ?? 0) != 0)
          <div>– Bon dilunasi hari ini: Rp {{ number_format($bonDilunasiHariIni,0,',','.') }}</div>
        @endif
      </div>
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
      <div class="text-sm opacity-70">Total Omset Bersih Hari Ini</div>
      <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalOmzetBersihHariIni,0,',','.') }}</div>
      <div class="text-xs text-gray-500 mt-1">(Kotor: Rp {{ number_format($totalOmzetKotorHariIni,0,',','.') }} − Fee: Rp {{ number_format($totalFee,0,',','.') }})</div>
      <div class="text-xs text-gray-500 mt-1">Tunai: Rp {{ number_format($totalTunaiHariIni,0,',','.') }} • Qris: Rp {{ number_format($totalQrisHariIni,0,',','.') }} • Bon: Rp {{ number_format($totalBonHariIni,0,',','.') }}</div>
    </div>
  </div>
  <div class="mt-4 grid md:grid-cols-3 gap-4 capture-desktop-3">
    <div class="bg-white p-5 rounded-xl shadow">
      <div class="text-sm opacity-70">Sisa Saldo Kartu Hari Ini</div>
      <div class="mt-2 text-3xl font-bold">{{ is_null($saldoKartu) ? '—' : 'Rp '.number_format($saldoKartu,0,',','.') }}</div>
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
            <th class="px-3 py-2 text-center">Harga</th>
            <th class="px-3 py-2 text-center">Metode</th>
            <th class="px-3 py-2 text-center">Total</th>
            <th class="px-3 py-2 text-center no-export">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($omset as $i => $r)
            <tr class="border-t">
              <td class="px-3 py-2 text-center">{{ ($omset->currentPage()-1)*$omset->perPage() + $loop->iteration }}</td>
              <td class="px-3 py-2">{{ $r->service->nama_service ?? '-' }}</td>
              <td class="px-3 py-2 text-center">{{ $r->qty }}</td>
              <td class="px-3 py-2 text-center">Rp {{ number_format($r->subtotal,0,',','.') }}</td>
              <td class="px-3 py-2 text-center">{{ $r->metode->nama ?? '-' }}</td>
              <td class="px-3 py-2 text-center">Rp {{ number_format($r->total,0,',','.') }}</td>
              <td class="px-3 py-2 text-center no-export">
                {{-- Hapus 1 grup omzet: service_id + metode_pembayaran_id --}}
                <form method="POST" action="{{ route('admin.rekap.destroy-group') }}"
                      onsubmit="return confirm('Hapus seluruh baris pada grup ini?')">
                  @csrf @method('DELETE')
                  <input type="hidden" name="service_id" value="{{ $r->service_id }}">
                  <input type="hidden" name="metode_pembayaran_id" value="{{ $r->metode_pembayaran_id }}">
                  {{-- Jika ingin batasi per tanggal, kirimkan juga hidden "tanggal" --}}
                  {{-- <input type="hidden" name="tanggal" value="{{ optional($r->created_at)->toDateString() }}"> --}}
                  <button class="px-3 py-1 text-xs rounded bg-red-600 text-white">Hapus</button>
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
            <th class="px-3 py-2 text-center">Harga</th>
            <th class="px-3 py-2 text-center no-export">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pengeluaran as $i => $r)
            <tr class="border-t">
              <td class="px-3 py-2 text-center">{{ $pengeluaran->firstItem() + $i }}</td>
              <td class="px-3 py-2">
                {{ $r->keterangan ?? '-' }}

                @php
                  // deteksi "owner draw" → hanya penanda visual
                  $isOwnerDraw = str($r->keterangan ?? '')->lower()->contains([
                    'bos', 'kanjeng', 'ambil duit', 'ambil duid', 'tarik kas', 'tarik',
                  ]);
                @endphp

                @if($isOwnerDraw)
                  <span
                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[11px]
                          bg-blue-50 text-blue-700 border border-blue-200"
                    title="Tidak dihitung sebagai pengeluaran bulan ini"
                  >
                    Tarik Kas
                  </span>
                @endif
              </td>
              <td class="px-3 py-2 text-center">{{ $r->metode->nama ?? '-' }}</td>
              <td class="px-3 py-2 text-center">Rp {{ number_format($r->total,0,',','.') }}</td>
              <td class="px-3 py-2 text-center no-export">
                <form method="POST" action="{{ route('admin.rekap.destroy', $r->id) }}"
                      onsubmit="return confirm('Hapus baris ini?')">
                  @csrf @method('DELETE')
                  <button  class="px-3 py-1 text-xs rounded bg-red-600 text-white">Hapus</button>
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
            <th class="px-3 py-2 text-center">Total</th>
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

          $isBonAsOfEnd = ($metodeNow === 'bon')
              || (($metodeNow !== 'bon') && optional($p->updated_at)->gt($asOfEnd));

          $dibayarHariIni = ($metodeNow !== 'bon')
              && optional($p->updated_at)->between($asOfStart, $asOfEnd);
          @endphp
          <tr class="border-t">
            <td class="px-3 py-2">{{ ($bon->currentPage()-1)*$bon->perPage() + $loop->iteration }}</td>

            <td class="px-3 py-2">
              <div class="font-medium">{{ $p->nama_pel }}</div>
            </td>

            <td class="px-3 py-2">{{ $p->service->nama_service ?? '-' }}</td>
            <td class="px-3 py-2 text-center">{{ $qty }}</td>
            <td class="px-3 py-2 text-center">Rp {{ number_format($total,0,',','.') }}</td>
            <td class="px-3 py-2 text-center">{{ optional($p->created_at)->format('d/m/Y H:i') }}</td>

            {{-- Metode (dropdown) --}}
            <td class="px-3 py-2 text-center">
              @php $current = $metodeNow; @endphp

              {{-- Form dropdown (sembunyikan saat export) --}}
              <form method="POST"
                    action="{{ route('admin.rekap.update-bon', $p) }}"
                    class="no-export inline-block m-0"
                    onsubmit="return confirm('Yakin ubah metode pembayaran?');">
                @csrf
                @method('PATCH')
                <select name="metode"
                        class="border rounded px-2 py-1 text-xs"
                        data-current="{{ $current }}"
                        onchange="
                          if (confirm('Ubah metode menjadi ' + this.options[this.selectedIndex].text + '?')) {
                            this.form.submit();
                          } else {
                            this.value = this.getAttribute('data-current');
                          }
                        ">
                  <option value="bon"   {{ $current==='bon'   ? 'selected' : '' }}>Bon</option>
                  <option value="tunai" {{ $current==='tunai' ? 'selected' : '' }}>Tunai</option>
                  <option value="qris"  {{ $current==='qris'  ? 'selected' : '' }}>QRIS</option>
                </select>
              </form>

              {{-- Teks statis pengganti (hanya tampil saat export) --}}
              <span class="export-only hidden text-xs">
                @switch($current)
                  @case('tunai') Tunai @break
                  @case('qris')  QRIS  @break
                  @default       Bon
                @endswitch
              </span>
            </td>

            {{-- Pembayaran badge --}}
            <td class="px-3 py-2 text-center">
              @if($isBonAsOfEnd)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-50 text-red-700 border border-red-200">
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
</div> <!-- end rekap-sheet -->

<!-- {{-- Tombol Input Rekap --}} -->
<div class="mt-6 text-right">
  <a href="{{ route('admin.rekap.input') }}"
     class="inline-flex items-center gap-2 rounded-lg bg-gray-800 text-white px-4 py-2 hover:brightness-110">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Input & Update Rekap
  </a>
  <button id="btn-download-jpg" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 text-white px-4 py-2 hover:brightness-110 no-export">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
    </svg>
    Download JPG
</div>

<!-- html-to-image (CDN + fallback) -->
<script defer src="https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.min.js"></script>
<script>
  window.addEventListener('load', function () {
    if (!window.htmlToImage) {
      const s = document.createElement('script');
      s.defer = true;
      s.src = 'https://unpkg.com/html-to-image@1.11.11/dist/html-to-image.min.js';
      document.head.appendChild(s);
    }
  });
</script>

<!-- Style khusus saat capture (paksa tampilan desktop & hilangkan scroll)
     >>> TIDAK mematikan shadow supaya kartu tetap “mengambang” seperti di layar <<< -->
<style>
  /* aktif hanya saat root diberi .capture-mode */
  #rekap-sheet.capture-mode {
    background: #ffffff !important;
    padding: 16px;
  }

  /* jangan sembunyikan shadow — biarkan seperti UI */
  /* #rekap-sheet.capture-mode .shadow { box-shadow: none !important; }  <-- DIHAPUS */

  /* hilangkan overflow agar tabel tak terpotong */
  #rekap-sheet.capture-mode .overflow-x-auto,
  #rekap-sheet.capture-mode .overflow-y-auto,
  #rekap-sheet.capture-mode .overflow-auto { overflow: visible !important; }
  #rekap-sheet.capture-mode ::-webkit-scrollbar { display: none !important; }
  /* default: teks statis disembunyikan */
  #rekap-sheet .export-only { display: none; }

  /* saat capture/export: sembunyikan form, tampilkan teks */
  #rekap-sheet.capture-mode .no-export   { display: none !important; }
  #rekap-sheet.capture-mode .export-only { display: inline !important; }


  /* Paksa grid desktop saat capture (abaikan breakpoint “md”) */
  #rekap-sheet.capture-mode .capture-desktop-4 {
    display: grid !important;
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 1rem !important;
  }
  #rekap-sheet.capture-mode .capture-desktop-3 {
    display: grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    gap: 1rem !important;
  }
</style>

<script>
  (function () {
    const btn  = document.getElementById('btn-download-jpg'); // tombol
    const root = document.getElementById('rekap-sheet');      // area yang di-capture
    if (!btn || !root) return;

    const EXPORT_WIDTH = 1280; // render selebar desktop

    function toggleCapture(on) {
      if (on) {
        root.classList.add('capture-mode');
      } else {
        root.classList.remove('capture-mode');
        root.style.width = '';
        root.style.height = '';
        root.style.maxWidth = '';
      }
    }

    btn.addEventListener('click', async () => {
      try {
        if (!window.htmlToImage) {
          alert('Library html-to-image belum termuat. Coba refresh halaman.');
          return;
        }

        // 1) Aktifkan mode capture (paksa desktop & non-scroll)
        toggleCapture(true);

        // 2) Paksa lebar desktop agar layout mengikuti desktop
        root.style.maxWidth = 'none';
        root.style.width    = EXPORT_WIDTH + 'px';
        await new Promise(r => requestAnimationFrame(r)); // biar reflow dulu

        // 3) Hitung tinggi penuh konten
        const w = root.scrollWidth;
        const h = root.scrollHeight;
        root.style.height = h + 'px';

        // 4) Render ke JPG (pixelRatio dinaikkan supaya tajam)
        const dataUrl = await window.htmlToImage.toJpeg(root, {
          quality: 0.95,
          backgroundColor: '#ffffff',
          pixelRatio: Math.max(2, window.devicePixelRatio || 1),
          style: { width: w + 'px', height: h + 'px' },
          filter: (n) => !n.classList || !n.classList.contains('no-export'),
        });

        // 5) Download
        const dateLabel = "{{ \Illuminate\Support\Str::of(request('d', optional($day ?? now())->toDateString()))->replace(':','-') }}";
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = `rekap-${dateLabel}.jpg`;
        a.click();

      } catch (e) {
        alert('Gagal membuat JPG: ' + (e?.message || e));
        console.error(e);
      } finally {
        // 6) Balik ke tampilan normal
        toggleCapture(false);
      }
    });
  })();
</script>

@endsection
