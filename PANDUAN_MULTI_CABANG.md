# 🏢 Panduan Implementasi Multi-Cabang

## 📋 Overview
Sistem laundry sekarang mendukung multi-cabang (Airan & Kopi). Setiap admin hanya bisa melihat dan mengelola data cabangnya sendiri.

---

## ⚠️ PENTING UNTUK PRODUCTION

### 🔥 **BACKUP DATABASE DULU!**
```bash
# Di production, backup database sebelum migrate
php artisan backup:database
# atau manual export via phpMyAdmin
```

---

## 🚀 Langkah Deployment di Production

### **Step 1: Backup Database** ✅
```bash
# Export database dulu untuk jaga-jaga
# Gunakan phpMyAdmin atau command:
mysqldump -u username -p database_name > backup_sebelum_multi_cabang.sql
```

### **Step 2: Pull Code Terbaru** ✅
```bash
git pull origin main
# atau upload file manual jika tidak pakai git
```

### **Step 3: Run Migration** ✅
```bash
php artisan migrate
```

**Apa yang terjadi saat migrate?**
1. ✅ Buat tabel `cabang` baru
2. ✅ Insert 2 data cabang:
   - ID 1: Airan (default)
   - ID 2: Kopi
3. ✅ Tambah kolom `cabang_id` ke semua tabel:
   - admins
   - pesanan_laundries
   - bons
   - rekaps
   - saldo_bon
   - saldo_kartu
   - saldo_kas
   - fees
   - status_pesanan
4. ✅ **OTOMATIS mengisi `cabang_id = 1` untuk SEMUA data existing** (ini yang penting!)
5. ✅ Tambah foreign key constraint

### **Step 4: Update User/Admin Existing** ✅
```sql
-- Set semua user existing ke cabang Airan (ID=1)
UPDATE users SET cabang_id = 1 WHERE cabang_id IS NULL;
UPDATE admins SET cabang_id = 1 WHERE cabang_id IS NULL;
```

### **Step 5: Buat Akun Admin untuk Cabang Kopi** ✅

**Via Laravel Tinker:**
```bash
php artisan tinker
```

```php
// Di tinker:
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Admin Kopi',
    'email' => 'kopi@qxpress.com',
    'password' => Hash::make('password_aman_123'),
    'is_admin' => true,
    'cabang_id' => 2, // ID Cabang Kopi
]);

// Tekan Ctrl+D untuk keluar
```

**Atau Via Seeder:**
```bash
php artisan db:seed --class=AdminKopiSeeder
```

### **Step 6: Test Login** ✅
1. Login dengan akun admin Airan (existing)
   - Seharusnya hanya melihat data Airan
2. Login dengan akun admin Kopi (baru)
   - Seharusnya data kosong atau hanya data Kopi

### **Step 7: Clear Cache** ✅
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## 🧪 Testing di Local (Development)

### **Step 1: Backup Database Local**
```bash
php artisan backup:database
```

### **Step 2: Run Migration**
```bash
php artisan migrate
```

### **Step 3: Buat User Test untuk Cabang Kopi**
```bash
php artisan tinker
```
```php
use App\Models\User;
User::create([
    'name' => 'Test Kopi',
    'email' => 'test-kopi@test.com',
    'password' => bcrypt('password'),
    'is_admin' => true,
    'cabang_id' => 2,
]);
```

### **Step 4: Test Fitur**
1. ✅ Login sebagai admin Airan → lihat data Airan saja
2. ✅ Login sebagai admin Kopi → data kosong (karena belum ada transaksi)
3. ✅ Buat pesanan baru di Kopi → pesanan otomatis masuk ke cabang_id=2
4. ✅ Cek database: kolom `cabang_id` terisi otomatis

---

## 📊 Perubahan Database

### Tabel Baru
- **cabang** (id, kode, nama, alamat, no_telepon, timestamps)

### Kolom Baru di Tabel Existing
Semua tabel berikut ditambah kolom `cabang_id`:
- ✅ users / admins
- ✅ pesanan_laundries
- ✅ bons
- ✅ rekaps
- ✅ saldo_bon
- ✅ saldo_kartu
- ✅ saldo_kas
- ✅ fees
- ✅ status_pesanan (jika ada)

**Default Value:** `1` (Cabang Airan) ← Data lama aman!

---

## 🔒 Cara Kerja Sistem Multi-Cabang

### **Auto-Filter by Cabang (Global Scope)**
Setiap query otomatis di-filter berdasarkan `cabang_id` user yang login:

```php
// Contoh: Admin Airan login (cabang_id = 1)
PesananLaundry::all(); 
// SQL: SELECT * FROM pesanan_laundries WHERE cabang_id = 1

// Contoh: Admin Kopi login (cabang_id = 2)
PesananLaundry::all();
// SQL: SELECT * FROM pesanan_laundries WHERE cabang_id = 2
```

### **Auto-Set Cabang saat Create**
Saat admin membuat data baru, `cabang_id` otomatis terisi:

```php
// Admin Airan membuat pesanan baru
PesananLaundry::create([...]); 
// Otomatis: cabang_id = 1

// Admin Kopi membuat pesanan baru
PesananLaundry::create([...]); 
// Otomatis: cabang_id = 2
```

**Tidak perlu ubah controller!** Semua sudah otomatis.

---

## 🛠️ Troubleshooting

### **Problem: Error saat migrate**
```
SQLSTATE[23000]: Integrity constraint violation
```
**Solusi:**
- Migration berjalan bertahap, pastikan tabel `cabang` dibuat dulu
- Jika sudah terlanjur error, rollback:
  ```bash
  php artisan migrate:rollback
  php artisan migrate
  ```

### **Problem: Data lama tidak kelihat**
**Cek:**
```sql
SELECT cabang_id, COUNT(*) FROM pesanan_laundries GROUP BY cabang_id;
```
**Jika ada yang NULL:**
```sql
UPDATE pesanan_laundries SET cabang_id = 1 WHERE cabang_id IS NULL;
```

### **Problem: Admin bisa lihat semua cabang**
**Cek kolom cabang_id di user:**
```sql
SELECT id, name, email, cabang_id FROM users WHERE is_admin = 1;
```
**Update jika NULL:**
```sql
UPDATE users SET cabang_id = 1 WHERE cabang_id IS NULL;
```

### **Problem: Error "cabang_id not found"**
**Clear cache:**
```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

---

## 📝 Catatan Penting

### ✅ **Yang AMAN:**
- Data lama tetap utuh (otomatis masuk cabang Airan)
- Admin lama tetap bisa login
- Fitur existing tetap jalan normal

### ⚠️ **Yang PERLU DIPERHATIKAN:**
- Setelah migrate, **WAJIB set `cabang_id` untuk semua user**
- Master data (Services, Metode Pembayaran) masih SHARED (semua cabang sama)
- Jika mau harga layanan beda per cabang, perlu development tambahan

### 🔮 **Future Enhancement:**
- Dashboard untuk developer/owner yang bisa lihat semua cabang
- Setting harga per cabang
- Laporan gabungan semua cabang

---

## 📞 Support

Jika ada masalah saat deployment:
1. Jangan panik
2. Restore database dari backup
3. Hubungi developer (kamu! 😄)
4. Screenshot error message

---

## ✅ Checklist Deployment Production

- [ ] Backup database
- [ ] Pull code terbaru
- [ ] Run `php artisan migrate`
- [ ] Update `cabang_id` di tabel users
- [ ] Buat admin baru untuk cabang Kopi
- [ ] Test login kedua cabang
- [ ] Clear cache
- [ ] Test create pesanan baru
- [ ] Verify data existing masih muncul
- [ ] Test laporan/rekap
- [ ] Monitoring 24 jam pertama

---

**Good luck! 🚀**
