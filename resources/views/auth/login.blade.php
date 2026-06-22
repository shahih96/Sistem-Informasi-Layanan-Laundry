<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Qxpress Laundry</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
  <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">
</head>
<body class="font-sans text-gray-800 bg-[#f5f8ff]">

  <header class="h-16 w-full bg-[#084cac] text-white shadow">
    <div class="max-w-7xl mx-auto px-6 h-full flex items-center justify-between">
      <a href="{{ route('landing.home') }}" class="flex items-center gap-3 min-w-0">
        <img src="{{ asset('images/logo.png') }}" alt="Logo Qxpress Laundry" class="h-8 w-auto md:h-9 block">
        <span class="font-semibold text-lg truncate">Qxpress Laundry</span>
      </a>
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
          <!-- Instagram Link -->
          <a href="https://instagram.com/qxpress.laundry" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 hover:opacity-100 transition-opacity">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1 1 12.324 0 6.162 6.162 0 0 1-12.324 0zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm4.965-10.322a1.44 1.44 0 1 1 2.881.001 1.44 1.44 0 0 1-2.881-.001z"/>
            </svg>
            <span>@qxpress.laundry</span>
          </a>
          
          <!-- WhatsApp Link -->
          <a href="https://wa.me/6282312579946" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 hover:opacity-100 transition-opacity">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-4.868 1.171l-.34.2-.352-.058 1.35-4.928.216-.338a9.9 9.9 0 012.992-.461h.005c5.476 0 9.927 4.451 9.927 9.934 0 2.65-1.031 5.139-2.905 7.012-1.873 1.873-4.362 2.905-7.012 2.905-5.487 0-9.938-4.451-9.938-9.927 0-1.757.434-3.467 1.261-5.037l.244-.388-.039-.424-1.324-4.934.338.199c1.44.908 2.639 2.159 3.319 3.582l.135.278 .305-.012c1.579-.123 3.168.176 4.718.916h.003z"/>
            </svg>
            <span>0823-1257-9946</span>
          </a>
        </div>
        <p class="text-xs opacity-80 mt-2">&copy; {{ date('Y') }} Qxpress Laundry</p>
      </div>
    </div>
  </footer>

</body>
</html>
