<x-app-layout>
  {{-- Layout responsif dengan drawer sidebar (Alpine) --}}
  <div x-data="{ open:false }" class="min-h-screen flex bg-[#f5f8ff]"> {{-- latar biru sangat muda --}}

    {{-- ===== Desktop sidebar (≥ lg) ===== --}}
    <aside class="w-64 hidden lg:flex flex-col shrink-0">
      {{-- Brand bar --}}
      <div class="h-16 flex items-center gap-2 px-4 bg-[#084cac] text-white">
        {{-- (opsional) logo kecil --}}
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-6 w-6 rounded bg-white/10 p-0.5 hidden sm:block">
        <div class="font-semibold tracking-tight">Qxpress Laundry</div>
      </div>

      {{-- Body sidebar --}}
      <div class="flex-1 bg-white border-r">
        <nav class="px-2 py-3 space-y-1">
          @php
            $active = 'bg-[#e9f1fa] text-[#084cac] font-semibold border-l-4 border-[#084cac]';
            $base   = 'flex items-center gap-2 px-3 py-2 rounded hover:bg-[#f3f7ff] text-slate-700';
          @endphp

          <a href="{{ route('admin.dashboard') }}"
             class="{{ $base }} {{ request()->routeIs('admin.dashboard') ? $active : '' }}">
            <svg class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/></svg>
            <span>Dashboard</span>
          </a>

          <a href="{{ route('admin.rekap.index') }}"
             class="{{ $base }} {{ request()->routeIs('admin.rekap.*') ? $active : '' }}">
             <svg class="h-5 w-5 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Rekap Keuangan</span>
          </a>

          <a href="{{ route('admin.pesanan.index') }}"
             class="{{ $base }} {{ request()->routeIs('admin.pesanan.*') ? $active : '' }}">
             <svg class="h-5 w-5 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Pesanan Laundry</span>
          </a>

          <a href="{{ route('admin.services.index') }}"
             class="{{ $base }} {{ request()->routeIs('admin.services.*') ? $active : '' }}">
             <svg class="h-5 w-5 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Layanan & Harga</span>
          </a>
        </nav>

        <div class="mt-auto p-4 text-sm">
          <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="flex items-center gap-2 text-slate-600 hover:text-[#084cac]">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1"/></svg>
              Keluar
            </button>
          </form>
        </div>
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
      <div class="h-16 flex items-center justify-between px-4 bg-[#084cac] text-white">
        <div class="font-semibold">Qxpress Laundry</div>
        <button class="p-2 rounded hover:bg-white/10" @click="open=false" aria-label="Tutup menu">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <nav class="px-2 py-3 space-y-1">
        @php
          $mBase = 'flex items-center gap-2 px-3 py-2 rounded hover:bg-[#f3f7ff] text-slate-700';
        @endphp
        <a @click="open=false" href="{{ route('admin.dashboard') }}"
           class="{{ $mBase }} {{ request()->routeIs('admin.dashboard') ? 'bg-[#e9f1fa] text-[#084cac] font-semibold' : '' }}">
          Dashboard
        </a>
        <a @click="open=false" href="{{ route('admin.rekap.index') }}"
           class="{{ $mBase }} {{ request()->routeIs('admin.rekap.*') ? 'bg-[#e9f1fa] text-[#084cac] font-semibold' : '' }}">
          Rekap Keuangan
        </a>
        <a @click="open=false" href="{{ route('admin.pesanan.index') }}"
           class="{{ $mBase }} {{ request()->routeIs('admin.pesanan.*') ? 'bg-[#e9f1fa] text-[#084cac] font-semibold' : '' }}">
          Pesanan Laundry
        </a>
        <a @click="open=false" href="{{ route('admin.services.index') }}"
           class="{{ $mBase }} {{ request()->routeIs('admin.services.*') ? 'bg-[#e9f1fa] text-[#084cac] font-semibold' : '' }}">
          Layanan & Harga
        </a>
      </nav>

      <div class="mt-auto p-4 text-sm">
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="flex items-center gap-2 text-slate-600 hover:text-[#084cac]">Keluar</button>
        </form>
      </div>
    </aside>

    {{-- ===== Main content ===== --}}
    <div class="flex-1 flex flex-col min-w-0">
      {{-- Header bar senada user --}}
      <header class="h-16 bg-[#084cac] text-white flex items-center justify-between gap-3 px-3 sm:px-4 shadow">
        <div class="flex items-center gap-3 min-w-0">
          {{-- hamburger for mobile --}}
          <button class="lg:hidden p-2 rounded hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40"
                  @click="open=true" aria-label="Buka menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <div class="font-semibold truncate">@yield('title','Dashboard Qxpress Laundry')</div>
        </div>

        <a href="{{ route('landing.home') }}"
           class="shrink-0 inline-flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg border border-white/70 hover:bg-white/10">
          Home
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
      </header>

      {{-- konten --}}
      <main class="p-4 sm:p-6">
        @if (session('ok'))
          <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 ring-1 ring-green-200/70">{{ session('ok') }}</div>
        @endif

        {{-- kartu default style agar seragam --}}
        <div class="[&_.card]:bg-white [&_.card]:rounded-xl [&_.card]:shadow [&_.card]:p-5">
          @yield('content')
        </div>
      </main>
    </div>
  </div>

  {{-- Alpine (fallback jika belum ada di app layout utama) --}}
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</x-app-layout>
