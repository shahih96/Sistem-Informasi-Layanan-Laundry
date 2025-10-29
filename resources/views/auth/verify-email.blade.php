<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Email - Qxpress Laundry</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">
</head>
<body class="font-sans text-gray-800 bg-[#f5f8ff]">
  <main class="min-h-screen grid place-items-center p-4">
    <div class="max-w-lg w-full bg-white rounded-2xl shadow p-6 ring-1 ring-black/5">
      <h1 class="text-xl font-bold">Verifikasi Email</h1>
      <p class="mt-2 text-sm text-gray-600">
        Link verifikasi telah dikirim ke email kamu. Belum menerima?
      </p>
      <form method="POST" action="{{ route('verification.send') }}" class="mt-4">@csrf
        <button class="px-4 py-2 rounded-lg text-white bg-[#084cac]">Kirim Ulang Email</button>
      </form>
      <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf
        <button class="px-4 py-2 rounded-lg border">Keluar</button>
      </form>
    </div>
  </main>
</body>
</html>