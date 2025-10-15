<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Qxpress Laundry</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="font-sans text-gray-800 bg-white">

  {{-- TOPBAR --}}
  <header class="h-14 w-full bg-gray-200">
    <div class="max-w-7xl mx-auto h-full px-4 flex items-center justify-between">
      <a href="{{ route('landing.home') }}" class="flex items-center gap-3">
        <div class="h-7 w-7 bg-gray-300 rounded"></div>
        <span class="text-sm md:text-base font-medium">Qxpress Laundry</span>
      </a>
      <nav class="hidden md:flex items-center gap-8 text-sm">
        <a href="{{ route('landing.home') }}">Home</a>
        <a href="{{ Route::has('services') ? route('services') : '#' }}">Daftar Harga</a>
        <a href="{{ url('/#tentang') }}">Tentang</a>
        <a href="{{ url('/#pesan') }}">Pesan</a>
      </nav>
    </div>
  </header>

  {{-- MAIN --}}
  <main class="max-w-xl mx-auto px-4 py-10 md:py-14">
      {{-- Kanan: panel login --}}
      <div class="rounded bg-gray-100 p-6 md:p-10">
        <h1 class="text-center text-lg md:text-xl font-semibold">Dashboard Login</h1>
        <div class="mt-1 h-0.5 bg-gray-300"></div>

        <form method="POST" action="{{ route('login') }}" class="mt-6 max-w-md mx-auto">
          @csrf

          {{-- Jika password-only: set email admin dari seeder --}}
          <input type="hidden" name="email" value="admin@qxpress.test">

          {{-- Password --}}
          <label for="email" class="block text-sm mt-4 mb-1">Email</label>
          <input id="email" name="email" type="email" required
                 autocomplete=""
                 class="w-full border rounded px-3 py-2 bg-white"
                 placeholder="Email">
          <label for="password" class="block text-sm mt-4 mb-1">Password</label>
          <input id="password" name="password" type="password" required
                 autocomplete="current-password"
                 class="w-full border rounded px-3 py-2 bg-white"
                 placeholder="Password">

          {{-- Error pesan manual (tanpa x-input-error) --}}
          @error('password')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
          @enderror
          @error('email')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
          @enderror

          <div class="text-center mt-6">
            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="text-sm text-gray-600 hover:underline">
                Forgot Password?
              </a>
            @endif
          </div>

          <div class="flex justify-end mt-6">
            <button type="submit" class="px-6 py-3 rounded-lg bg-gray-900 text-white hover:brightness-110">
              Log in
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>

  {{-- FOOTER --}}
  <footer class="bg-gray-200">
    <div class="max-w-7xl mx-auto px-4 py-8">
      <p class="text-center text-sm">Contact Us!</p>
      <div class="mt-3 flex items-center justify-center gap-8 text-sm">
        <div class="flex items-center gap-2">
          <span class="inline-block h-5 w-5 bg-gray-400 rounded-full"></span>
          <span>@qxpress.laundry</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="inline-block h-5 w-5 bg-gray-400 rounded-full"></span>
          <span>0813-7382-0217</span>
        </div>
      </div>
    </div>
  </footer>

</body>
</html>
