@props([
  'waUrl' => '#',
  'brand' => 'Qxpress Laundry',
  'logo'  => asset('images/logo.png'),
])

<header x-data="{ open:false }" class="fixed top-0 left-0 w-full bg-[#084cac] text-white z-50 shadow h-16">
  <div class="max-w-7xl mx-auto px-6 h-full flex items-center justify-between">

    <!-- Logo -->
    <a href="{{ url('/') }}" class="flex items-center gap-3 min-w-0">
      <img src="{{ $logo }}" alt="Logo {{ $brand }}" class="h-8 w-auto md:h-9 block">
      <span class="font-semibold text-lg md:text-xl truncate">{{ $brand }}</span>
    </a>

    <!-- Desktop Menu -->
    <nav class="hidden md:flex items-center gap-8 text-base">
      <a href="{{ url('/') }}"
         class="relative group {{ request()->is('/') ? 'after:w-full' : '' }}">
        Home
        <span class="absolute left-0 -bottom-1 w-0 h-0.5 bg-white transition-all group-hover:w-full"></span>
      </a>

      <a href="{{ route('services', false) ?: '#' }}"
         class="relative group {{ request()->routeIs('services') ? 'after:w-full' : '' }}">
        Daftar Harga
        <span class="absolute left-0 -bottom-1 w-0 h-0.5 bg-white transition-all group-hover:w-full"></span>
      </a>

      <a href="{{ route('tracking', false) ?: '#' }}"
         class="relative group {{ request()->routeIs('tracking') ? 'after:w-full' : '' }}">
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
      <a @click="open=false" href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="py-2 px-2 rounded hover:bg:white/10">Pesan</a>
    </nav>
  </div>
</header>
