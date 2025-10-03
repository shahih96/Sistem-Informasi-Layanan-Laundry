<x-app-layout>
  {{-- Layout responsif dengan drawer sidebar (Alpine) --}}
  <div x-data="{ open:false }" class="min-h-screen flex bg-gray-100">

    {{-- ===== Desktop sidebar (≥ lg) ===== --}}
    <aside class="w-64 bg-white border-r hidden lg:flex flex-col shrink-0">
      <div class="h-16 flex items-center px-4 font-bold">Qxpress Laundry</div>

      <nav class="px-2 space-y-1">
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.dashboard')?'bg-gray-100 font-semibold':'' }}">
          Dashboard
        </a>
        <a href="{{ route('admin.rekap.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.rekap.*')?'bg-gray-100 font-semibold':'' }}">
          Rekap Keuangan
        </a>
        <a href="{{ route('admin.pesanan.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.pesanan.*')?'bg-gray-100 font-semibold':'' }}">
          Pesanan Laundry
        </a>
        <a href="{{ route('admin.services.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.services.*')?'bg-gray-100 font-semibold':'' }}">
          Layanan & Harga
        </a>
      </nav>

      <div class="mt-auto p-4 text-sm">
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="flex items-center gap-2 text-gray-600 hover:text-gray-900">Keluar</button>
        </form>
      </div>
    </aside>

    {{-- ===== Mobile overlay + drawer (< lg) ===== --}}
    <div
      class="fixed inset-0 bg-black/40 z-40 lg:hidden"
      x-show="open"
      x-transition.opacity
      @click="open=false"
      aria-hidden="true"></div>

    <aside
      class="fixed inset-y-0 left-0 w-72 bg-white border-r z-50 lg:hidden flex flex-col transform transition-transform duration-200"
      :class="open ? 'translate-x-0' : '-translate-x-full'">
      <div class="h-16 flex items-center justify-between px-4">
        <div class="font-bold">Qxpress Laundry</div>
        <button class="p-2 rounded hover:bg-gray-100" @click="open=false" aria-label="Tutup menu">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <nav class="px-2 space-y-1">
        <a @click="open=false" href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.dashboard')?'bg-gray-100 font-semibold':'' }}">
          Dashboard
        </a>
        <a @click="open=false" href="{{ route('admin.rekap.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.rekap.*')?'bg-gray-100 font-semibold':'' }}">
          Rekap Keuangan
        </a>
        <a @click="open=false" href="{{ route('admin.pesanan.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.pesanan.*')?'bg-gray-100 font-semibold':'' }}">
          Pesanan Laundry
        </a>
        <a @click="open=false" href="{{ route('admin.services.index') }}"
           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.services.*')?'bg-gray-100 font-semibold':'' }}">
          Layanan & Harga
        </a>
      </nav>

      <div class="mt-auto p-4 text-sm">
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="flex items-center gap-2 text-gray-600 hover:text-gray-900">Keluar</button>
        </form>
      </div>
    </aside>

    {{-- ===== Main content ===== --}}
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 bg-white border-b flex items-center justify-between gap-3 px-3 sm:px-4">
        <div class="flex items-center gap-3 min-w-0">
          {{-- hamburger for mobile --}}
          <button class="lg:hidden p-2 rounded hover:bg-gray-100" @click="open=true" aria-label="Buka menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <div class="font-bold truncate">@yield('title','Dashboard Qxpress Laundry')</div>
        </div>

        <a href="{{ route('landing.home') }}" class="text-sm underline shrink-0">Home</a>
      </header>

      <main class="p-4 sm:p-6">
        @if (session('ok'))
          <div class="mb-4 p-3 rounded bg-green-50 text-green-700">{{ session('ok') }}</div>
        @endif
        @yield('content')
      </main>
    </div>
  </div>

  {{-- Alpine (fallback jika belum ada di app layout utama) --}}
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</x-app-layout>
