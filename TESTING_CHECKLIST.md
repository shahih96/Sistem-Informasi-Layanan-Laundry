# 🧪 Testing Checklist - Sistem Informasi Laundry

## ✅ FITUR YANG SUDAH SELESAI & PERLU DITEST

### 📊 **1. DASHBOARD**
- [ ] Chart omzet bulan berjalan tampil dengan benar
- [ ] Navigasi prev/next bulan berfungsi
- [ ] Dropdown lompat ke bulan tertentu berfungsi
- [ ] Total pesanan hari ini akurat
- [ ] Pendapatan hari ini (kotor) sesuai dengan rekap
- [ ] Pesanan diproses & selesai sesuai status terbaru
- [ ] Riwayat pesanan terbaru tampil 5-10 baris
- [ ] Pengeluaran bulan ini akurat (exclude "tarik kas")
- [ ] Total Cash (s.d. hari ini) sesuai dengan rekap
- [ ] Pendapatan bersih = omzet kotor - fee - pengeluaran
- [ ] Tabel pengeluaran bisa ditampilkan/disembunyikan
- [ ] Badge "Tarik Kas" muncul untuk pengeluaran owner draw

### 🧺 **2. PESANAN LAUNDRY**
- [ ] Form input pesanan berfungsi (nama, HP, service, qty, metode, status)
- [ ] Autocomplete pelanggan berdasarkan nama berfungsi
- [ ] Autocomplete pelanggan berdasarkan HP berfungsi
- [ ] Filter pelanggan: hanya tampil yang punya HP valid (bukan '-' atau kosong)
- [ ] Fuzzy search (Levenshtein) untuk nama & HP berfungsi
- [ ] Dropdown service exclude yang `is_fee_service = 1`
- [ ] Metode pembayaran default = Bon
- [ ] Status awal: Diproses/Selesai
- [ ] Tabel pesanan tampil dengan pagination
- [ ] Kolom: Aksi (X), Pelanggan + No HP (gabung), Layanan, Status, Qty, Total, Pembayaran, Update Terakhir
- [ ] Tombol X (delete) menyembunyikan pesanan (is_hidden = true)
- [ ] Ketika disembunyikan: status otomatis jadi "Selesai" jika sebelumnya "Diproses"
- [ ] Ketika disembunyikan: status tidak duplikat jika sudah "Selesai"
- [ ] Badge "Disembunyikan" muncul untuk pesanan yang hidden
- [ ] Dropdown status bisa diubah (Diproses ↔ Selesai), warna berubah
- [ ] Badge pembayaran: Lunas (hijau) atau Belum Lunas (merah)
- [ ] Responsive mobile: nama & HP di satu kolom, tombol X di kiri

#### **2.1 Migrasi Bon Lama**
- [ ] Form migrasi bon tampil jika belum dikunci
- [ ] Input: nama pelanggan, layanan, qty (metode otomatis Bon)
- [ ] Tidak membuat baris rekap (hanya pesanan)
- [ ] Tombol "Kunci" menyembunyikan form migrasi
- [ ] Setelah dikunci, form tidak muncul lagi

### 💰 **3. REKAP KEUANGAN**
#### **3.1 Halaman Index (Tampilan Baca)**
- [ ] Filter tanggal berfungsi
- [ ] Mode baca saja untuk tanggal selain hari ini (warning kuning tampil)
- [ ] Ringkasan keuangan (4 kartu atas):
  - [ ] Total Cash Laundry (Akumulasi) + breakdown
  - [ ] Total Bon Pelanggan (Akumulasi) + breakdown
  - [ ] Total Fee Karyawan Hari Ini + rincian per kategori
  - [ ] Total Omset Bersih Hari Ini + breakdown
- [ ] Sisa Saldo Kartu Hari Ini (bisa null jika belum input)
- [ ] Total Tap Kartu Hari Ini
- [ ] Tap Gagal Hari Ini
- [ ] Tabel Omset: service + metode + qty + total (dengan badge "Fee Antar-Jemput")
- [ ] Tombol hapus grup omset (hanya untuk hari ini)
- [ ] Pagination omset
- [ ] Tabel Pengeluaran: nama + metode + harga + badge "Tarik Kas"
- [ ] Tombol hapus pengeluaran (hanya untuk hari ini)
- [ ] Pagination pengeluaran
- [ ] Tabel Bon Pelanggan: nama, layanan, qty, total, metode (dropdown), pembayaran (badge), tanggal masuk
- [ ] Urutan kolom bon: Metode → Pembayaran → Tanggal Masuk
- [ ] Dropdown metode bon: Bon/Tunai/QRIS, warna berbeda, bisa diubah (hanya hari ini)
- [ ] Badge pembayaran bon: Lunas (hijau) / Belum Lunas (merah)
- [ ] Jika bon dilunasi hari ini, muncul keterangan "dibayar hari ini"
- [ ] Tombol "Download JPG" berfungsi (export rekap ke gambar)
- [ ] Export JPG: sembunyikan tombol & dropdown, tampilkan teks statis

#### **3.2 Halaman Input & Update**
- [ ] Form opening kas (hanya muncul sekali, bisa dikunci)
- [ ] Input: tanggal cutover + init cash
- [ ] Tombol "Kunci" menyembunyikan form opening
- [ ] Form saldo kartu tampil jika ada data historis
- [ ] Input: saldo akhir + jumlah tap gagal
- [ ] Validasi: saldo tidak boleh > 5 juta
- [ ] Total tap hari ini dihitung otomatis berdasarkan saldo
- [ ] Form omset: service + qty + metode
- [ ] Dropdown service exclude `is_fee_service = 1`
- [ ] Form pengeluaran: keterangan + metode + nominal
- [ ] Tombol simpan berfungsi untuk semua form

#### **3.3 Perhitungan (CRITICAL)**
##### **Cash Flow**
- [ ] Saldo kemarin dihitung dengan benar (setelah perbaikan `prevEnd`)
- [ ] Opening cash ditambahkan jika cutover <= prevEnd
- [ ] Penjualan tunai hari ini dihitung
- [ ] Pelunasan bon tunai hari ini dihitung (termasuk bon migrasi)
- [ ] Pengeluaran tunai hari ini dihitung
- [ ] AJ QRIS tidak mengurangi cash laundry
- [ ] Total Cash = saldo kemarin + tunai masuk + pelunasan bon - pengeluaran - fee - AJ QRIS + opening

##### **Fee Karyawan**
- [ ] Fee lipat: Rp 3.000 per 7 Kg (carry-over berfungsi)
- [ ] Fee setrika: Rp 1.000/Kg atau paket 3Kg/5Kg/7Kg
- [ ] Fee bed cover: Rp 3.000/pcs
- [ ] Fee hordeng (kecil/besar): Rp 3.000/pcs
- [ ] Fee boneka (besar/kecil): Rp 1.000/pcs
- [ ] Fee satuan: Rp 1.000/pcs
- [ ] Sisa lipat carry-over tampil jika > 0

##### **Bon/Piutang**
- [ ] Bon kemarin dihitung
- [ ] Bon masuk hari ini (metode = bon)
- [ ] Bon dilunasi hari ini (bon → tunai/qris)
- [ ] Total piutang akumulasi = bon kemarin + masuk - dilunasi

##### **Saldo Kartu**
- [ ] Saldo kemarin (dari database)
- [ ] Tap hari ini = (saldo kemarin - saldo akhir + tap gagal) / 10.000
- [ ] Validasi: saldo tidak boleh > 5 juta
- [ ] Tap gagal bisa diinput manual

### 🏷️ **4. SERVICES (LAYANAN)**
- [ ] Tabel layanan tampil semua
- [ ] Form tambah layanan: nama + harga
- [ ] Form edit layanan (modal/inline)
- [ ] Hapus layanan dengan konfirmasi
- [ ] Checkbox `is_fee_service` untuk layanan antar jemput
- [ ] Layanan dengan `is_fee_service = 1` tidak muncul di dropdown pesanan/rekap
- [ ] Seeder sudah set `is_fee_service = 1` untuk "Antar Jemput <=5KM" & ">5KM"

### 📋 **5. STATUS PESANAN**
- [ ] Dropdown status di tabel pesanan berfungsi
- [ ] Perubahan status tersimpan ke tabel `status_pesanan`
- [ ] Warna dropdown berubah: Diproses (kuning), Selesai (biru)
- [ ] Status terbaru tampil di dashboard & tabel pesanan

### 💳 **6. SALDO KARTU**
- [ ] Tabel saldo kartu tampil per hari
- [ ] Form input saldo (di halaman rekap input)
- [ ] Edit saldo (jika perlu)
- [ ] Hapus saldo dengan konfirmasi
- [ ] Cap saldo maksimal 5 juta

### 💵 **7. BON PELANGGAN (STANDALONE)**
- [ ] Halaman khusus bon pelanggan (jika ada route terpisah)
- [ ] Detail bon per pelanggan
- [ ] History pelunasan

---

## 🐛 **POTENTIAL BUGS TO CHECK**

### **Perhitungan**
- [ ] Saldo kemarin di tanggal X = Total Cash di tanggal X-1?
- [ ] Opening cash hanya ditambahkan 1x (tidak duplikat)?
- [ ] Fee lipat carry-over tidak hilang saat ganti hari?
- [ ] Pelunasan bon migrasi dihitung di cash flow?
- [ ] AJ QRIS tidak mengurangi cash laundry? (hanya fee yang dikurangi)
- [ ] Pengeluaran "tarik kas" tidak dihitung di dashboard?

### **UI/UX**
- [ ] Mobile: tabel bisa di-scroll horizontal?
- [ ] Tombol konfirmasi delete muncul?
- [ ] Dropdown warna berubah sesuai status?
- [ ] Badge lunas/belum lunas akurat?
- [ ] Pagination berfungsi di semua tabel?
- [ ] Form validation error message tampil?

### **Database**
- [ ] Tidak ada duplikat status "Selesai" saat sembunyikan pesanan?
- [ ] `paid_at` terisi saat bon dilunasi?
- [ ] `is_hidden` tidak mempengaruhi perhitungan rekap?
- [ ] Relasi model berfungsi (pesanan → service, metode, status)?

### **Edge Cases**
- [ ] Apa yang terjadi jika input rekap di hari yang sama 2x?
- [ ] Apa yang terjadi jika hapus service yang sudah dipakai pesanan?
- [ ] Apa yang terjadi jika ubah harga service setelah ada pesanan?
- [ ] Apa yang terjadi jika akses tanggal sebelum cutover opening?
- [ ] Apa yang terjadi jika saldo kartu kemarin tidak ada (null)?

---

## ⚠️ **KNOWN ISSUES (FIXED)**
- [x] ~~Saldo kemarin salah karena `subSecond()` vs `endOfDay()` microseconds~~ → **FIXED**
- [x] ~~Opening cash tidak dihitung karena Carbon mutation~~ → **FIXED with copy()**
- [x] ~~Status duplikat saat sembunyikan pesanan yang sudah "Selesai"~~ → **FIXED**
- [x] ~~Customer suggestion tampil yang HP-nya '-' atau kosong~~ → **FIXED**
- [x] ~~Urutan kolom bon tidak sesuai permintaan~~ → **FIXED**

---

## 🚀 **RECOMMENDED NEXT STEPS**

1. **Testing Manual** (prioritas tinggi):
   - Coba input pesanan → lihat muncul di dashboard & rekap
   - Coba ubah metode bon → cek total cash & piutang berubah
   - Coba sembunyikan pesanan → cek status jadi "Selesai"
   - Coba input rekap di tanggal berbeda → cek saldo kemarin konsisten
   - Coba download JPG → pastikan format rapi

2. **Testing Edge Cases**:
   - Input saldo kartu > 5 juta → harus ditolak
   - Akses rekap tanggal lalu → mode baca saja (tidak bisa edit/hapus)
   - Lock opening/migrasi → form tidak muncul lagi

3. **Performance Check**:
   - Query rekap untuk bulan penuh → cek waktu loading
   - Pagination di tabel dengan banyak data
   - Chart omzet untuk 30 hari → pastikan smooth

4. **Data Integrity**:
   - Export rekap ke JPG beberapa hari berturut-turut
   - Cek apakah total cash hari ini = saldo kemarin hari besok
   - Validasi manual: hitung fee lipat & bandingkan dengan sistem

5. **User Acceptance**:
   - Minta user mencoba semua fitur
   - Catat feedback & request tambahan
   - Prioritaskan bug fix vs nice-to-have features

---

## 📝 **NOTES**
- Clean code refactoring sudah dilakukan di `RekapController`
- Service layer pattern sudah diterapkan (FeeCalculatorService, CashFlowCalculatorService, SaldoKartuCalculatorService)
- Mobile responsive sudah dioptimalkan untuk tabel pesanan & bon
- Export JPG menggunakan html-to-image library
- Carbon date handling sudah diperbaiki (gunakan `copy()` + `endOfDay()`)

---

**Status Terakhir Update**: 4 November 2025
**Developer**: GitHub Copilot + User
**Tech Stack**: Laravel 11, Tailwind CSS, Alpine.js, Chart.js
