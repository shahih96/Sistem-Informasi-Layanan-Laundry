<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Qxpress Laundry</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
</head>
<body class="font-sans text-gray-800 bg-[#f5f8ff]">

  {{-- TOPBAR senada user --}}
  <header class="h-16 w-full bg-[#084cac] text-white shadow">
    <div class="max-w-7xl mx-auto px-6 h-full flex items-center justify-between">
      <a href="{{ route('landing.home') }}" class="flex items-center gap-3 min-w-0">
          <img src="{{ asset('images/logo.png') }}" alt="Logo Qxpress Laundry" class="h-8 w-auto md:h-9 block">
        <span class="font-semibold text-lg truncate">Qxpress Laundry</span>
      </a>

      <nav class="hidden md:flex items-center gap-6 text-sm">
        <a class="hover:underline/60" href="{{ route('landing.home') }}">Home</a>
        <a class="hover:underline/60" href="{{ Route::has('services') ? route('services') : '#' }}">Daftar Harga</a>
        <a class="hover:underline/60" href="{{ url('/#tentang') }}">Tentang</a>
        <a class="hover:underline/60" href="{{ url('/#pesan') }}">Pesan</a>
      </nav>
    </div>
  </header>

  {{-- MAIN --}}
  <main class="min-h-[calc(100vh-8rem)] flex items-center">
    <div class="w-full">
      <div class="max-w-xl mx-auto px-4">
        {{-- Kartu login --}}
        <div class="relative rounded-2xl bg-white shadow ring-1 ring-black/5 overflow-hidden">
          {{-- header kartu --}}
          <div class="px-6 md:px-10 pt-8 pb-6 bg-gradient-to-r from-[#e9f1fa] to-white">
            <div class="flex items-center gap-3">
              <span class="grid place-items-center h-10 w-10 rounded-full bg-[#084cac] text-white ring-2 ring-[#084cac]/20">
                <!-- icon lock -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 11c1.657 0 3 1.343 3 3v3H9v-3c0-1.657 1.343-3 3-3zm0-7a5 5 0 00-5 5v2h10V9a5 5 0 00-5-5z"/>
                </svg>
              </span>
              <div>
                <h1 class="text-xl md:text-2xl font-extrabold text-gray-900">Dashboard Login</h1>
                <p class="text-sm text-gray-600">Masuk untuk mengelola Qxpress Laundry</p>
              </div>
            </div>
          </div>

          {{-- form --}}
          <div class="px-6 md:px-10 pb-8 pt-6">
            {{-- status / pesan sukses --}}
            @if (session('status'))
              <div class="mb-4 rounded-lg bg-blue-50 text-blue-700 px-4 py-2 text-sm ring-1 ring-blue-200">
                {{ session('status') }}
              </div>
            @endif

            {{-- error global --}}
            @if ($errors->any())
              <div class="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-2 text-sm ring-1 ring-red-200">
                Terjadi kesalahan. Silakan periksa kembali isian kamu.
              </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="max-w-md">
              @csrf

              {{-- Email --}}
              <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
              <input id="email" name="email" type="email" required autofocus
                     value="{{ old('email') }}"
                     class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#084cac] focus:ring-[#084cac] bg-white px-3 py-2"
                     placeholder="nama@domain.com">
              @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror

              {{-- Password --}}
              <label for="password" class="block text-sm font-medium text-gray-700 mt-4">Password</label>
              <input id="password" name="password" type="password" required
                     autocomplete="current-password"
                     class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#084cac] focus:ring-[#084cac] bg-white px-3 py-2"
                     placeholder="••••••••">
              @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror

              {{-- Remember & Forgot --}}
              <div class="mt-4 flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                  <input type="checkbox" name="remember" class="rounded border-gray-300 text-[#084cac] focus:ring-[#084cac]">
                  Ingat saya
                </label>

                @if (Route::has('password.request'))
                  <a href="{{ route('password.request') }}" class="text-sm text-[#084cac] hover:underline">
                    Lupa Password?
                  </a>
                @endif
              </div>

              <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('landing.home') }}"
                   class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                  Kembali
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white bg-[#084cac] shadow hover:brightness-110">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 12h14M12 5l7 7-7 7"/>
                  </svg>
                  Log in
                </button>
              </div>
            </form>
          </div>
        </div>

        {{-- catatan kecil --}}
        <p class="text-center text-xs text-gray-500 mt-4">
          Gunakan akun yang diberikan admin untuk mengakses dashboard.
        </p>
      </div>
    </div>
  </main>

  {{-- FOOTER senada user --}}
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

</body>
</html>
