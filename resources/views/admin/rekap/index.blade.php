@extends('admin.layout')
@section('title', 'Rekap Keuangan – Qxpress Laundry')

@section('content')

    @php
        $isToday = ($day ?? now())->isToday();
        $isYesterday = ($day ?? now())->isYesterday();
        $isEditable = $isToday || $isYesterday; // H atau H-1 bisa edit
    @endphp

    <!-- {{-- Filter Tanggal --}} -->
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <form method="GET" action="{{ route('admin.rekap.index') }}" class="flex items-center gap-2">
            <input type="date" name="d" value="{{ request('d', optional($day ?? now())->toDateString()) }}"
                class="border rounded-lg px-3 py-2" />
            <button class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:brightness-110">
                Tampilkan
            </button>
        </form>
    </div>

    <div id="rekap-sheet" class="mt-2 p-2">
        <div class="text-sm text-gray-600 ml-auto pb-2">
            Menampilkan rekap tanggal:
            <strong>{{ \Carbon\Carbon::parse(request('d', optional($day ?? now())->toDateString()))->translatedFormat('l, d M Y') }}</strong>
        </div>

        @php
            $isToday = ($day ?? now())->isToday();
            $isYesterday = ($day ?? now())->isYesterday();
            $isEditable = $isToday || $isYesterday;
        @endphp

        {{-- Banner untuk H-1 (Mode Revisi) --}}
        @if ($isYesterday)
            <div
                class="mt-3 mb-4 p-3 rounded-lg border border-orange-300 bg-orange-50 text-orange-800 text-sm font-medium flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <strong>MODE REVISI KEMARIN (H-1)</strong><br>
                    Anda dapat mengedit data kemarin untuk memperbaiki kesalahan rekap. 
                    <strong>Perhatian:</strong> Perubahan pada data kemarin akan mempengaruhi perhitungan saldo hari ini. 
                    Pastikan revisi dilakukan dengan hati-hati.
                </div>
            </div>
        @endif

        {{-- Banner untuk H-2 dan sebelumnya (Read-only) --}}
        @if (!$isToday && !$isYesterday)
            <div
                class="mt-3 mb-4 p-3 rounded-lg border border-yellow-300 bg-yellow-50 text-yellow-800 text-sm font-medium flex items-start gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M12 2a10 10 0 11-10 10A10 10 0 0112 2z" />
                </svg>
                <span>
                    Mode tampilan baca saja. Data pada tanggal ini <strong>tidak bisa diubah maupun dihapus</strong>.
                    Untuk melakukan input atau update, silakan kembali ke tanggal hari ini.
                </span>
            </div>
        @endif

        <!-- {{-- Ringkasan Keuangan (atas) --}} -->
        <div class="grid md:grid-cols-4 gap-4 capture-desktop-4">
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-green-500">
                <div class="text-sm opacity-70 font-bold">Total Cash Laundry (Akumulasi)</div>
                <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalCashAdj, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">Saldo kemarin: Rp
                    {{ number_format($saldoCashKemarin, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    @if (($openingCashForDisplay ?? 0) != 0)
                        <div>+ Saldo tunai opening: Rp {{ number_format($openingCashForDisplay, 0, ',', '.') }}</div>
                    @endif
                    @if (($penjualanTunaiHariIni ?? 0) != 0)
                        <div>+ Tunai hari ini: Rp {{ number_format($penjualanTunaiHariIni, 0, ',', '.') }}</div>
                    @endif
                    @if (($pelunasanBonTunaiHariIni ?? 0) != 0)
                        <div>+ Pelunasan bon (tunai): Rp {{ number_format($pelunasanBonTunaiHariIni, 0, ',', '.') }}</div>
                    @endif
                    @if (($pengeluaranTunaiMurniHariIni ?? 0) != 0)
                        <div>– Pengeluaran tunai: Rp {{ number_format($pengeluaranTunaiMurniHariIni, 0, ',', '.') }}</div>
                    @endif
                    @if (($tarikKasHariIni ?? 0) != 0)
                        <div>– Tarik kas: Rp {{ number_format($tarikKasHariIni, 0, ',', '.') }}</div>
                    @endif
                    @if (($feeOngkirHariIni ?? 0) != 0)
                        <div>– Fee Antar-Jemput harian: Rp {{ number_format($feeOngkirHariIni, 0, ',', '.') }}</div>
                    @endif
                    @if (($gajiHariIni ?? 0) != 0)
                        <div>– Gaji: Rp {{ number_format($gajiHariIni, 0, ',', '.') }}</div>
                    @endif
                    @if (($totalFee ?? 0) != 0)
                        <div>– Fee hari ini: Rp {{ number_format($totalFee, 0, ',', '.') }}</div>
                    @endif
                    @if (($ajQrisHariIni ?? 0) != 0)
                        <div>– Ongkir Qris: Rp {{ number_format($ajQrisHariIni, 0, ',', '.') }}</div>
                    @endif
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-red-500">
                <div class="text-sm opacity-70 font-bold">Total Bon Pelanggan (Akumulasi)</div>
                <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalPiutang ?? 0, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">Bon kemarin: Rp {{ number_format($bonKemarin, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">
                    @if (($bonMasukHariIni ?? 0) != 0)
                        <div>+ Bon masuk hari ini: Rp {{ number_format($bonMasukHariIni, 0, ',', '.') }}</div>
                    @endif
                    @if (($bonDilunasiHariIni ?? 0) != 0)
                        <div>– Bon dilunasi hari ini: Rp {{ number_format($bonDilunasiHariIni, 0, ',', '.') }}</div>
                    @endif
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-yellow-500">
                <div class="text-sm opacity-70 font-bold">Total Fee Karyawan Hari ini</div>
                <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalFee ?? 0, 0, ',', '.') }}</div>

                {{-- Rincian kategori (muncul hanya jika ada) --}}
                <div class="text-xs text-gray-500 mt-1">
                    @if (($kgLipatTerbayar ?? 0) > 0 && ($feeLipat ?? 0) > 0)
                        <div>Lipat {{ $kgLipatTerbayar }} Kg: Rp {{ number_format($feeLipat, 0, ',', '.') }}</div>
                    @endif

                    @if (($setrikaKgTotal ?? 0) > 0 && ($feeSetrika ?? 0) > 0)
                        <div>Setrika {{ $setrikaKgTotal }} Kg: Rp {{ number_format($feeSetrika, 0, ',', '.') }}</div>
                    @endif

                    @if (($bedCoverCount ?? 0) > 0 && ($feeBedCover ?? 0) > 0)
                        <div>Bed Cover {{ $bedCoverCount }} pcs: Rp {{ number_format($feeBedCover, 0, ',', '.') }}</div>
                    @endif

                    @php $totalHordeng = ($hordengKecilCount ?? 0) + ($hordengBesarCount ?? 0); @endphp
                    @if ($totalHordeng > 0 && ($feeHordeng ?? 0) > 0)
                        <div>Hordeng {{ $totalHordeng }} pcs: Rp {{ number_format($feeHordeng, 0, ',', '.') }}</div>
                    @endif

                    @php $totalBoneka = ($bonekaBesarCount ?? 0) + ($bonekaKecilCount ?? 0); @endphp
                    @if ($totalBoneka > 0 && ($feeBoneka ?? 0) > 0)
                        <div>Boneka {{ $totalBoneka }} pcs: Rp {{ number_format($feeBoneka, 0, ',', '.') }}</div>
                    @endif

                    @if (($satuanCount ?? 0) > 0 && ($feeSatuan ?? 0) > 0)
                        <div>Satuan {{ $satuanCount }} pcs: Rp {{ number_format($feeSatuan, 0, ',', '.') }}</div>
                    @endif
                </div>

                {{-- Sisa lipat carry-over (muncul hanya jika > 0) --}}
                @if (($sisaLipatBaru ?? 0) > 0)
                    <div class="text-[11px] text-gray-500 mt-2">
                        Sisa kg lipat (Akumulasi):
                        <strong>{{ $sisaLipatBaru }} Kg</strong>
                    </div>
                @endif
            </div>

            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-500">
                <div class="text-sm opacity-70 font-bold">Total Omset Bersih Hari Ini</div>
                <div class="mt-2 text-3xl font-bold">Rp {{ number_format($totalOmzetBersihHariIni, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">(Kotor: Rp
                    {{ number_format($totalOmzetKotorHariIni, 0, ',', '.') }} −
                    Fee: Rp {{ number_format($totalFee, 0, ',', '.') }})</div>
                <div class="text-xs text-gray-500 mt-1">Tunai: Rp {{ number_format($totalTunaiHariIni, 0, ',', '.') }} •
                    Qris:
                    Rp {{ number_format($totalQrisHariIni, 0, ',', '.') }} • Bon: Rp
                    {{ number_format($totalBonHariIni, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="mt-4 grid md:grid-cols-3 gap-4 capture-desktop-3">
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-gray-800">
                <div class="text-sm opacity-70 font-bold">Sisa Saldo Kartu Hari Ini</div>
                <div class="mt-2 text-3xl font-bold">
                    {{ is_null($saldoKartu) ? '—' : 'Rp ' . number_format($saldoKartu, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    Saldo kartu kemarin: {{ is_null($saldoPrev) ? '—' : 'Rp ' . number_format($saldoPrev, 0, ',', '.') }}
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-gray-800">
                <div class="text-sm opacity-70 font-bold">Total Tap Kartu Hari Ini</div>
                <div class="mt-2 text-3xl font-bold">
                    {{ $totalTapHariIni === null || (int) $totalTapHariIni === 0 ? '—' : (int) $totalTapHariIni }}
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-gray-800">
                <div class="text-sm opacity-70 font-bold">Tap Gagal Hari Ini</div>
                <div class="mt-2 text-3xl font-bold">
                    {{ $tapGagalHariIni === null || (int) $tapGagalHariIni === 0 ? '—' : number_format($tapGagalHariIni, 0, ',', '.') }}
                </div>
            </div>
        </div>


        <!-- {{-- Tabel Omset --}} -->
        <div class="mt-8 bg-white p-5 rounded-xl shadow">
            <div class="font-semibold mb-3">Tabel Omset Hari Ini</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2">No</th>
                            <th class="px-3 py-2 text-left">Nama Layanan</th>
                            <th class="px-3 py-2 text-center">Kuantitas</th>
                            <th class="px-3 py-2 text-center">Harga</th>
                            <th class="px-3 py-2 text-center">Metode</th>
                            <th class="px-3 py-2 text-center">Total</th>
                            <th class="px-3 py-2 text-center no-export">Aksi</th>
                        </tr>
                    </thead>
                    <tbody
                        class="
                    [&_tr:nth-child(odd)]:bg-slate-50/70
                    [&_tr:nth-child(even)]:bg-white
                    [&_tr:hover]:bg-amber-50/40
                  ">
                        @foreach ($omset as $i => $r)
                            <tr class="border-t">
                                <td class="px-3 py-2 text-center">
                                    {{ ($omset->currentPage() - 1) * $omset->perPage() + $loop->iteration }}</td>
                                <td class="px-3 py-2">
                                    {{ $r->service->nama_service ?? '-' }}
                                    @if (optional($r->service)->is_fee_service)
                                        <span
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[11px]
                                                     bg-purple-50 text-purple-700 border border-purple-200"
                                            title="Fee kurir, tidak dihitung ke omzet/cash">
                                            Ongkir
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">{{ $r->qty }}</td>
                                <td class="px-3 py-2 text-center">
                                    Rp
                                    {{ number_format((int) floor(($r->total ?? 0) / max(1, (int) ($r->qty ?? 1))), 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 text-center">{{ $r->metode->nama ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">Rp {{ number_format($r->total, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-center no-export">
                                    @php
                                        $isBonTransaction = optional($r->metode)->nama && strtolower($r->metode->nama) === 'bon';
                                        $canDelete = $isEditable && !($isYesterday && $isBonTransaction);
                                    @endphp
                                    
                                    @if ($canDelete)
                                        <form method="POST"
                                            action="{{ route('admin.rekap.destroy-group', ['d' => request('d')]) }}"
                                            onsubmit="return confirm('Hapus seluruh baris pada grup ini?')">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="service_id" value="{{ $r->service_id }}">
                                            <input type="hidden" name="metode_pembayaran_id"
                                                value="{{ $r->metode_pembayaran_id }}">
                                            <button class="px-3 py-1 text-xs rounded bg-red-600 text-white">Hapus</button>
                                        </form>
                                    @elseif ($isYesterday && $isBonTransaction)
                                        <span class="text-orange-600 text-xs font-medium" title="Transaksi BON tidak dapat dihapus di mode revisi">🔒 BON</span>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $omset->links('pagination::tailwind') }}</div>
        </div>

        <!-- {{-- Tabel Pengeluaran --}} -->
        <div class="mt-8 bg-white p-5 rounded-xl shadow">
            <div class="font-semibold mb-3">Tabel Pengeluaran Hari Ini</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2">No</th>
                            <th class="px-3 py-2 text-left">Nama</th>
                            <th class="px-3 py-2 text-center">Metode</th>
                            <th class="px-3 py-2 text-center">Harga</th>
                            <th class="px-3 py-2 text-center no-export">Aksi</th>
                        </tr>
                    </thead>
                    <tbody
                        class="
                    [&_tr:nth-child(odd)]:bg-slate-50/70
                    [&_tr:nth-child(even)]:bg-white
                    [&_tr:hover]:bg-amber-50/40
                  ">
                        @foreach ($pengeluaran as $i => $r)
                            <tr class="border-t">
                                <td class="px-3 py-2 text-center">{{ $pengeluaran->firstItem() + $i }}</td>
                                <td class="px-3 py-2">
                                    {{ $r->keterangan ?? '-' }}

                                    @php
                                        $keteranganLower = strtolower($r->keterangan ?? '');
                                        // deteksi "owner draw"
                                        $isOwnerDraw = str_contains($keteranganLower, 'bos') || str_contains($keteranganLower, 'kanjeng') || 
                                                       str_contains($keteranganLower, 'ambil duit') || str_contains($keteranganLower, 'ambil duid') || 
                                                       str_contains($keteranganLower, 'tarik kas');
                                        // deteksi "fee antar jemput / ongkir"
                                        $isFeeOngkir = str_contains($keteranganLower, 'ongkir') || 
                                                       str_contains($keteranganLower, 'antar jemput') ||
                                                       str_contains($keteranganLower, 'anter jemput'); 
                                        // deteksi "gaji"
                                        $isGaji = str_contains($keteranganLower, 'gaji');
                                    @endphp

                                    @if ($isFeeOngkir)
                                        <span
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[11px]
                          bg-orange-50 text-orange-700 border border-orange-200"
                                            title="Fee Antar Jemput - Hanya mengurangi Total Cash, tidak masuk perhitungan Pengeluaran">
                                            Fee Antar-Jemput
                                        </span>
                                    @elseif ($isGaji)
                                        <span
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[11px]
                          bg-purple-50 text-purple-700 border border-purple-200"
                                            title="Gaji - Hanya mengurangi Total Cash, tidak masuk perhitungan Pengeluaran">
                                            Gaji
                                        </span>
                                    @elseif ($isOwnerDraw)
                                        <span
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[11px]
                          bg-blue-50 text-blue-700 border border-blue-200"
                                            title="Tidak dihitung sebagai pengeluaran bulan ini">
                                            Tarik Kas
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">{{ $r->metode->nama ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">Rp {{ number_format($r->total, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-center no-export">
                                    @if ($isEditable)
                                        <form method="POST" action="{{ route('admin.rekap.destroy', $r->id) }}"
                                            onsubmit="return confirm('Hapus baris ini?')">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="d" value="{{ request('d', optional($day ?? now())->toDateString()) }}">
                                            <button class="px-3 py-1 text-xs rounded bg-red-600 text-white">Hapus</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $pengeluaran->links('pagination::tailwind') }}</div>
        </div>

        <!-- {{-- Tabel Bon Pelanggan --}} -->
        <div class="mt-8 bg-white p-5 rounded-xl shadow">
            <div class="font-semibold mb-3">Tabel Bon Pelanggan</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2">No</th>
                            <th class="px-3 py-2 text-left">Nama Pelanggan</th>
                            <th class="px-3 py-2 text-left">Layanan</th>
                            <th class="px-3 py-2 text-center">Kuantitas</th>
                            <th class="px-3 py-2 text-center">Total</th>
                            <th class="px-3 py-2 text-center">Metode</th>
                            <th class="px-3 py-2 text-center">Pembayaran</th>
                            <th class="px-3 py-2 text-center">Tanggal Masuk</th>
                        </tr>
                    </thead>
                    <tbody
                        class="
                    [&_tr:nth-child(odd)]:bg-slate-50/70
                    [&_tr:nth-child(even)]:bg-white
                    [&_tr:hover]:bg-amber-50/40
                  ">
                        @forelse($bon as $i => $p)
                            @php
                                $asOfStart = ($day ?? now())->copy()->startOfDay();
                                $asOfEnd = ($day ?? now())->copy()->endOfDay();

                                $qty = max(1, (int) ($p->qty ?? 1));
                                $harga = (int) ($p->harga_satuan ?? ($p->service->harga_service ?? 0));
                                $total = $qty * $harga;

                                $metodeNow = strtolower($p->metode->nama ?? 'bon');

                                $isBonAsOfEnd =
                                    $metodeNow === 'bon' ||
                                    ($metodeNow !== 'bon' && optional($p->updated_at)->gt($asOfEnd));

                                $dibayarHariIni =
                                    $metodeNow !== 'bon' && optional($p->updated_at)->between($asOfStart, $asOfEnd);
                            @endphp
                            <tr class="border-t">
                                <td class="px-3 py-2">{{ ($bon->currentPage() - 1) * $bon->perPage() + $loop->iteration }}
                                </td>

                                <td class="px-3 py-2">
                                    <div class="font-medium">{{ $p->nama_pel }}</div>
                                </td>

                                <td class="px-3 py-2">{{ $p->service->nama_service ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $qty }}</td>
                                <td class="px-3 py-2 text-center">Rp {{ number_format($total, 0, ',', '.') }}</td>

                                {{-- Metode (dropdown) --}}
                                <td class="px-3 py-2 text-center">
                                    @php $current = $metodeNow; @endphp

                                    {{-- Form dropdown (sembunyikan saat export) --}}
                                    <form method="POST" action="{{ route('admin.rekap.update-bon', $p) }}"
                                        class="no-export inline-block m-0"
                                        onsubmit="return confirm('Yakin ubah metode pembayaran?');">
                                        @csrf
                                        @method('PATCH')
                                        @php
                                            $metColor = match ($current) {
                                                'bon' => 'bg-yellow-100 border-yellow-400 text-yellow-800',
                                                'tunai' => 'bg-green-100 border-green-400 text-green-800',
                                                'qris' => 'bg-blue-100 border-blue-400 text-blue-800',
                                                default => 'bg-white border-gray-300 text-gray-700',
                                            };
                                        @endphp

                                        <select @disabled(!$isEditable) name="metode"
                                            class="border rounded px-2 py-1 text-xs appearance-none pr-6 bg-no-repeat {{ $metColor }}"
                                            style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-position:right 0.45rem center;"
                                            data-current="{{ $current }}"
                                            onchange="
                                              if (confirm('Ubah metode menjadi ' + this.options[this.selectedIndex].text + '?')) {
                                                this.form.submit();
                                              } else {
                                                this.value = this.getAttribute('data-current');
                                              }
                                            ">
                                            <option value="bon" {{ $current === 'bon' ? 'selected' : '' }}>Bon
                                            </option>
                                            <option value="tunai" {{ $current === 'tunai' ? 'selected' : '' }}>Tunai
                                            </option>
                                            <option value="qris" {{ $current === 'qris' ? 'selected' : '' }}>QRIS
                                            </option>
                                        </select>


                                    </form>

                                    {{-- Teks statis pengganti (hanya tampil saat export) --}}
                                    <span class="export-only hidden text-xs">
                                        @switch($current)
                                            @case('tunai')
                                                Tunai
                                            @break

                                            @case('qris')
                                                QRIS
                                            @break

                                            @default
                                                Bon
                                        @endswitch
                                    </span>
                                </td>

                                {{-- Pembayaran badge --}}
                                <td class="px-3 py-2 text-center">
                                    @if ($isBonAsOfEnd)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-50 text-red-700 border border-red-200">
                                            Belum Lunas
                                        </span>
                                    @else
                                        <div class="flex flex-col items-center gap-1">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-50 text-green-700 border border-green-200">
                                                Lunas
                                            </span>
                                            @if ($dibayarHariIni)
                                                <p class="text-[11px] text-gray-500">dibayar hari ini</p>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- Tanggal Masuk --}}
                                <td class="px-3 py-2 text-center">{{ optional($p->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-4 text-center text-gray-500">Tidak ada data bon
                                        pelanggan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">{{ $bon->links('pagination::tailwind') }}</div>
            </div>
        </div> <!-- end rekap-sheet -->

        <!-- {{-- Tombol Input Rekap --}} -->
        <div class="mt-6 text-right">
            @if ($isEditable)
                <a href="{{ route('admin.rekap.input', ['d' => request('d', optional($day ?? now())->toDateString())]) }}"
                    class="inline-flex items-center gap-2 rounded-lg {{ $isYesterday ? 'bg-orange-600' : 'bg-blue-600' }} text-white px-4 py-2 hover:brightness-110">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $isYesterday ? 'Revisi Rekap Kemarin' : 'Input & Update Rekap' }}
                </a>
            @endif
            <button id="btn-download-jpg"
                class="inline-flex items-center gap-2 rounded-lg bg-gray-800 text-white px-4 py-2 hover:brightness-110 no-export">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download JPG
        </div>

        <!-- html-to-image (CDN + fallback) -->
        <script defer src="https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.min.js"></script>
        <script>
            window.addEventListener('load', function() {
                if (!window.htmlToImage) {
                    const s = document.createElement('script');
                    s.defer = true;
                    s.src = 'https://unpkg.com/html-to-image@1.11.11/dist/html-to-image.min.js';
                    document.head.appendChild(s);
                }
            });
        </script>

        <!-- Style khusus saat capture (paksa tampilan desktop & hilangkan scroll)
                                                                                             >>> TIDAK mematikan shadow supaya kartu tetap “mengambang” seperti di layar <<< -->
        <style>
            /* aktif hanya saat root diberi .capture-mode */
            #rekap-sheet.capture-mode {
                background: #ffffff !important;
                padding: 16px;
            }

            /* jangan sembunyikan shadow — biarkan seperti UI */
            /* #rekap-sheet.capture-mode .shadow { box-shadow: none !important; }  <-- DIHAPUS */

            /* hilangkan overflow agar tabel tak terpotong */
            #rekap-sheet.capture-mode .overflow-x-auto,
            #rekap-sheet.capture-mode .overflow-y-auto,
            #rekap-sheet.capture-mode .overflow-auto {
                overflow: visible !important;
            }

            #rekap-sheet.capture-mode ::-webkit-scrollbar {
                display: none !important;
            }

            /* default: teks statis disembunyikan */
            #rekap-sheet .export-only {
                display: none;
            }

            /* saat capture/export: sembunyikan form, tampilkan teks */
            #rekap-sheet.capture-mode .no-export {
                display: none !important;
            }

            #rekap-sheet.capture-mode .export-only {
                display: inline !important;
            }


            /* Paksa grid desktop saat capture (abaikan breakpoint “md”) */
            #rekap-sheet.capture-mode .capture-desktop-4 {
                display: grid !important;
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                gap: 1rem !important;
            }

            #rekap-sheet.capture-mode .capture-desktop-3 {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 1rem !important;
            }
        </style>

        <script>
            (function() {
                const btn = document.getElementById('btn-download-jpg'); // tombol
                const root = document.getElementById('rekap-sheet'); // area yang di-capture
                if (!btn || !root) return;

                const EXPORT_WIDTH = 1280; // render selebar desktop

                function toggleCapture(on) {
                    if (on) {
                        root.classList.add('capture-mode');
                    } else {
                        root.classList.remove('capture-mode');
                        root.style.width = '';
                        root.style.height = '';
                        root.style.maxWidth = '';
                    }
                }

                btn.addEventListener('click', async () => {
                    try {
                        if (!window.htmlToImage) {
                            alert('Library html-to-image belum termuat. Coba refresh halaman.');
                            return;
                        }

                        // 1) Aktifkan mode capture (paksa desktop & non-scroll)
                        toggleCapture(true);

                        // 2) Paksa lebar desktop agar layout mengikuti desktop
                        root.style.maxWidth = 'none';
                        root.style.width = EXPORT_WIDTH + 'px';
                        await new Promise(r => requestAnimationFrame(r)); // biar reflow dulu

                        // 3) Hitung tinggi penuh konten
                        const w = root.scrollWidth;
                        const h = root.scrollHeight;
                        root.style.height = h + 'px';

                        // 4) Render ke JPG (pixelRatio dinaikkan supaya tajam)
                        const dataUrl = await window.htmlToImage.toJpeg(root, {
                            quality: 0.95,
                            backgroundColor: '#ffffff',
                            pixelRatio: Math.max(2, window.devicePixelRatio || 1),
                            style: {
                                width: w + 'px',
                                height: h + 'px'
                            },
                            filter: (n) => !n.classList || !n.classList.contains('no-export'),
                        });

                        // 5) Download
                        const dateLabel =
                            "{{ \Illuminate\Support\Str::of(request('d', optional($day ?? now())->toDateString()))->replace(':', '-') }}";
                        const a = document.createElement('a');
                        a.href = dataUrl;
                        a.download = `rekap-${dateLabel}.jpg`;
                        a.click();

                    } catch (e) {
                        alert('Gagal membuat JPG: ' + (e?.message || e));
                        console.error(e);
                    } finally {
                        // 6) Balik ke tampilan normal
                        toggleCapture(false);
                    }
                });
            })();
        </script>

    @endsection
