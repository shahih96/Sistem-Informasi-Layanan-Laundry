<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar Harga - Qxpress Laundry</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
  <style>[x-cloak]{display:none !important}</style>
</head>

<body class="font-sans text-gray-800 bg-white">
  <x-site-header :wa-url="$waUrl" />

  <!-- MAIN -->
  <main class="pt-16">
    <section class="max-w-7xl mx-auto px-4 py-10 md:py-14">
      <div class="text-center">
        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900">Daftar Harga</h1>
        <p class="mt-2 text-sm md:text-base text-gray-600">Harga transparan, layanan fleksibel—pilih sesuai kebutuhanmu.</p>
      </div>

      <!-- Grid kategori -->
      <div class="mt-10 grid gap-6 md:grid-cols-3">
        @foreach($grouped as $kategori => $rows)
          <section class="bg-white rounded-2xl border shadow-sm hover:shadow-md transition">
            <div class="px-5 py-3 bg-[#e9f1fa] rounded-t-2xl font-semibold text-gray-900 flex items-center justify-between">
              <span>{{ $kategori }}</span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-white border text-gray-600">{{ $rows->count() }} layanan</span>
            </div>

            <div class="p-5 space-y-4">
              @foreach($rows as $row)
                <div class="flex items-start gap-3">
                  <div class="shrink-0 h-9 w-9 rounded-lg bg-gray-50 border flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M3 6h18M3 12h18M3 18h18" />
                    </svg>
                  </div>

                  <div class="flex-1">
                    <div class="flex items-baseline justify-between gap-3">
                      <h3 class="font-semibold text-gray-900">{{ $row->nama_service }}</h3>
                      <div class="font-extrabold text-gray-900">Rp {{ number_format($row->harga_service, 0, ',', '.') }}</div>
                    </div>
                    <div class="mt-1 text-xs text-gray-500">
                      Diupdate: {{ optional($row->updated_at)->translatedFormat('d M Y') ?? '-' }}
                    </div>
                  </div>
                </div>
                @if(!$loop->last)
                  <div class="h-px bg-gray-100"></div>
                @endif
              @endforeach
            </div>
          </section>
        @endforeach
      </div>

      <div class="mt-12 text-center">
        <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-5 py-3 rounded-lg text-white bg-[#084cac] shadow hover:brightness-110">Pesan Sekarang</a>
      </div>

      <div class="mt-6 text-center text-xs text-gray-500">
        Data harga bersifat informatif dan dapat berubah sewaktu-waktu.
      </div>
    </section>
  </main>

  <x-site-footer />

  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>