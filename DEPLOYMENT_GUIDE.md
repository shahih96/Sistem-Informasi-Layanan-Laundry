# 🚀 PANDUAN DEPLOYMENT - QXPRESS LAUNDRY SYSTEM
## Step-by-Step Upload ke Hosting (Niagahoster)

---

## 📋 **PERSIAPAN (Di Laptop/Lokal)**

### **1. Backup Database** ✅
**Waktu:** 2 menit

```bash
# Windows (Double-click)
backup-database.bat

# Atau manual via phpMyAdmin:
# 1. Buka http://localhost/phpmyadmin
# 2. Pilih database "tugasakhir"
# 3. Tab "Export" → Quick → Go
# 4. Save file: backup_laundry_2025-11-04.sql
```

**Hasil:** File `.sql` di folder `backup/`

---

### **2. Optimize Laravel** ✅
**Waktu:** 2 menit

```bash
# Buka PowerShell/CMD di folder project
cd D:\laragon\www\Sistem-Informasi-Layanan-Laundry

# Clear cache dulu
php artisan optimize:clear

# Install dependencies production (no dev)
composer install --no-dev --optimize-autoloader

# Build assets
npm run build
```

**Hasil:** 
- Folder `vendor/` optimized
- Folder `public/build/` berisi CSS/JS compiled

---

### **3. Check File .env** ✅
**Waktu:** 1 menit

Pastikan `.env` TIDAK akan ter-upload ke server (nanti buat baru di server).

```bash
# Check .gitignore
type .gitignore | findstr .env

# Harusnya muncul:
# .env
# .env.backup
# .env.production
```

✅ Kalau ada berarti aman!

---

### **4. Compress Project** ✅
**Waktu:** 5 menit

**Zip seluruh project KECUALI:**
- `node_modules/` (besar, install ulang di server)
- `.env` (buat baru di server)
- `backup/` (database backup tidak perlu)
- `.git/` (tidak perlu)

**Cara Compress:**
1. Buka folder project
2. Select semua file/folder KECUALI di atas
3. Klik kanan → Send to → Compressed (zipped) folder
4. Nama: `qxpress-laundry.zip`

**Hasil:** File `qxpress-laundry.zip` (~50-100 MB)

---

## 🌐 **BELI HOSTING & DOMAIN**

### **1. Beli Hosting Niagahoster**

**Link:** https://www.niagahoster.co.id/cloud-hosting

**Pilih Paket:** Bayi Cloud
- **Harga:** Rp 720.000/tahun (diskon sering ada!)
- **Gratis:** Domain .com + SSL

**Proses:**
1. Klik "Pesan Sekarang"
2. Pilih durasi: **1 Tahun** (dapat domain gratis!)
3. Pilih domain baru (contoh: `qxpresslaundry.com`)
4. Checkout → Bayar

**Waktu:** Aktivasi 5-10 menit setelah pembayaran

---

### **2. Akses cPanel**

Setelah hosting aktif, kamu dapat email berisi:
- **cPanel URL:** `https://cpanel.qxpresslaundry.com:2083`
- **Username:** (biasanya nama domain)
- **Password:** (cek di email)

**Login ke cPanel**

---

## 📤 **UPLOAD PROJECT KE SERVER**

### **1. Upload via File Manager cPanel** ✅
**Waktu:** 10 menit

**Langkah:**
1. Login cPanel
2. Klik **File Manager**
3. Navigate ke folder `public_html/`
4. **HAPUS semua file default** (index.html, cgi-bin, dll)
5. Klik **Upload**
6. Pilih file `qxpress-laundry.zip`
7. Tunggu upload selesai (5-10 menit tergantung koneksi)
8. Klik kanan file zip → **Extract**
9. Pilih extract ke `/public_html/`
10. Setelah extract selesai, **Hapus file zip**

**Struktur folder sekarang:**
```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
├── artisan
├── composer.json
└── ...
```

---

### **2. Pindahkan Isi Folder `public/` ke Root** ✅
**PENTING!** Laravel harus serve dari folder `public/`, tapi di shared hosting, root adalah `public_html/`

**Langkah:**
1. Di File Manager, buka folder `public_html/public/`
2. **Select All** → Klik **Move**
3. Move ke: `/public_html/`
4. Confirm → **Move Files**
5. **Hapus folder `public/` yang sekarang kosong**

**Struktur sekarang:**
```
public_html/
├── app/           ← Laravel folders
├── bootstrap/
├── config/
├── ...
├── index.php      ← Laravel entry point (dari folder public)
├── favicon.ico
└── images/
```

---

### **3. Edit `index.php`** ✅
**Waktu:** 2 menit

File `index.php` sekarang di root, tapi masih merujuk ke folder yang salah.

**Langkah:**
1. Klik kanan `index.php` → **Edit**
2. Cari baris:
   ```php
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   ```
3. Ganti jadi:
   ```php
   require __DIR__.'/vendor/autoload.php';
   $app = require_once __DIR__.'/bootstrap/app.php';
   ```
4. **Save Changes**

---

### **4. Set Permission Folder `storage/` dan `bootstrap/cache/`** ✅
**Waktu:** 2 menit

Laravel perlu write access ke folder ini.

**Langkah:**
1. Klik kanan folder `storage/` → **Change Permissions**
2. Set ke: `755` (rwxr-xr-x)
3. ✅ Check **Recurse into subdirectories**
4. Click **Change Permissions**

Ulangi untuk folder `bootstrap/cache/`

---

## 🗄️ **SETUP DATABASE**

### **1. Buat Database MySQL** ✅
**Waktu:** 3 menit

**Langkah:**
1. Di cPanel, cari **MySQL® Databases**
2. **Create New Database**
   - Database Name: `laundry_db`
   - Click **Create Database**
3. **Add New User**
   - Username: `laundry_user`
   - Password: **Generate Strong Password** (SIMPAN password ini!)
   - Click **Create User**
4. **Add User To Database**
   - User: `laundry_user`
   - Database: `laundry_db`
   - Click **Add**
   - Privileges: **ALL PRIVILEGES**
   - Click **Make Changes**

**Catat:**
```
Database Name: laundry_db
Username: laundry_user
Password: (password yang di-generate)
Host: localhost
```

---

### **2. Import Database** ✅
**Waktu:** 5 menit

**Langkah:**
1. Di cPanel, cari **phpMyAdmin**
2. Login (otomatis)
3. Pilih database `laundry_db` di sidebar kiri
4. Tab **Import**
5. Click **Choose File**
6. Pilih file `backup_laundry_2025-11-04.sql` (dari laptop)
7. Scroll ke bawah → Click **Import**
8. Tunggu... (1-3 menit)
9. ✅ Success message!

**Verify:**
- Check tabel sudah ada: `pesanan_laundry`, `rekap`, `services`, dll

---

## ⚙️ **KONFIGURASI .ENV**

### **1. Buat File `.env` di Server** ✅
**Waktu:** 5 menit

**Langkah:**
1. Di File Manager, navigate ke `/public_html/`
2. Click **+ File**
3. Nama file: `.env`
4. Click **Create New File**
5. Klik kanan `.env` → **Edit**

---

### **2. Copy dari `.env.example`** ✅

**Langkah:**
1. Buka file `.env.example` di laptop (dengan Notepad)
2. Copy semua isinya
3. Paste ke file `.env` di server (cPanel editor)
4. **Edit nilai berikut:**

```env
APP_NAME=QxpressLaundry
APP_ENV=production                    # ← PENTING!
APP_KEY=                              # ← Nanti generate
APP_DEBUG=false                       # ← PENTING! False!
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://qxpresslaundry.com    # ← Domain kamu

LOG_LEVEL=error                       # ← Production level

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=laundry_db                # ← Dari step database
DB_USERNAME=laundry_user              # ← Dari step database  
DB_PASSWORD=password_yang_digenerate  # ← Password database!

CACHE_STORE=file                      # ← Pakai file cache
QUEUE_CONNECTION=database

# Google reCAPTCHA (tracking page)
NOCAPTCHA_SITEKEY=your_site_key       # ← Dari Google reCAPTCHA
NOCAPTCHA_SECRET=your_secret_key      # ← Dari Google reCAPTCHA

# Email (optional, untuk notifikasi error)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@qxpresslaundry.com
MAIL_FROM_NAME="Qxpress Laundry"
```

5. **Save Changes**

---

### **3. Generate APP_KEY** ✅
**Waktu:** 2 menit

APP_KEY kosong, perlu di-generate!

**Cara 1: Via SSH (Recommended)**
```bash
# Login via SSH (Terminal & SSH di cPanel)
cd public_html
php artisan key:generate
```

**Cara 2: Manual**
1. Buka: https://generate-random.org/laravel-key-generator
2. Click **Generate Laravel Key**
3. Copy hasil (contoh: `base64:abc123...`)
4. Paste ke `.env`:
   ```env
   APP_KEY=base64:abc123def456...
   ```
5. Save

---

## 🔐 **SETUP SSL (HTTPS)**

### **1. Install SSL Certificate** ✅
**Waktu:** 5 menit

**Langkah:**
1. Di cPanel, cari **SSL/TLS Status**
2. Centang domain kamu: `qxpresslaundry.com` dan `www.qxpresslaundry.com`
3. Click **Run AutoSSL**
4. Tunggu... (2-5 menit)
5. ✅ Status: **SSL Installed**

**Test:**
- Buka: `https://qxpresslaundry.com` (harus ada gembok hijau)

---

### **2. Force HTTPS (Redirect HTTP)** ✅

**Langkah:**
1. Di File Manager, edit file `.htaccess` di root (`/public_html/.htaccess`)
2. Tambahkan di **PALING ATAS** (sebelum kode Laravel):

```apache
# Force HTTPS
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# Laravel default routes (jangan dihapus!)
```

3. Save

**Test:**
- Buka: `http://qxpresslaundry.com` → harus auto redirect ke `https://`

---

## 🎯 **FINALISASI**

### **1. Clear Cache & Optimize** ✅
**Waktu:** 2 menit

**Via SSH:**
```bash
cd public_html

# Clear semua cache
php artisan optimize:clear

# Cache production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Tanpa SSH (Skip aja, atau pakai Artisan GUI jika ada)**

---

### **2. Set Storage Link** ✅
**Waktu:** 1 menit

Untuk akses gambar di `storage/app/public/`

**Via SSH:**
```bash
php artisan storage:link
```

**Tanpa SSH:**
1. Buat symlink manual via File Manager:
2. Navigate ke `/public_html/storage/app/`
3. Folder `public/` → klik kanan → **Get Link**
4. Di `/public_html/` buat symlink nama `storage` → target `/public_html/storage/app/public`

---

### **3. Testing Aplikasi** ✅
**Waktu:** 10 menit

**Test Checklist:**

**A. Public Pages:**
- [ ] `https://qxpresslaundry.com` → Landing page muncul?
- [ ] `https://qxpresslaundry.com/tracking` → Tracking form muncul?
- [ ] `https://qxpresslaundry.com/services` → Daftar harga muncul?
- [ ] Gambar hero slider muncul?
- [ ] Gambar layanan muncul?

**B. Admin Panel:**
- [ ] `https://qxpresslaundry.com/login` → Login page muncul?
- [ ] Login dengan kredensial admin → Berhasil?
- [ ] Dashboard muncul?
- [ ] Data pesanan muncul?
- [ ] Data rekap muncul?

**C. Tracking:**
- [ ] Input No. HP → Data muncul?
- [ ] reCAPTCHA berfungsi?

**D. Performance:**
- [ ] Page load < 2 detik?
- [ ] No error 500?

---

## 🔧 **TROUBLESHOOTING**

### **Error: 500 Internal Server Error**

**Penyebab & Solusi:**

1. **Permission salah**
   ```bash
   # Set permission via SSH
   chmod -R 755 storage/
   chmod -R 755 bootstrap/cache/
   ```

2. **APP_KEY kosong**
   - Check `.env` → `APP_KEY=` harus ada value!
   - Generate ulang: `php artisan key:generate`

3. **Database connection failed**
   - Check `.env`:
     - `DB_DATABASE` nama benar?
     - `DB_USERNAME` benar?
     - `DB_PASSWORD` benar?
   - Test connection via phpMyAdmin

4. **Missing vendor/**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

---

### **Error: Images Not Found (404)**

**Penyebab:** Path gambar salah setelah pindah folder `public/`

**Solusi:**
1. Check file `.htaccess` sudah benar
2. Check folder `images/` ada di `/public_html/images/`
3. Check `storage/` symlink sudah dibuat

---

### **Error: CSS/JS Not Loaded**

**Penyebab:** Asset tidak di-build atau path salah

**Solusi:**
```bash
# Di laptop, build ulang
npm run build

# Upload folder public/build/ ke server
```

---

### **Error: Too Many Redirects**

**Penyebab:** Force HTTPS loop

**Solusi:**
Edit `.htaccess`, pastikan:
```apache
RewriteCond %{HTTPS} off
```
Bukan:
```apache
RewriteCond %{HTTPS} !=on  # ← Salah!
```

---

## 📊 **MONITORING & MAINTENANCE**

### **1. Setup Auto Backup Database**

**Via cPanel Cron Job:**

1. cPanel → **Cron Jobs**
2. Add New Cron Job:
   - Minute: `0`
   - Hour: `2` (jam 2 pagi)
   - Day: `*`
   - Month: `*`
   - Weekday: `*`
   - Command:
     ```bash
     mysqldump -u laundry_user -p'password' laundry_db > /home/username/backups/backup_$(date +\%F).sql
     ```
3. Save

**Backup akan jalan otomatis setiap hari jam 2 pagi!**

---

### **2. Monitor Error Logs**

**Lokasi log:**
```
/public_html/storage/logs/laravel.log
```

**Cara check:**
1. File Manager → Navigate ke `storage/logs/`
2. Klik kanan `laravel.log` → **View**
3. Cari error level: `ERROR`, `CRITICAL`, `EMERGENCY`

**Setup Email Notification (Optional):**
- Install Laravel Telescope (dev only)
- Atau gunakan Sentry.io (free tier)

---

### **3. Update System**

**Saat ada update code:**

```bash
# 1. Backup database dulu!
# 2. Upload file baru ke server
# 3. Clear cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run migration (jika ada)
php artisan migrate --force

# 5. Test!
```

---

## ✅ **POST-DEPLOYMENT CHECKLIST**

- [ ] Hosting aktif & domain sudah propagasi (bisa diakses)
- [ ] SSL certificate installed (HTTPS hijau)
- [ ] Database imported & connection works
- [ ] `.env` configured dengan benar:
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_ENV=production`
  - [ ] `APP_KEY` generated
  - [ ] `APP_URL` benar
  - [ ] Database credentials benar
- [ ] File permissions: `storage/` dan `bootstrap/cache/` = 755
- [ ] `storage/` symlink created
- [ ] Landing page accessible
- [ ] Tracking page working
- [ ] Daftar harga page working
- [ ] Admin login working
- [ ] Dashboard data muncul
- [ ] reCAPTCHA working di tracking
- [ ] Images loading correctly
- [ ] No 500 errors
- [ ] Auto backup database setup (cron job)
- [ ] Email notification setup (optional)

---

## 📞 **SUPPORT**

### **Niagahoster Support:**
- **Live Chat:** https://www.niagahoster.co.id
- **WhatsApp:** 0804-1-808-888
- **Email:** sales@niagahoster.co.id
- **Ticket:** Member Area → Support

### **Dokumentasi Laravel:**
- https://laravel.com/docs/11.x/deployment

---

## 🎉 **SELAMAT!**

Sistem laundry kamu sekarang **LIVE** dan bisa diakses dari mana saja! 🚀

**Share link:**
- Landing: `https://qxpresslaundry.com`
- Tracking: `https://qxpresslaundry.com/tracking`
- Admin: `https://qxpresslaundry.com/login`

**Next Steps:**
- Monitor traffic di Google Analytics
- Setup WhatsApp Business untuk customer support
- Share link ke pelanggan via social media
- Print QR code tracking untuk ditempel di outlet

---

**Total waktu deployment:** ~1-2 jam (pertama kali)  
**Update berikutnya:** ~10-15 menit

🎯 **System Production Ready!**
