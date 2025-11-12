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
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" type="image/x-icon">
</head>

<body class="font-sans text-gray-800">
    <x-site-header :wa-url="$waUrl" />
    <main class="pt-16">
        <!-- HERO -->
        <section class="relative">
            <div x-data="{ active: 0, slides: ['{{ asset('images/hero1.jpeg') }}', '{{ asset('images/hero2.jpeg') }}', '{{ asset('images/hero3.jpeg') }}'] }" x-init="setInterval(() => active = (active + 1) % slides.length, 4000)"
                class="relative max-w-8xl mx-auto h-64 md:h-[35rem] overflow-hidden">
                <!-- Slides -->
                <template x-for="(slide, index) in slides" :key="index">
                    <div x-show="active === index" x-transition:enter="transition-opacity duration-1000"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition-opacity duration-1000" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0" class="absolute inset-0">
                        <img :src="slide" alt="Hero Banner" class="w-full h-full object-cover">
                    </div>
                </template>

                <!-- Overlay teks -->
                <div
                    class="absolute inset-0 flex flex-col items-center justify-center text-center px-4
                    bg-gradient-to-t from-black/70 via-black/40 to-transparent">
                    <h1 class="text-3xl md:text-5xl font-extrabold text-white drop-shadow-[2px_2px_2px_#000000]">
                        Welcome to RUmdim
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
                        <img src="{{ asset('images/fitur-1.jpg') }}" alt="Fitur 1" class="w-full h-full object-cover"
                            onerror="this.style.display='none'">
                    </div>
                    <h3 class="font-semibold mt-4">Bawa ke Outlet atau Pesan Online Kapanpun</h3>
                    <p class="text-sm text-gray-600 mt-2">Datang langsung ke outlet atau pesan via Whatsapp.</p>
                </div>
                <!-- Kartu 2 -->
                <div class="text-center">
                    <div class="w-full h-40 rounded-lg bg-gray-200 overflow-hidden">
                        <img src="{{ asset('images/fitur-2.jpg') }}" alt="Fitur 2" class="w-full h-full object-cover"
                            onerror="this.style.display='none'">
                    </div>
                    <h3 class="font-semibold mt-4">Nikmati Waktu Luang Anda</h3>
                    <p class="text-sm text-gray-600 mt-2">Kami siap jemput sesuai preferensimu.</p>
                </div>
                <!-- Kartu 3 -->
                <div class="text-center">
                    <div class="w-full h-40 rounded-lg bg-gray-200 overflow-hidden">
                        <img src="{{ asset('images/fitur-3.jpg') }}" alt="Fitur 3" class="w-full h-full object-cover"
                            onerror="this.style.display='none'">
                    </div>
                    <h3 class="font-semibold mt-4">Santai dengan Pengantaran Langsung</h3>
                    <p class="text-sm text-gray-600 mt-2">Cucian diantar ke alamat Anda. Ada layanan Express.</p>
                </div>
            </div>
        </section>

        <!-- LAYANAN KAMI -->
        <section class="max-w-7xl mx-auto px-4 py-6">
            <h2 class="text-2xl md:text-3xl font-bold text-center">Layanan Kami</h2>

            <div class="mt-10 grid gap-6 md:grid-cols-4">
                <!-- Self Service -->
                <div class="bg-white rounded-2xl shadow hover:shadow-lg transition p-6">
                    <div class="relative w-full rounded-lg overflow-hidden aspect-[16/9]">
                        <img src="{{ asset('images/layanan-1.jpg') }}" alt="Self Service"
                            class="w-full h-full object-cover" onerror="this.style.display='none'">
                    </div>
                    <h3 class="mt-4 font-semibold text-lg">Self Service</h3>
                    <p class="text-sm text-gray-600 mt-1">Cuci pakaian sendiri dengan mesin modern.</p>
                    <p class="text-[#084cac] font-semibold mt-2">Mulai dari Rp.10,000</p>
                </div>

                <!-- Cuci Lipat -->
                <div class="bg-white rounded-2xl shadow hover:shadow-lg transition p-6">
                    <div class="relative w-full rounded-lg overflow-hidden aspect-[16/9]">
                        <img src="{{ asset('images/layanan-2.jpg') }}" alt="Cuci Lipat"
                            class="w-full h-full object-cover" onerror="this.style.display='none'">
                    </div>
                    <h3 class="mt-4 font-semibold text-lg">Cuci Lipat</h3>
                    <p class="text-sm text-gray-600 mt-1">Dicuci bersih dan dilipat rapi.</p>
                    <p class="text-[#084cac] font-semibold mt-2">Mulai dari Rp.4,000/Kg</p>
                </div>

                <!-- Cuci Setrika -->
                <div class="bg-white rounded-2xl shadow hover:shadow-lg transition p-6">
                    <div class="relative w-full rounded-lg overflow-hidden aspect-[16/9]">
                        <img src="{{ asset('images/layanan-3.jpg') }}" alt="Cuci Setrika"
                            class="w-full h-full object-cover" onerror="this.style.display='none'">
                    </div>
                    <h3 class="mt-4 font-semibold text-lg">Cuci Setrika</h3>
                    <p class="text-sm text-gray-600 mt-1">Bersih, wangi, dan bebas kusut.</p>
                    <p class="text-[#084cac] font-semibold mt-2">Mulai dari Rp.6,000/Kg</p>
                </div>

                <!-- Antar Jemput -->
                <div class="bg-white rounded-2xl shadow hover:shadow-lg transition p-6">
                    <div class="relative w-full rounded-lg overflow-hidden aspect-[16/9]">
                        <img src="{{ asset('images/layanan-4.jpg') }}" alt="Antar Jemput"
                            class="w-full h-full object-cover" onerror="this.style.display='none'">
                    </div>
                    <h3 class="mt-4 font-semibold text-lg">Antar Jemput</h3>
                    <p class="text-sm text-gray-600 mt-1">Jemput & antar cucian ke rumah.</p>
                    <p class="text-[#084cac] font-semibold mt-2">Mulai dari Rp.5,000</p>
                </div>
            </div>

            <!-- Info layanan dan harga -->
            <div class="mt-8 text-center">
                <a href="{{ route('services', false) ?: '#' }}"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-lg text-white bg-[#084cac] shadow hover:brightness-110">
                    Info Layanan & Harga
                </a>
            </div>
        </section>

        <!-- TENTANG KAMI -->
        <section id="tentang" class="scroll-mt-16 md:scroll-mt-20">
            <div class="max-w-7xl mx-auto px-4 py-8 md:py-16">
                <div
                    class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#CDE6EE] to-white ring-1 ring-black/5 shadow-sm">
                    <div
                        class="pointer-events-none absolute -top-24 -right-20 h-72 w-72 rounded-full bg-white/40 blur-3xl">
                    </div>
                    <div
                        class="pointer-events-none absolute -bottom-20 -left-20 h-80 w-80 rounded-full bg-[#84c5de]/20 blur-3xl">
                    </div>

                    <div class="relative p-6 md:p-10">
                        <h2 class="text-2xl md:text-3xl font-bold text-center tracking-tight text-gray-900">Tentang
                            Kami</h2>
                        <p class="mt-2 text-center text-gray-600 max-w-2xl mx-auto">
                            Laundry cepat, bersih, dan wangi dengan layanan ramah—siap jemput dan antar ke rumah Anda.
                        </p>

                        <div class="mt-10 grid md:grid-cols-2 gap-10 items-center">
                            <!-- Kolom kiri (logo) -->
                            <div class="flex justify-center">
                                <div class="relative w-72 md:w-96 lg:w-[420px]">
                                    <img src="{{ asset('images/logo.png') }}" alt="Logo Qxpress Laundry"
                                        class="w-full h-auto object-contain transition-transform duration-300 hover:scale-[1.03]"
                                        onerror="this.style.display='none'">
                                    <div
                                        class="pointer-events-none absolute bottom-0 right-0 w-24 h-24 bg-[#084cac]/10 blur-2xl">
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom kanan (teks) -->
                            <div>
                                <h3
                                    class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight text-center md:text-left">
                                    Qxpress Laundry
                                </h3>
                                <p class="mt-3 text-gray-700 text-center md:text-left">
                                    Kami berkomitmen menghadirkan pengalaman laundry yang mulus: harga jelas, proses
                                    rapi, dan hasil memuaskan.
                                </p>

                                <!-- Keunggulan -->
                                <div class="mt-6">
                                    <h4 class="font-semibold text-gray-900 mb-4">Keunggulan</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="flex items-center gap-3 p-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-sm md:text-base text-gray-800">Layanan antar
                                                jemput</span>
                                        </div>
                                        <div class="flex items-center gap-3 p-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-sm md:text-base text-gray-800">Proses cepat & tepat
                                                waktu</span>
                                        </div>
                                        <div class="flex items-center gap-3 p-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-sm md:text-base text-gray-800">Cuci setrika bersih &
                                                wangi</span>
                                        </div>
                                        <div class="flex items-center gap-3 p-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-sm md:text-base text-gray-800">Harga terjangkau</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Info Kontak -->
                                <div class="mt-6 space-y-2 text-gray-800 text-sm md:text-base">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#084cac]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span><strong>Buka:</strong> 06.00 – 22.00 WIB (Setiap hari)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#084cac]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.25 6.75A2.25 2.25 0 014.5 4.5h1.8a1.5 1.5 0 011.41 1.02l.7 2.1a1.5 1.5 0 01-.36 1.53L7 10.8a12.5 12.5 0 006.2 6.2l1.65-1.05a1.5 1.5 0 011.53-.36l2.1.7A1.5 1.5 0 0120 18.7v1.8a2.25 2.25 0 01-2.25 2.25c-8.1 0-14.25-6.15-14.25-14.25z" />
                                        </svg>
                                        <span><strong>0813-7382-0217</strong></span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#084cac]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 21s-7.5-4.5-7.5-10.5a7.5 7.5 0 1115 0C19.5 16.5 12 21 12 21z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span><strong>Jl. Airan Raya No.139</strong>, Way Hui, Jati Agung, Lampung
                                            Selatan</span>
                                    </div>
                                </div>

                                <!-- CTA Buttons -->
                                <div class="mt-6 flex flex-wrap gap-3">
                                    <a href="https://wa.me/6281373820217?text=Halo%20Qxpress%20Laundry%2C%20mau%20jemput%20cucian%20ya"
                                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl text-white bg-[#084cac] shadow hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#084cac]/60">
                                        Pesan Antar-Jemput
                                    </a>
                                    <a href="https://maps.app.goo.gl/wZTuH7kCyBtoT3P68"
                                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl text-[#084cac] bg-white/70 ring-1 ring-black/5 hover:bg-white shadow">
                                        Lihat Peta
                                    </a>
                                    <a href="{{ route('tracking', false) ?: '#' }}"
                                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl text-[#084cac] bg-white/70 ring-1 ring-black/5 hover:bg-white shadow">
                                        Lacak Pesanan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA AKHIR -->
        <section id="pesan" class="scroll-mt-16 md:scroll-mt-20 max-w-7xl mx-auto px-4 py-4 md:py-16">
            <div class="grid md:grid-cols-2 gap-6 md:gap-10">
                <div class="h-full min-h-[22rem] flex flex-col justify-center space-y-3 md:space-y-5 lg:space-y-6 py-8 md:py-0">
                    <p class="text-gray-700">Nikmati Waktu Santai dan Ketenangan</p>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight">
                        Qxpress Laundry<br />Bikin Nyantai
                    </h2>
                    <p class="text-gray-600">"Layanan laundry berkualitas dengan harga terjangkau, proses cepat, dan
                        pelayanan ramah."</p>
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

                <!-- Hidden di mobile -->
                <div class="hidden md:block w-full rounded-xl bg-gray-200 overflow-hidden">
                    <img src="{{ asset('images/cta.jpg') }}" alt="CTA"
                        class="w-full h-72 md:h-[22rem] object-cover" onerror="this.style.display='none'">
                </div>
            </div>
        </section>


    </main>

    <x-site-footer />

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>

</html>
