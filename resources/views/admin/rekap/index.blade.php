@extends('admin.layout')
@section('title', 'Rekap Keuangan – Qxpress Laundry')

@section('content')

    @php
        $isToday = ($day ?? now())->isToday();
        $isYesterday = ($day ?? now())->isYesterday();
        $isEditable = $isToday || $isYesterday;
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
                <div class="text-xs text-gray-500 mt-1 font-bold">Saldo kemarin: Rp
                    {{ number_format($saldoCashKemarin, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-500 mt-1 font-bold">
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
                <div class="text-xs text-gray-500 mt-1 font-bold">Bon kemarin: Rp {{ number_format($bonKemarin, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1 font-bold">
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
                <div class="text-xs text-gray-500 mt-1 font-bold">
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
                <div class="text-xs text-gray-500 mt-1 font-bold">(Kotor: Rp
                    {{ number_format($totalOmzetKotorHariIni, 0, ',', '.') }} −
                    Fee: Rp {{ number_format($totalFee, 0, ',', '.') }})</div>
                <div class="text-xs text-gray-500 mt-1 font-bold">Tunai: Rp {{ number_format($totalTunaiHariIni, 0, ',', '.') }} •
                    Qris:
                    Rp {{ number_format($totalQrisHariIni, 0, ',', '.') }} • Bon: Rp
                    {{ number_format($totalBonHariIni, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1 font-bold">Saldo Qris hari ini: Rp {{ number_format($saldoQrHariIni, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="mt-4 grid md:grid-cols-3 gap-4 capture-desktop-3">
            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-gray-800">
                <div class="text-sm opacity-70 font-bold">Sisa Saldo Kartu Hari Ini</div>
                <div class="mt-2 text-3xl font-bold">
                    {{ is_null($saldoKartu) ? '—' : 'Rp ' . number_format($saldoKartu, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-500 mt-1 font-bold">
                    Saldo kartu kemarin: {{ is_null($saldoPrev) ? '—' : 'Rp ' . number_format($saldoPrev, 0, ',', '.') }}
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow border-l-4 border-gray-800">
                <div class="text-sm opacity-70 font-bold">Total Tap Kartu Hari Ini</div>
                <div class="mt-2 text-3xl font-bold">
                    {{ $totalTapHariIni === null || (int) $totalTapHariIni === 0 ? '—' : (int) $totalTapHariIni }}
                </div>
                <div class="text-xs text-gray-600 mt-2">
                    Expected Tap: <span class="font-bold {{ $expectedTapHariIni > 0 && $totalTapHariIni > 0 && $totalTapHariIni >= $expectedTapHariIni ? 'text-green-600' : 'text-amber-600' }}">{{ $expectedTapHariIni }}</span>
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
                <table class="min-w-full text-sm table-fixed">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 w-16">No</th>
                            <th class="px-3 py-2 text-left w-24">Nama Layanan</th>
                            <th class="px-3 py-2 text-center w-24">Metode</th>
                            <th class="px-3 py-2 text-center w-24">Kuantitas</th>
                            <th class="px-3 py-2 text-center w-24">Total</th>
                            <th class="px-3 py-2 text-center w-32 no-export">Aksi</th>
                        </tr>
                    </thead>
                    <tbody
                        class="
                    [&_tr:nth-child(odd)]:bg-slate-50/70
                    [&_tr:nth-child(even)]:bg-white
                    [&_tr:hover]:bg-amber-50/40
                  ">
                        @php
                            // Fungsi untuk aliasing nama layanan
                            function getServiceAlias($namaService) {
                                $aliases = [
                                    'Cuci Self Service ≤7Kg' => 'Cuci',
                                    'Kering Self Service ≤7Kg' => 'Kering',
                                    'Cuci Lipat Express ≤7Kg' => 'CKL E',
                                    'Cuci Setrika Express ≤3Kg' => 'CKS E 3Kg',
                                    'Cuci Setrika Express ≤5Kg' => 'CKS E 5Kg',
                                    'Cuci Setrika Express ≤7Kg' => 'CKS E 7Kg',
                                ];
                                return $aliases[$namaService] ?? $namaService;
                            }
                            
                            // Urutkan berdasarkan nama layanan (abjad), lalu metode
                            $sortedOmset = $omset->sortBy(function($item) {
                                $metodeName = strtolower($item->metode->nama ?? '');
                                // Prioritas: tunai (1), qris (2), lainnya (3)
                                $metodePriority = match($metodeName) {
                                    'tunai' => 1,
                                    'qris' => 2,
                                    default => 3,
                                };
                                return ($item->service->nama_service ?? '') . '_' . $metodePriority;
                            });
                            
                            // Group berdasarkan service_id
                            $groupedOmset = $sortedOmset->groupBy('service_id');
                            $rowCounter = ($omset->currentPage() - 1) * $omset->perPage();
                        @endphp
                        
                        @foreach ($groupedOmset as $serviceId => $items)
                            @foreach ($items as $idx => $r)
                                @php
                                    $isFirstInGroup = $idx === 0;
                                    if ($isFirstInGroup) $rowCounter++;
                                @endphp
                                <tr class="border-t">
                                    {{-- Nomor hanya di baris pertama --}}
                                    @if ($isFirstInGroup)
                                        <td class="px-3 py-2 text-center font-bold">{{ $rowCounter }}</td>
                                        <td class="px-3 py-2 font-bold">
                                            {{ getServiceAlias($r->service->nama_service ?? '-') }}
                                            @if (optional($r->service)->is_fee_service)
                                                <span
                                                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[11px]
                                                             bg-purple-50 text-purple-700 border border-purple-200"
                                                    title="Fee kurir, tidak dihitung ke omzet/cash">
                                                    Ongkir
                                                </span>
                                            @endif
                                        </td>
                                    @else
                                        <td class="px-3 py-2 text-center"></td>
                                        <td class="px-3 py-2"></td>
                                    @endif
                                    
                                    <td class="px-3 py-2 text-center font-bold">{{ $r->metode->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center font-bold">{{ $r->qty }}</td>
                                    <td class="px-3 py-2 text-center font-bold">Rp {{ number_format($r->total, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-center no-export">
                                        {{-- Tombol Edit dengan modal - hanya aktif untuk hari ini dan H-1 --}}
                                        @php
                                            $tanggalData = request('d', optional($day ?? now())->toDateString());
                                            $dataDate = \Carbon\Carbon::parse($tanggalData);
                                            $today = \Carbon\Carbon::today();
                                            $daysDiff = $today->diffInDays($dataDate, false); // false = bisa negatif
                                            
                                            // Aktif jika: hari ini (0) atau kemarin (-1)
                                            // Disabled jika: lebih dari 2 hari yang lalu (< -1)
                                            $isEditable = $daysDiff >= -1;
                                        @endphp
                                        
                                        <button type="button" 
                                            onclick="openOmsetModal({{ $r->service_id }}, {{ $r->metode_pembayaran_id }}, '{{ $tanggalData }}')"
                                            class="px-3 py-1 text-xs rounded {{ $isEditable ? 'bg-blue-600 text-white hover:brightness-110' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}" 
                                            title="{{ $isEditable ? 'Lihat detail dan hapus' : 'Hanya bisa edit data hari ini dan kemarin' }}"
                                            {{ $isEditable ? '' : 'disabled' }}>
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
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
                <table class="min-w-full text-sm table-fixed">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 w-8">No</th>
                            <th class="px-3 py-2 text-left w-16">Nama</th>
                            <th class="px-3 py-2 text-center w-16">Metode</th>
                            <th class="px-3 py-2 text-center w-16">Harga</th>
                            <th class="px-3 py-2 text-center w-16 no-export">Aksi</th>
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
                                <td class="px-3 py-2 text-center font-bold">{{ $pengeluaran->firstItem() + $i }}</td>
                                <td class="px-3 py-2 font-bold">
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
                                        
                                        // cek apakah ada badge yang akan ditampilkan
                                        $hasBadge = $isFeeOngkir || $isGaji || $isOwnerDraw;
                                    @endphp

                                    @if (!$hasBadge)
                                        {{ $r->keterangan ?? '-' }}
                                    @endif

                                    @if ($isFeeOngkir)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[11px]
                          bg-orange-50 text-orange-700 border border-orange-200"
                                            title="Fee Antar Jemput - Hanya mengurangi Total Cash, tidak masuk perhitungan Pengeluaran">
                                            Fee Antar-Jemput
                                        </span>
                                    @elseif ($isGaji)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[11px]
                          bg-purple-50 text-purple-700 border border-purple-200"
                                            title="Gaji - Hanya mengurangi Total Cash, tidak masuk perhitungan Pengeluaran">
                                            Gaji
                                        </span>
                                    @elseif ($isOwnerDraw)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[11px]
                          bg-blue-50 text-blue-700 border border-blue-200"
                                            title="Tidak dihitung sebagai pengeluaran bulan ini">
                                            Tarik Kas
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center font-bold">{{ $r->metode->nama ?? '-' }}</td>
                                <td class="px-3 py-2 text-center font-bold">Rp {{ number_format($r->total, 0, ',', '.') }}</td>
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
                        @php
                            $groupedBon = [];
                            
                            foreach ($bon as $p) {
                                $groupId = $p->group_id ?? 'single-' . $p->id;
                                
                                if (!isset($groupedBon[$groupId])) {
                                    $groupedBon[$groupId] = [
                                        'main' => $p,
                                        'additional' => []
                                    ];
                                } else {
                                    $groupedBon[$groupId]['additional'][] = $p;
                                }
                            }
                            
                            $rowNumber = ($bon->currentPage() - 1) * $bon->perPage();
                        @endphp
                        
                        @forelse($groupedBon as $groupId => $group)
                            @php
                                $rowNumber++;
                                $p = $group['main'];
                                $additionalServices = $group['additional'];
                                
                                $asOfStart = ($day ?? now())->copy()->startOfDay();
                                $asOfEnd = ($day ?? now())->copy()->endOfDay();

                                // Hitung total pesanan utama
                                $qty = max(1, (int) ($p->qty ?? 1));
                                $harga = (int) ($p->harga_satuan ?? ($p->service->harga_service ?? 0));
                                $totalLayanan = $qty * $harga;
                                
                                // Hitung antar jemput jika ada
                                $hargaAntarJemput = 0;
                                if ($p->antar_jemput_service_id && $p->antarJemputService) {
                                    $hargaAntarJemput = (int) ($p->antarJemputService->harga_service ?? 0);
                                }
                                
                                // Hitung total dari layanan tambahan
                                $totalAdditional = 0;
                                foreach ($additionalServices as $addServ) {
                                    $qtyAdd = max(1, (int) ($addServ->qty ?? 1));
                                    $hargaAdd = (int) ($addServ->harga_satuan ?? ($addServ->service->harga_service ?? 0));
                                    $totalAdditional += $qtyAdd * $hargaAdd;
                                }
                                
                                $total = $totalLayanan + $hargaAntarJemput + $totalAdditional;

                                $metodeNow = strtolower($p->metode->nama ?? 'bon');

                                $isBonAsOfEnd =
                                    $metodeNow === 'bon' ||
                                    ($metodeNow !== 'bon' && optional($p->updated_at)->gt($asOfEnd));

                                $dibayarHariIni =
                                    $metodeNow !== 'bon' && optional($p->updated_at)->between($asOfStart, $asOfEnd);
                            @endphp
                            <tr class="border-t">
                                <td class="px-3 py-2 font-bold">{{ $rowNumber }}</td>

                                <td class="px-3 py-2 font-bold">
                                    <div class="font-bold">{{ $p->nama_pel }}</div>
                                </td>

                                <td class="px-3 py-2 font-bold">
                                    <div>{{ $p->service->nama_service ?? '-' }} ({{ $qty }}x)</div>
                                    @foreach ($additionalServices as $addServ)
                                        <div class="mt-0.5">
                                            {{ $addServ->service->nama_service ?? '-' }} ({{ $addServ->qty }}x)
                                        </div>
                                    @endforeach
                                    @if ($p->antar_jemput_service_id && $p->antarJemputService)
                                        <div class="text-xs text-gray-500 mt-0.5 font-normal">{{ $p->antarJemputService->nama_service }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center font-bold">Rp {{ number_format($total, 0, ',', '.') }}</td>

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
                                    <span class="export-only hidden text-xs font-bold">
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
                                <td class="px-3 py-2 text-center font-bold">{{ optional($p->created_at)->format('d/m/Y') }}</td>
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

        <!-- Modal Detail Omset -->
        <div id="omsetModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closeOmsetModal()">
            <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                <!-- Header -->
                <div class="sticky top-0 bg-[#084cac] text-white px-6 py-4 flex items-center justify-between rounded-t-xl">
                    <h3 class="text-xl font-bold">Detail Omset</h3>
                    <button onclick="closeOmsetModal()" class="text-white hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama Layanan</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Metode</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Kuantitas</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700">Total</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Pelanggan</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Waktu Input</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="omsetModalBody" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        <div class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Memuat data...
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Script untuk Modal Omset -->
        <script>
            function openOmsetModal(serviceId, metodeId, tanggal) {
                const modal = document.getElementById('omsetModal');
                const modalBody = document.getElementById('omsetModalBody');
                
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                
                // Loading state
                modalBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Memuat data...
                            </div>
                        </td>
                    </tr>
                `;
                
                // Fetch data
                fetch(`/admin/rekap/detail?service_id=${serviceId}&metode_id=${metodeId}&tanggal=${tanggal}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data); // Debug log
                        
                        if (data.success && data.data && data.data.length > 0) {
                            modalBody.innerHTML = '';
                            data.data.forEach(r => {
                                const row = document.createElement('tr');
                                row.className = 'hover:bg-gray-50 border-b';
                                row.innerHTML = `
                                    <td class="px-4 py-3">${r.service_name || '-'}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex px-2 py-1 rounded text-xs font-semibold ${getMetodeBadge(r.metode)}">
                                            ${r.metode || '-'}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold">${r.qty}</td>
                                    <td class="px-4 py-3 text-right font-semibold">Rp ${formatRupiah(r.total)}</td>
                                    <td class="px-4 py-3">
                                        ${r.pelanggan_name || '-'}
                                        ${r.pelanggan_hp ? `<div class="text-xs text-gray-500">${r.pelanggan_hp}</div>` : ''}
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        <div>${r.tanggal}</div>
                                        <div class="text-gray-500">${r.jam}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button onclick="deleteOmset(${r.id}, ${r.pesanan_laundry_id || 'null'})" 
                                            class="px-3 py-1.5 text-xs rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
                                            Hapus
                                        </button>
                                    </td>
                                `;
                                modalBody.appendChild(row);
                            });
                        } else {
                            modalBody.innerHTML = `
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        ${data.message || 'Tidak ada data'}
                                    </td>
                                </tr>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modalBody.innerHTML = `
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center">
                                    <div class="text-red-600 font-semibold mb-2">Terjadi kesalahan saat memuat data</div>
                                    <div class="text-sm text-gray-600">${error.message || error}</div>
                                </td>
                            </tr>
                        `;
                    });
            }

            function closeOmsetModal() {
                const modal = document.getElementById('omsetModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function deleteOmset(rekapId, pesananLaundryId) {
                if (!confirm('Apakah Anda yakin ingin menghapus data ini?\n\nData rekap akan dihapus' + (pesananLaundryId ? ' beserta data pesanan laundry terkait.' : '.'))) {
                    return;
                }

                fetch(`/admin/rekap/delete-omset/${rekapId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Data berhasil dihapus');
                        closeOmsetModal();
                        window.location.reload();
                    } else {
                        alert(data.message || 'Gagal menghapus data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus data');
                });
            }

            function formatRupiah(angka) {
                return new Intl.NumberFormat('id-ID').format(angka);
            }

            function getMetodeBadge(metode) {
                const m = (metode || '').toLowerCase();
                if (m === 'tunai') return 'bg-green-100 text-green-700 border border-green-300';
                if (m === 'qris') return 'bg-blue-100 text-blue-700 border border-blue-300';
                if (m === 'bon') return 'bg-yellow-100 text-yellow-700 border border-yellow-300';
                return 'bg-gray-100 text-gray-700 border border-gray-300';
            }

            // Close modal with ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeOmsetModal();
                }
            });
        </script>

    @endsection
