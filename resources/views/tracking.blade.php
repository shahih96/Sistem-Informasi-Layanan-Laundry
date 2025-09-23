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
  <main class="pt-16 flex-1">
    <section class="max-w-7xl mx-auto px-4 py-10 md:py-14">
      <h1 class="text-3xl md:text-4xl font-extrabold text-center text-gray-900">Lacak Status Laundry Anda</h1>

      <!-- Search Bar -->
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
        <!-- {{-- reCAPTCHA widget --}} -->
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
        @php($hasQuery = filled($q ?? request('q')))

        {{-- Error input ATAU hasil kosong --}}
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

          {{-- Tips (compact, centered) --}}
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

        @elseif(isset($items) && $items->count())
          <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-[#e9f1fa]">
                  <tr class="text-left text-sm">
                    <th class="px-6 py-3 font-semibold text-gray-900">Nama</th>
                    <th class="px-6 py-3 font-semibold text-gray-900">Layanan</th>
                    <th class="px-6 py-3 font-semibold text-gray-900">Waktu Masuk</th>
                    <th class="px-6 py-3 font-semibold text-gray-900">Status</th>
                    <th class="px-6 py-3 font-semibold text-gray-900">Update Terakhir</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                  @foreach($items as $row)
                    <tr>
                      <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $row->nama_pel ?? '-' }}</div>
                        <div class="text-xs text-gray-500">{{ $row->no_hp_pel ?? '-' }}</div>
                      </td>
                      <td class="px-6 py-4">{{ optional($row->service)->nama_service ?? '-' }}</td>
                      <td class="px-6 py-4">{{ $row->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                      <td class="px-6 py-4">
                        {{ $row->latestStatusLog?->status?->nama_status
                          ?? $row->latestStatusLog?->keterangan
                          ?? '-' }}
                      </td>
                      <td class="px-6 py-4">
                        {{ $row->latestStatusLog?->updated_at?->translatedFormat('d M Y, H:i')
                          ?? $row->latestStatusLog?->created_at?->translatedFormat('d M Y, H:i')
                          ?? '-' }}
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

        @elseif(!$hasQuery)
          {{-- Tips awal (belum ada query) --}}
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
      </div>
    </section>
  </main>

  <!-- FOOTER -->
  <footer class="mt-auto bg-[#084cac] text-white">
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