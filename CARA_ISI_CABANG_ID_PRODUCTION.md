# 🎯 QUICK START - Cara Nambahin cabang_id ke Data Existing di Production

## ⚠️ PENTING! Baca Ini Dulu!

Jangan khawatir, **migration sudah otomatis mengisi `cabang_id = 1` untuk semua data existing**!

---

## 🚀 Step-by-Step di Production:

### **1. BACKUP DATABASE** ⚠️⚠️⚠️
```bash
# Cara 1: Via Command Line
mysqldump -u root -p nama_database > backup_sebelum_cabang_$(date +%Y%m%d).sql

# Cara 2: Via phpMyAdmin
# - Login phpMyAdmin
# - Pilih database
# - Klik tab "Export"
# - Klik "Go"
```

### **2. Upload Code ke Production**
```bash
# Jika pakai Git:
git pull origin main

# Jika manual:
# - Upload semua file via FTP/FileZilla
# - Pastikan folder database/migrations terupdate
```

### **3. Run Migration** (INI YANG PENTING!)
```bash
cd /path/to/your/laravel/project
php artisan migrate
```

**Apa yang terjadi saat migrate?**
```
✓ Buat tabel 'cabang'
✓ Insert 2 data: Airan (ID=1), Kopi (ID=2)
✓ Tambah kolom 'cabang_id' ke semua tabel
✓ OTOMATIS ISI cabang_id = 1 untuk SEMUA data existing ← INI YANG LO TANYAIN!
✓ Tambah foreign key
```

**Jadi, data existing OTOMATIS kena isi `cabang_id = 1`!**

### **4. Verify Data (Opsional - Kalau Mau Mastiin)**
```sql
-- Cek jumlah data per cabang
SELECT cabang_id, COUNT(*) as total 
FROM pesanan_laundries 
GROUP BY cabang_id;

-- Harusnya semua data existing punya cabang_id = 1

-- Cek user
SELECT id, name, email, cabang_id FROM users;

-- Harusnya semua user existing punya cabang_id = 1
```

### **5. Buat Admin untuk Cabang Kopi**
```bash
# Cara 1: Via Seeder (Recommended)
php artisan db:seed --class=AdminKopiSeeder

# Cara 2: Via Tinker (Manual)
php artisan tinker
```

Jika pakai tinker, ketik:
```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Admin Kopi',
    'email' => 'kopi@qxpress.com',
    'password' => Hash::make('Password_Aman_123!'),
    'is_admin' => true,
    'cabang_id' => 2,
    'email_verified_at' => now(),
]);

// Tekan Ctrl+D untuk keluar
```

### **6. Clear Cache**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### **7. Test Login**
1. **Login dengan admin Airan (existing account)**
   - Seharusnya masih bisa login
   - Lihat dashboard → data Airan masih muncul
   
2. **Login dengan admin Kopi (account baru)**
   - Email: kopi@qxpress.com
   - Password: Password_Aman_123! (atau yang kamu set)
   - Dashboard kosong (normal, karena belum ada transaksi)

3. **Test Create Pesanan di Cabang Kopi**
   - Buat pesanan baru
   - Cek database: `SELECT * FROM pesanan_laundries ORDER BY id DESC LIMIT 1;`
   - Harusnya `cabang_id = 2`

---

## 🛡️ Kalau Ada Masalah:

### **Problem 1: Data existing tidak muncul setelah migrate**
**Cek cabang_id:**
```sql
SELECT cabang_id, COUNT(*) FROM pesanan_laundries GROUP BY cabang_id;
```

**Jika ada data dengan cabang_id = NULL:**
```sql
UPDATE pesanan_laundries SET cabang_id = 1 WHERE cabang_id IS NULL;
UPDATE bons SET cabang_id = 1 WHERE cabang_id IS NULL;
UPDATE rekaps SET cabang_id = 1 WHERE cabang_id IS NULL;
UPDATE saldo_bon SET cabang_id = 1 WHERE cabang_id IS NULL;
UPDATE saldo_kartu SET cabang_id = 1 WHERE cabang_id IS NULL;
UPDATE saldo_kas SET cabang_id = 1 WHERE cabang_id IS NULL;
UPDATE fees SET cabang_id = 1 WHERE cabang_id IS NULL;
```

### **Problem 2: Admin tidak bisa login**
**Cek cabang_id user:**
```sql
SELECT id, name, email, cabang_id FROM users WHERE is_admin = 1;
```

**Jika NULL, update:**
```sql
UPDATE users SET cabang_id = 1 WHERE cabang_id IS NULL;
```

### **Problem 3: Error "cabang_id doesn't exist"**
**Clear cache dulu:**
```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

**Kalau masih error, cek apakah migration sudah jalan:**
```bash
php artisan migrate:status
```

### **Problem 4: Admin bisa lihat data semua cabang**
**Kemungkinan cache. Clear cache:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

**Atau, cek apakah user punya cabang_id:**
```sql
SELECT id, name, email, cabang_id FROM users WHERE id = [ID_USER_YG_LOGIN];
```

---

## 📝 Checklist Production:

```
☐ Backup database
☐ Upload/pull code terbaru
☐ Run: php artisan migrate
☐ Verify: Data existing punya cabang_id = 1
☐ Run: php artisan db:seed --class=AdminKopiSeeder
☐ Clear cache
☐ Test login admin Airan (existing)
☐ Test login admin Kopi (baru)
☐ Test create pesanan di kedua cabang
☐ Test lihat laporan
☐ Monitoring 1-2 hari pertama
```

---

## 💡 Tips:

1. **Jangan panik!** Migration sudah dirancang aman.
2. **Data existing AMAN**, semuanya auto-fill `cabang_id = 1`.
3. **Kalau ragu, test di local/staging dulu** dengan database copy production.
4. **Simpan backup database** di tempat yang aman.
5. **Screenshot setiap step** kalau ada error untuk troubleshoot.

---

## 🎉 Setelah Sukses Deploy:

- ✅ Cabang Airan tetap jalan normal
- ✅ Cabang Kopi siap digunakan
- ✅ Data terpisah otomatis
- ✅ Tidak perlu ubah cara kerja existing

---

**Pertanyaan? Lihat file `PANDUAN_MULTI_CABANG.md` untuk dokumentasi lengkap!**

**Good luck! 🚀**
