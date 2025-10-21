<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password - Qxpress Laundry</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
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

  <main class="min-h-[calc(100vh-8rem)] flex items-center">
    <div class="w-full">
      <div class="max-w-xl mx-auto px-4">
        <div class="relative rounded-2xl bg-white shadow ring-1 ring-black/5 overflow-hidden">
          <div class="px-6 md:px-10 pt-8 pb-6 bg-gradient-to-r from-[#e9f1fa] to-white">
            <h1 class="text-xl md:text-2xl font-extrabold text-gray-900">Buat Password Baru</h1>
            <p class="text-sm text-gray-600">Masukkan password baru untuk akunmu.</p>
          </div>

          <div class="px-6 md:px-10 pb-8 pt-6">
            @if ($errors->any())
              <div class="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-2 text-sm ring-1 ring-red-200">
                Terjadi kesalahan. Silakan periksa kembali isian kamu.
              </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}" class="max-w-md">
              @csrf
              <input type="hidden" name="token" value="{{ request('token') }}">
              <input type="hidden" name="email" value="{{ request('email') }}">

              <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>
              <input id="password" name="password" type="password" required
                     class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#084cac] focus:ring-[#084cac] bg-white px-3 py-2"
                     placeholder="••••••••">

              <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mt-4">Konfirmasi Password</label>
              <input id="password_confirmation" name="password_confirmation" type="password" required
                     class="mt-1 w-full rounded-lg border-gray-300 focus:border-[#084cac] focus:ring-[#084cac] bg-white px-3 py-2"
                     placeholder="••••••••">

              <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('login') }}"
                   class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Kembali</a>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white bg-[#084cac] shadow hover:brightness-110">
                  Reset Password
                </button>
              </div>
            </form>
          </div>
        </div>

        <p class="text-center text-xs text-gray-500 mt-4">
          Setelah reset, kamu bisa login kembali dengan password baru.
        </p>
      </div>
    </div>
  </main>

  <footer class="bg-[#084cac] text-white">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center text-sm">© {{ date('Y') }} Qxpress Laundry</div>
  </footer>
</body>
</html>
