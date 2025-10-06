<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Qxpress Laundry</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
  <style>
    [x-cloak] {
      display: none !important
    }
  </style>
</head>

<body class="font-sans text-gray-800">
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

  <!-- MAIN CONTENT-->
  <main class="pt-16">
    <!-- HERO -->
    <section class="relative">
      <div
        x-data="{ active: 0, slides: ['{{ asset('images/hero1.jpeg') }}', '{{ asset('images/hero2.jpeg') }}', '{{ asset('images/hero3.jpeg') }}'] }"
        x-init="setInterval(() => active = (active + 1) % slides.length, 4000)"
        class="relative max-w-8xl mx-auto h-64 md:h-[35rem] overflow-hidden">
        <!-- Slides -->
        <template x-for="(slide, index) in slides" :key="index">
          <div
            x-show="active === index"
            x-transition:enter="transition-opacity duration-1000"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-1000"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0">
            <img :src="slide" alt="Hero Banner" class="w-full h-full object-cover">
          </div>
        </template>

        <!-- Overlay teks -->
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-4
                    bg-gradient-to-t from-black/70 via-black/40 to-transparent">
          <h1 class="text-3xl md:text-5xl font-extrabold text-white drop-shadow-[2px_2px_2px_#000000]">
            Welcome to Qxpress Laundry
          </h1>
          <p class="text-lg md:text-2xl text-gray-100 mt-3 drop-shadow-[2px_2px_2px_#000000]">
            Solusi Laundry Terpercaya Anda
          </p>
          <div class="mt-6 flex items-center justify-center gap-4">
            <a href="{{ route('tracking', false) ?: '#' }}"
              class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white
                      bg-[#084cac] shadow-lg hover:brightness-110 hover:scale-105 transition transform">
              Lacak Status Laundry Anda
            </a>
            <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" 
              class="inline-flex items-center justify-center px-6 py-3 rounded-lg border border-[#084cac]
                      text-[#084cac] bg-white/95 shadow hover:bg-[#f0f4ff] hover:scale-105 transition transform">
              Pesan Sekarang
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- TIGA FITUR ATAS -->
    <section class="max-w-7xl mx-auto px-4 py-12">
      <div class="grid md:grid-cols-3 gap-8">
        <!-- Kartu 1 -->
        <div class="text-center">
          <div class="w-full h-40 rounded-lg bg-gray-200 overflow-hidden">
            <img src="{{ asset('images/fitur-1.jpg') }}" alt="Fitur 1"
              class="w-full h-full object-cover"
              onerror="this.style.display='none'">
          </div>
          <h3 class="font-semibold mt-4">Bawa ke Outlet atau Pesan Online Kapanpun</h3>
          <p class="text-sm text-gray-600 mt-2">
            Datang langsung ke outlet atau pesan via Whatsapp.
          </p>
        </div>
        <!-- Kartu 2 -->
        <div class="text-center">
          <div class="w-full h-40 rounded-lg bg-gray-200 overflow-hidden">
            <img src="{{ asset('images/fitur-2.jpg') }}" alt="Fitur 2"
              class="w-full h-full object-cover"
              onerror="this.style.display='none'">
          </div>
          <h3 class="font-semibold mt-4">Nikmati Waktu Luang Anda</h3>
          <p class="text-sm text-gray-600 mt-2">
            Kami siap jemput sesuai preferensimu.
          </p>
        </div>
        <!-- Kartu 3 -->
        <div class="text-center">
          <div class="w-full h-40 rounded-lg bg-gray-200 overflow-hidden">
            <img src="{{ asset('images/fitur-3.jpg') }}" alt="Fitur 3"
              class="w-full h-full object-cover"
              onerror="this.style.display='none'">
          </div>
          <h3 class="font-semibold mt-4">Santai dengan Pengantaran Langsung</h3>
          <p class="text-sm text-gray-600 mt-2">
            Cucian diantar ke alamat Anda. Ada layanan Express.
          </p>
        </div>
      </div>
    </section>

    <!-- LAYANAN KAMI -->
    <section class="max-w-7xl mx-auto px-4 py-6">
      <h2 class="text-2xl md:text-3xl font-bold text-center">Layanan Kami</h2>

      <div class="mt-10 grid gap-6 md:grid-cols-4">
        @php
        $services = [
        ['name'=>'Self Service','desc'=>'Cuci pakaian sendiri dengan mesin modern.','price'=>'Mulai dari Rp.10,000','img'=>'layanan-1.jpg'],
        ['name'=>'Cuci Lipat','desc'=>'Dicuci bersih dan dilipat rapi.','price'=>'Mulai dari Rp.4,000/Kg','img'=>'layanan-2.jpg'],
        ['name'=>'Cuci Setrika','desc'=>'Bersih, wangi, dan bebas kusut.','price'=>'Mulai dari Rp.6,000/Kg','img'=>'layanan-3.jpg'],
        ['name'=>'Antar Jemput','desc'=>'Jemput & antar cucian ke rumah.','price'=>'Mulai dari Rp.5,000','img'=>'layanan-4.jpg'],
        ];
        @endphp

        @foreach ($services as $s)
        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition p-6">
          <div class="w-full h-28 rounded-lg bg-gray-200 overflow-hidden">
            <img src="{{ asset('images/'.$s['img']) }}" alt="{{ $s['name'] }}"
              class="w-full h-full object-cover"
              onerror="this.style.display='none'">
          </div>
          <h3 class="mt-4 font-semibold text-lg">{{ $s['name'] }}</h3>
          <p class="text-sm text-gray-600 mt-1">{{ $s['desc'] }}</p>
          <p class="text-[#084cac] font-semibold mt-2">{{ $s['price'] }}</p>
        </div>
        @endforeach
      </div>

      <div class="mt-8 text-center">
        <a href="{{ route('services', false) ?: '#' }}"
          class="inline-flex items-center justify-center px-5 py-3 rounded-lg border border-[#084cac] text-[#084cac] hover:bg-[#f0f4ff]">
          Info Layanan & Harga
        </a>
      </div>
    </section>

    <!-- TENTANG KAMI -->
    <section id="tentang" class="scroll-mt-16 md:scroll-mt-20 max-w-7xl mx-auto px-4 py-16 bg-[#CDE6EE] rounded-2xl shadow-sm">
      <h2 class="text-2xl md:text-3xl font-bold text-center mb-10">Tentang Kami</h2>

      <div class="grid md:grid-cols-2 gap-10 items-center">
        <!-- Kolom kiri (logo/gambar) -->
        <div class="flex justify-center">
          <div class="w-80 h-80 rounded-xl flex items-right justify overflow-hidden">
            <img src="{{ asset('images/logo.png') }}"
              alt="Logo Qxpress Laundry"
              class="max-w-full max-h-full object-contain"
              onerror="this.style.display='none'">
          </div>
        </div>

        <!-- Kolom kanan (teks) -->
        <div>
          <h3 class="text-3xl md:text-4xl font-extrabold text-gray-900">Qxpress Laundry</h3>

          <div class="mt-4">
            <h4 class="font-semibold mb-3">Keunggulan</h4>
            <!-- Checklist modern -->
            <div class="space-y-3 text-gray-700">
              <p class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Layanan antar jemput
              </p>
              <p class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Proses cepat dan tepat waktu
              </p>
              <p class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Cuci setrika bersih wangi
              </p>
              <p class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Harga terjangkau
              </p>
            </div>
          </div>

          <!-- Info Kontak -->
          <div class="mt-6 space-y-3 text-gray-800 text-sm md:text-base">
            <p class="flex items-center gap-2">
              <!-- Clock Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#084cac]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span><strong>Buka setiap hari:</strong> 06.00 – 22.00 WIB</span>
            </p>

            <p class="flex items-center gap-2">
              <!-- Phone Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#084cac]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-.59 1.41l-1.12 1.12a16.02 16.02 0 006.36 6.36l1.12-1.12A2 2 0 0115 14h2a2 2 0 012 2v2a2 2 0 01-2 2h-1C9.37 20 4 14.63 4 8V7a2 2 0 012-2z" />
              </svg>
              <span><strong>0813-7382-0217</strong></span>
            </p>

            <p class="flex items-center gap-2">
              <!-- Location Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#084cac]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.656 0 3-1.344 3-3S13.656 5 12 5s-3 1.344-3 3 1.344 3 3 3zm0 0c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5z" />
              </svg>
              <span><strong>Jl. Airan Raya No.139, Way Hui, Jati Agung, Lampung Selatan</strong></span>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA AKHIR -->
    <section id="pesan" class="scroll-mt-16 md:scroll-mt-20 max-w-7xl mx-auto px-4 py-16">
      <div class="grid md:grid-cols-2 gap-10 items-center">
        <!-- Teks CTA kiri -->
        <div class="h-full flex flex-col justify-center space-y-3 md:space-y-5 lg:space-y-6">
          <p class="text-gray-700">Nikmati Waktu Santai dan Ketenangan</p>

          <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight">
            Qxpress Laundry<br />Bikin Nyantai
          </h2>

          <p class="text-gray-600">
            “Layanan laundry berkualitas dengan harga terjangkau, proses cepat, dan pelayanan ramah.”
          </p>

          <div class="flex gap-3 md:gap-4">
            <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
              class="inline-flex items-center justify-center px-5 py-3 rounded-lg text-white bg-[#084cac] shadow hover:brightness-110">
              Pesan Sekarang
            </a>
            <a href="{{ route('tracking', false) ?: '#' }}"
              class="inline-flex items-center justify-center px-5 py-3 rounded-lg border border-[#084cac] text-[#084cac] hover:bg-[#f0f4ff]">
              Lacak Status Laundry
            </a>
          </div>
        </div>

        <!-- Gambar CTA kanan -->
        <div class="w-full rounded-xl bg-gray-200 overflow-hidden">
          <img src="{{ asset('images/cta.jpg') }}" alt="CTA"
              class="w-full h-72 md:h-[22rem] object-cover"
              onerror="this.style.display='none'">
        </div>
      </div>
    </section>


    <!-- FOOTER -->
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

  </main>

  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>

</html>