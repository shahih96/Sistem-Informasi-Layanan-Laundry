<x-app-layout>
  <div class="min-h-screen flex bg-gray-100">
    {{-- Sidebar --}}
    <aside class="w-64 bg-white border-r hidden md:flex flex-col">
      <div class="h-16 flex items-center px-4 font-bold">Qxpress Laundry</div>
      <nav class="px-2 space-y-1">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.dashboard')?'bg-gray-100 font-semibold':'' }}">Dashboard</a>
        <a href="{{ route('admin.rekap.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.rekap.*')?'bg-gray-100 font-semibold':'' }}">Rekap Keuangan</a>
        <a href="{{ route('admin.pesanan.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.pesanan.*')?'bg-gray-100 font-semibold':'' }}">Pesanan Laundry</a>
        <a href="{{ route('admin.services.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 {{ request()->routeIs('admin.services.*')?'bg-gray-100 font-semibold':'' }}">Layanan & Harga</a>
      </nav>
      <div class="mt-auto p-4 text-sm">
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="flex items-center gap-2 text-gray-600 hover:text-gray-900">Keluar</button>
        </form>
      </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1">
      <header class="h-16 bg-white border-b flex items-center justify-between px-4">
        <div class="font-bold">
          @yield('title','Dashboard Qxpress Laundry')
        </div>
        <a href="{{ route('landing.home') }}" class="text-sm underline">Home</a>
      </header>

      <main class="p-6">
        @if (session('ok'))
          <div class="mb-4 p-3 rounded bg-green-50 text-green-700">{{ session('ok') }}</div>
        @endif
        @yield('content')
      </main>
    </div>
  </div>
</x-app-layout>
