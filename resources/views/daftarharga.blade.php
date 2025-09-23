<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar Harga - Qxpress Laundry</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
  <style>
    [x-cloak]{display:none !important}
  </style>
</head>

<body class="font-sans text-gray-800 bg-white">
  <!-- NAVBAR (Responsive: Desktop + Mobile Dropdown) -->
  <header x-data="{ open:false }" class="fixed top-0 left-0 w-full bg-[#084cac] text-white z-50 shadow h-16">
    <div class="max-w-7xl mx-auto px-6 h-full flex items-center justify-between">

      <!-- Logo -->
      <a href="{{ url('/') }}" class="flex items-center gap-3 min-w-0">
        <img src="{{ asset('images/logo.png') }}" alt="Logo Qxpress Laundry" class="h-8 w-auto md:h-9 block">
        <span class="font-semibold text-lg md:text-xl truncate">Qxpress Laundry</span>
      </a>

      <!-- Desktop Menu -->
      <nav class="hidden md:flex items-center gap-8 text-base">
        <a href="{{ url('/') }}" class="relative group">
          Home
          <span class="absolute left-0 -bottom-1 w-0 h-0.5 bg-white transition-all group-hover:w-full"></span>
        </a>
        <a href="{{ route('services', false) ?: '#' }}" class="relative group">
          Daftar Harga
          <span class="absolute left-0 -bottom-1 w-0 h-0.5 bg-white transition-all group-hover:w-full"></span>
        </a>
        <a href="{{ route('tracking', false) ?: '#' }}" class="relative group">
          Lacak Status Laundry
          <span class="absolute left-0 -bottom-1 w-0 h-0.5 bg-white transition-all group-hover:w-full"></span>
        </a>
        <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="relative group">
          Pesan
        <span class="absolute left-0 -bottom-1 w-0 h-0.5 bg-white transition-all group-hover:w-full"></span>
        </a>
      </nav>

      <!-- Mobile Toggle -->
      <button
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-controls="mobile-menu"
        class="md:hidden inline-flex items-center justify-center p-2 rounded-lg hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/50">
        <!-- Icon: Hamburger -->
        <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <!-- Icon: Close -->
        <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Mobile Dropdown -->
    <div
      id="mobile-menu"
      x-show="open"
      @click.outside="open=false"
      @keydown.escape.window="open=false"
      x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="opacity-0 -translate-y-2"
      x-transition:enter-end="opacity-100 translate-y-0"
      x-transition:leave="transition ease-in duration-150"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-2"
      class="md:hidden bg-[#084cac] border-t border-white/10">
      <nav class="px-6 py-3 flex flex-col gap-1 text-base">
        <a @click="open=false" href="{{ url('/') }}" class="py-2 px-2 rounded hover:bg-white/10">Home</a>
        <a @click="open=false" href="{{ route('services', false) ?: '#' }}" class="py-2 px-2 rounded hover:bg-white/10">Daftar Harga</a>
        <a @click="open=false" href="{{ route('tracking', false) ?: '#' }}" class="py-2 px-2 rounded hover:bg-white/10">Lacak Status Laundry</a>
        <a @click="open=false" href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="py-2 px-2 rounded hover:bg-white/10">Pesan</a>
      </nav>
    </div>
  </header>

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
            <!-- Header kategori -->
            <div class="px-5 py-3 bg-[#e9f1fa] rounded-t-2xl font-semibold text-gray-900 flex items-center justify-between">
              <span>{{ $kategori }}</span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-white border text-gray-600">{{ $rows->count() }} layanan</span>
            </div>

            <!-- Isi list layanan -->
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

      <!-- CTA back to all services -->
      <div class="mt-12 text-center">
        <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center px-5 py-3 rounded-lg text-white bg-[#084cac] shadow hover:brightness-110">Pesan Sekarang</a>
      </div>

      <div class="mt-6 text-center text-xs text-gray-500">
        Data harga bersifat informatif dan dapat berubah sewaktu-waktu.
      </div>
    </section>
  </main>

  <!-- FOOTER (match Landing Page with synced contact) -->
  <footer class="bg-[#084cac] text-white">
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="flex flex-col items-center gap-2">
        <p class="text-sm">Contact Us!</p>
        <div class="flex items-center gap-6 text-sm opacity-95">
          <span>@qxpress.laundry</span>
          <span>0813-7382-0217</span>
        </div>
        <p class="text-xs opacity-80 mt-2">&copy; {{ date('Y') }} Qxpress Laundry</p>
      </div>
    </div>
  </footer>

  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
