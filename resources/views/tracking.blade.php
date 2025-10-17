<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lacak Status Laundry - Qxpress Laundry</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
  <style>[x-cloak]{display:none !important}</style>
  {!! NoCaptcha::renderJs('id') !!}
</head>

<body class="min-h-screen flex flex-col font-sans text-gray-800 bg-white">
  <x-site-header :wa-url="$waUrl" />

  <!-- MAIN -->
  <main class="pt-16 flex-1">
    <section class="max-w-7xl mx-auto px-4 py-10 md:py-14">
      <h1 class="text-3xl md:text-4xl font-extrabold text-center text-gray-900">Lacak Status Laundry Anda</h1>

      <!-- Search -->
      <form method="GET" action="{{ route('tracking') }}" class="mt-8">
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 md:gap-4 max-w-4xl mx-auto">
          <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input name="q" value="{{ old('q', $q ?? request('q')) }}" type="search" placeholder="Search No.HP..." class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 focus:border-[#084cac] focus:ring-[#084cac]" />
          </div>
          <button type="submit" class="shrink-0 inline-flex items-center justify-center px-5 py-3 rounded-lg text-white bg-[#084cac] shadow hover:brightness-110">Lacak Status Laundry</button>
        </div>
        <div class="max-w-4xl mx-auto mt-4 grid place-items-center">
          {!! NoCaptcha::display() !!}
          @error('g-recaptcha-response')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>
        <p class="text-center text-xs text-gray-500 mt-2">Masukkan <strong>No. HP</strong> yang digunakan saat membuat pesanan.</p>
      </form>

      <!-- Results -->
      <div class="mt-10">
        @if($hasQuery = filled($q ?? request('q')))

        {{-- Error / Empty --}}
        @if(!empty($error) || ($hasQuery && isset($items) && $items->isEmpty()))
          <div class="max-w-2xl mx-auto mb-6">
            <div class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center">
              <p class="font-semibold text-red-700">Data tidak ditemukan</p>
              <p class="text-sm text-red-600 mt-1">
                @if(!empty($error))
                  {{ $error }}
                @else
                  Coba periksa kembali No. HP yang kamu masukkan.
                @endif
              </p>
            </div>
          </div>

          <div class="mt-6 flex justify-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white/90 px-4 py-2 text-xs md:text-sm text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="16" x2="12" y2="12" />
                <line x1="12" y1="8" x2="12" y2="8" />
              </svg>
              <span>Tips: gunakan format nomor seperti <span class="font-semibold">0812xxxxxxx</span>.</span>
            </div>
          </div>

        {{-- Ada hasil --}}
        @elseif(isset($items) && $items->count())
          <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-[#e9f1fa]">
                  <tr class="text-left text-sm">
                    <th class="px-6 py-3 font-semibold text-gray-900">Nama</th>
                    <th class="px-6 py-3 font-semibold text-gray-900">Layanan</th>
                    <th class="px-6 py-3 font-semibold text-gray-900 text-left">Status</th>
                    <th class="px-6 py-3 font-semibold text-gray-900 text-center">Qty</th>
                    <th class="px-6 py-3 font-semibold text-gray-900 text-center">Total</th>
                    <th class="px-6 py-3 font-semibold text-gray-900 text-center">Pembayaran</th>
                    <th class="px-6 py-3 font-semibold text-gray-900">Waktu Masuk</th>
                    <th class="px-6 py-3 font-semibold text-gray-900">Update Terakhir</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 text-sm">
                @foreach($items as $row)
                  @php
                    $qty   = max(1, (int)($row->qty ?? 1));
                    $harga = (int) optional($row->service)->harga_service;
                    $total = $qty * $harga;

                    $statusText = $row->latestStatusLog?->status?->nama_status
                                  ?? $row->latestStatusLog?->keterangan
                                  ?? '-';

                    $isSelesai = \Illuminate\Support\Str::of($statusText)->lower()->contains('selesai');

                    $updatedAt = $row->latestStatusLog?->updated_at
                                ?? $row->latestStatusLog?->created_at
                                ?? null;

                    $metodeNow = strtolower($row->metode->nama ?? 'bon');
                  @endphp
                  <tr>
                    <td class="px-6 py-4">
                      <div class="font-medium text-gray-900">{{ $row->nama_pel ?? '-' }}</div>
                      <div class="text-xs text-gray-500">{{ $row->no_hp_pel ?? '-' }}</div>
                    </td>
                    <td class="px-6 py-4">{{ optional($row->service)->nama_service ?? '-' }}</td>
                    <td class="px-6 py-4">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-xs border
                        {{ $isSelesai ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-yellow-50 text-yellow-700 border border-yellow-200' }}">
                        {{ $statusText }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $qty }}</td>
                    <td class="px-6 py-4 text-right">Rp {{ number_format($total,0,',','.') }}</td>
                    <td class="px-6 py-4 text-center">
                      @if($metodeNow === 'bon')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-50 text-red-700 border border-red-200">
                          Belum Lunas
                        </span>
                      @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-50 text-green-700 border border-green-200">
                          Lunas
                        </span>
                      @endif
                    </td>
                    <td class="px-6 py-4">{{ $row->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                    <td class="px-6 py-4">{{ $updatedAt?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          </div>

        {{-- Belum ada query --}}
        @elseif(!$hasQuery)
          <div class="mt-6 flex justify-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white/90 px-4 py-2 text-xs md:text-sm text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="16" x2="12" y2="12" />
                <line x1="12" y1="8" x2="12" y2="8" />
              </svg>
              <span>Tips: gunakan format nomor seperti <span class="font-semibold">0812xxxxxxx</span>.</span>
            </div>
          </div>
        @endif  
      @endif
      </div>
    </section>
  </main>

  <x-site-footer class="mt-auto" />

  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>