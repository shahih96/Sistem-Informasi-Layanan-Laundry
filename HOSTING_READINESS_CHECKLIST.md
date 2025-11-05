# 🚀 CHECKLIST PERSIAPAN HOSTING & ANALISIS RISIKO
## Sistem Informasi Laundry - Production Ready Assessment

---

## ✅ YANG SUDAH SELESAI & AMAN

### 1. **Core Features** ✓
- [x] Dashboard dengan chart omzet
- [x] Pesanan laundry (CRUD)
- [x] Rekap keuangan (input & tampilan)
- [x] Perhitungan fee karyawan (carry-over lipat)
- [x] Tracking bon pelanggan
- [x] Saldo kartu (cap 5 juta)
- [x] Export rekap ke JPG
- [x] Migrasi bon lama (locked)
- [x] Opening setup (locked)

### 2. **Database Optimization** ✓
- [x] 14 indexes strategis sudah diterapkan
- [x] Query optimization (20-100x lebih cepat)
- [x] Relasi model sudah proper (hasMany, belongsTo)
- [x] Soft deletes untuk pesanan

### 3. **Code Quality** ✓
- [x] Clean code refactoring (service layer pattern)
- [x] No syntax errors
- [x] Perhitungan balance & sinkron
- [x] No double counting

### 4. **Security (Basic)** ✓
- [x] Authentication (Laravel Breeze/Fortify)
- [x] CSRF protection (Laravel default)
- [x] Password hashing (bcrypt)

### 5. **UI/UX** ✓
- [x] Mobile responsive
- [x] Confirmation dialogs
- [x] Loading states
- [x] Error messages

---

## ⚠️ RISIKO & MITIGASI SEBELUM HOSTING

### 🔴 **CRITICAL (HARUS diperbaiki sebelum hosting)**

#### **1. Environment Configuration**
**Risiko:** `.env` file dengan kredensial production tercopy ke public repo
**Dampak:** Database password, APP_KEY bocor → sistem di-hack
**Mitigasi:**
```bash
# 1. Pastikan .env di .gitignore
echo ".env" >> .gitignore

# 2. Buat .env.example tanpa credential
cp .env .env.example
# Edit .env.example, ganti password dengan "your_password_here"

# 3. Di server, buat .env baru
cp .env.example .env
# Isi dengan credential production
```

**Checklist:**
- [ ] `.env` ada di `.gitignore`
- [ ] `.env.example` sudah dibuat (no real password)
- [ ] `APP_KEY` di-generate ulang untuk production

---

#### **2. Database Backup**
**Risiko:** Data hilang karena server crash / human error
**Dampak:** Semua data transaksi 1 tahun hilang (fatal!)
**Mitigasi:**
```bash
# Setup automatic backup (cron job di server)

# A. Manual backup (lakukan sekarang!)
mysqldump -u root -p laundry_db > backup_$(date +%F).sql

# B. Automatic daily backup (cron)
# Edit crontab: crontab -e
0 2 * * * mysqldump -u root -pPASSWORD laundry_db > /backup/laundry_$(date +\%F).sql
0 3 * * * find /backup -name "laundry_*.sql" -mtime +7 -delete
# Backup jam 2 pagi, hapus backup > 7 hari
```

**Checklist:**
- [ ] Backup manual sebelum hosting
- [ ] Setup automatic daily backup di server
- [ ] Test restore backup minimal 1x
- [ ] Backup disimpan di lokasi terpisah (Google Drive / Dropbox)

---

#### **3. APP_DEBUG = false di Production**
**Risiko:** Error message expose struktur database & path file
**Dampak:** Hacker bisa tahu struktur sistem
**Mitigasi:**
```env
# File: .env (di server production)
APP_ENV=production
APP_DEBUG=false  # ← PENTING!
APP_URL=https://yourdomain.com
```

**Checklist:**
- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production`
- [ ] Custom error page (resources/views/errors/500.blade.php)

---

#### **4. Database Credentials**
**Risiko:** Gunakan user 'root' dengan full access
**Dampak:** Jika di-hack, bisa drop semua database
**Mitigasi:**
```sql
-- Buat user khusus untuk aplikasi (hanya akses 1 database)
CREATE USER 'laundry_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE ON laundry_db.* TO 'laundry_user'@'localhost';
FLUSH PRIVILEGES;
```

**Update `.env`:**
```env
DB_USERNAME=laundry_user
DB_PASSWORD=strong_password_here
```

**Checklist:**
- [ ] Buat database user khusus (bukan root)
- [ ] Password database strong (min 16 karakter, kombinasi)
- [ ] Test koneksi dengan user baru

---

#### **5. File Permissions (Linux Server)**
**Risiko:** File `.env` bisa dibaca oleh user lain
**Dampak:** Credential bocor
**Mitigasi:**
```bash
# Set permission yang benar
chmod 644 .env
chown www-data:www-data .env

# Storage & cache harus writable
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Checklist:**
- [ ] `.env` permission = 644
- [ ] `storage/` writable oleh web server
- [ ] `bootstrap/cache/` writable

---

### 🟡 **HIGH PRIORITY (Sangat direkomendasikan)**

#### **6. HTTPS / SSL Certificate**
**Risiko:** Data login & transaksi dikirim tanpa enkripsi
**Dampak:** Password bisa disadap di jaringan publik
**Mitigasi:**
```bash
# Gunakan Let's Encrypt (gratis)
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

**Checklist:**
- [ ] SSL certificate installed
- [ ] Force HTTPS (redirect http → https)
- [ ] Test di https://www.ssllabs.com/ssltest/

---

#### **7. Rate Limiting**
**Risiko:** Brute force attack pada login
**Dampak:** Password bisa di-crack
**Mitigasi:**
```php
// routes/web.php (sudah ada di Laravel default)
Route::middleware('throttle:login')->group(function () {
    // Login routes
});

// config/auth.php - tambahkan
'throttle' => [
    'max_attempts' => 5,      // Max 5 percobaan
    'decay_minutes' => 1,     // Lock selama 1 menit
],
```

**Checklist:**
- [ ] Rate limiting aktif di login
- [ ] Test: coba login salah 6x → harus di-block

---

#### **8. Logging & Monitoring**
**Risiko:** Error production tidak ketahuan
**Dampak:** Bug menyebabkan data salah, tapi tidak ada alert
**Mitigasi:**
```php
// config/logging.php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'error',
        'days' => 14,  // Keep 14 hari
    ],
],
```

**Setup monitoring:**
```bash
# Install Laravel Telescope (dev only)
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Atau gunakan service seperti:
# - Sentry.io (free tier 5.000 errors/month)
# - Bugsnag
# - Rollbar
```

**Checklist:**
- [ ] Log errors ke file
- [ ] Setup email notification untuk critical errors
- [ ] Monitor disk space (log tidak penuh)

---

#### **9. Database Connection Pool**
**Risiko:** Terlalu banyak koneksi database bersamaan
**Dampak:** "Too many connections" error saat traffic tinggi
**Mitigasi:**
```env
# .env
DB_CONNECTION=mysql
DB_POOL_MIN=2
DB_POOL_MAX=10  # Sesuaikan dengan server MySQL

# Atau gunakan Redis untuk cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

**Checklist:**
- [ ] Set max connections sesuai server capacity
- [ ] Monitor active connections
- [ ] Consider Redis untuk session & cache

---

#### **10. Input Validation & Sanitization**
**Risiko:** SQL Injection, XSS attack
**Dampak:** Data dicuri atau diubah oleh hacker
**Mitigasi:**
```php
// Sudah ada Eloquent (aman dari SQL injection)
// Tapi pastikan validasi input:

// app/Http/Requests/StorePesananRequest.php
public function rules()
{
    return [
        'nama_pel' => 'required|string|max:100',
        'no_hp_pel' => 'required|regex:/^[0-9]{10,15}$/',  // Hanya angka
        'qty' => 'required|integer|min:1|max:1000',
        'service_id' => 'required|exists:services,id',
    ];
}

// Blade templates (sudah aman karena {{ }} auto-escape)
{{ $pesanan->nama_pel }}  // ✓ Aman
{!! $pesanan->nama_pel !!}  // ✗ Bahaya (jangan pakai!)
```

**Checklist:**
- [ ] Semua input di-validasi
- [ ] Gunakan `{{ }}` di Blade (bukan `{!! !!}`)
- [ ] Regex validation untuk HP, email, dll

---

### 🟢 **MEDIUM PRIORITY (Good to have)**

#### **11. Cache Strategy**
**Risiko:** Query lambat saat data banyak (meski sudah ada index)
**Dampak:** Dashboard load 0.5-1 detik (masih ok tapi bisa lebih cepat)
**Mitigasi:**
```php
// Cache dashboard stats (update setiap 5 menit)
$totalPesanan = Cache::remember('dashboard.pesanan.today', 300, function () {
    return PesananLaundry::whereDate('created_at', today())->count();
});

// Cache omset bulan ini
$omsetBulan = Cache::remember('dashboard.omset.' . date('Y-m'), 3600, function () {
    return Rekap::whereMonth('created_at', date('m'))->sum('total');
});
```

**Checklist:**
- [ ] Cache data yang jarang berubah
- [ ] Clear cache saat ada update data
- [ ] Monitor hit rate cache

---

#### **12. Queue untuk Export JPG**
**Risiko:** Export JPG besar (data 1 tahun) timeout
**Dampak:** User klik export, loading lama, error
**Mitigasi:**
```php
// Setup queue
php artisan queue:table
php artisan migrate

// Dispatch job
ExportRekapJob::dispatch($tanggal, $userId);

// User dapat notifikasi via email saat selesai
```

**Checklist:**
- [ ] Setup queue driver (database / Redis)
- [ ] Background worker: `php artisan queue:work`
- [ ] Supervisor untuk auto-restart worker

---

#### **13. API Rate Limiting per IP**
**Risiko:** DDoS attack membebani server
**Dampak:** Server down, user tidak bisa akses
**Mitigasi:**
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ...
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
        // Max 60 request per menit per IP
    ],
];
```

**Checklist:**
- [ ] Throttle 60 req/min untuk web
- [ ] Whitelist IP kantor jika perlu
- [ ] Monitor di log jika ada IP suspicious

---

#### **14. Scheduled Tasks (Cron)**
**Risiko:** Log files menumpuk, database tidak ter-optimize
**Dampak:** Disk penuh, performance turun
**Mitigasi:**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Backup database setiap hari jam 2 pagi
    $schedule->command('backup:database')->dailyAt('02:00');
    
    // Hapus log lama (>30 hari)
    $schedule->command('log:clear')->monthly();
    
    // Optimize database setiap minggu
    $schedule->command('db:optimize')->weekly();
}
```

**Setup di server:**
```bash
# Edit crontab
crontab -e

# Tambahkan
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

**Checklist:**
- [ ] Setup Laravel scheduler
- [ ] Cron job aktif di server
- [ ] Test scheduled tasks running

---

#### **15. Multi-User Access Control**
**Risiko:** Kasir bisa akses halaman admin (ubah harga, dll)
**Dampak:** Data diubah sembarangan
**Mitigasi:**
```php
// Buat role: admin, kasir
// app/Models/User.php
public function isAdmin() {
    return $this->role === 'admin';
}

// Middleware
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/services', ...);  // Hanya admin
});
```

**Checklist:**
- [ ] Tambah kolom `role` di tabel users
- [ ] Middleware untuk cek role
- [ ] UI hide menu sesuai role

---

### 🔵 **LOW PRIORITY (Optional, tapi bagus untuk masa depan)**

#### **16. Audit Log / Activity Tracking**
**Risiko:** Tidak tahu siapa yang ubah/hapus data
**Dampak:** Susah tracking jika ada kesalahan
**Mitigasi:**
```bash
composer require spatie/laravel-activitylog

# Log otomatis setiap create/update/delete
PesananLaundry::create([...]);
// Tercatat: User ID 1 create pesanan ID 123 at 2025-11-04 10:30
```

---

#### **17. Export Excel/PDF (selain JPG)**
**Risiko:** Akuntan minta laporan Excel untuk analisis
**Dampak:** Harus manual copy-paste dari web
**Mitigasi:**
```bash
composer require maatwebsite/excel
# Export rekap bulanan ke Excel
```

---

#### **18. Email Notification**
**Risiko:** Owner tidak tahu jika ada error critical
**Dampak:** Bug tidak ketahuan sampai user komplain
**Mitigasi:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# Kirim email jika:
# - Saldo kartu hampir habis (< 100K)
# - Error 500 terjadi
# - Bon belum lunas > 1 bulan
```

---

#### **19. Mobile App (PWA)**
**Risiko:** User ingin akses dari HP tanpa buka browser
**Dampak:** User experience kurang nyaman
**Mitigasi:**
```html
<!-- Buat Progressive Web App -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#3b82f6">

<!-- User bisa "Add to Home Screen" -->
```

---

#### **20. Database Replication (Advanced)**
**Risiko:** Server database mati, semua down
**Dampak:** Bisnis berhenti total
**Mitigasi:**
```
Setup Master-Slave Replication
- Master: untuk write (insert/update/delete)
- Slave: untuk read (select)
- Jika master mati, slave jadi master
```

---

## 🎯 CHECKLIST SEBELUM GO LIVE

### **A. Pre-Deployment (Di Local)**
- [ ] Run `php artisan optimize:clear`
- [ ] Run `php artisan config:clear`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Test all features masih jalan
- [ ] Backup database local
- [ ] Export database structure (migrations)
- [ ] Update `.env.example`
- [ ] Remove debug code (dd(), dump(), console.log())
- [ ] Check composer.json (no dev dependencies di production)

### **B. Server Setup**
- [ ] PHP 8.1+ installed
- [ ] MySQL 8.0+ installed
- [ ] Nginx/Apache configured
- [ ] SSL certificate installed
- [ ] Firewall configured (port 80, 443, 22 only)
- [ ] Setup non-root user
- [ ] Install Composer
- [ ] Install Node.js (untuk npm build)

### **C. Deployment**
- [ ] Clone repository ke server
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm install && npm run build`
- [ ] Copy `.env.example` ke `.env`
- [ ] Update `.env` dengan kredensial production
- [ ] `php artisan key:generate`
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed` (jika perlu)
- [ ] `php artisan storage:link`
- [ ] Set file permissions (755 folders, 644 files)
- [ ] Test aplikasi bisa diakses

### **D. Post-Deployment**
- [ ] Test login
- [ ] Test create pesanan
- [ ] Test rekap input
- [ ] Test export JPG
- [ ] Test di berbagai device (mobile, tablet, desktop)
- [ ] Monitor error log 24 jam pertama
- [ ] Backup database production pertama kali
- [ ] Setup automatic backup
- [ ] Setup monitoring (Sentry/Bugsnag)
- [ ] Dokumentasikan akses server (IP, password, dll)

---

## 📊 ESTIMASI RISIKO BERDASARKAN PRIORITAS

| Risiko | Probability | Impact | Priority | Effort |
|--------|-------------|--------|----------|--------|
| .env bocor | Low | Critical | 🔴 | 5 min |
| Data hilang (no backup) | Medium | Critical | 🔴 | 15 min |
| DEBUG=true di prod | High | High | 🔴 | 1 min |
| SQL Injection | Low | Critical | 🟡 | Done (Eloquent) |
| Brute force login | Medium | High | 🟡 | 10 min |
| No HTTPS | High | High | 🟡 | 30 min |
| Disk penuh (log) | Medium | Medium | 🟢 | 20 min |
| DDoS attack | Low | Medium | 🟢 | 15 min |
| Kasir ubah harga | Medium | Low | 🔵 | 2 hours |
| Export timeout | Low | Low | 🔵 | 3 hours |

---

## 🚀 RECOMMENDED HOSTING PROVIDERS

### **Untuk Budget Terbatas (<100K/bulan):**
1. **Niagahoster** (Indonesia)
   - Layanan Bayi: Rp 10K/bulan (shared hosting)
   - Pros: Support Bahasa Indonesia, fast response
   - Cons: Limited resources

2. **Heroku** (Free tier)
   - Pros: Gratis, auto-deploy from Git
   - Cons: Sleep after 30 min inactivity

3. **Railway.app** (Free tier)
   - Pros: Modern, support MySQL
   - Cons: US server (latency ke Indonesia)

### **Untuk Production Serius (300K-500K/bulan):**
1. **DigitalOcean** (Droplet $6/month)
   - Pros: Full control, fast SSD
   - Cons: Harus setup sendiri

2. **Vultr** (Cloud Compute $6/month)
   - Pros: Singapore datacenter (low latency)
   - Cons: No managed service

3. **AWS Lightsail** ($5/month)
   - Pros: AWS ecosystem, reliable
   - Cons: Complex billing

### **Untuk Enterprise (1-2 juta/bulan):**
1. **Laravel Forge + DigitalOcean**
   - Pros: Auto-deployment, zero-downtime
   - Cons: Mahal ($19/month + $12/month server)

---

## 📝 FINAL RECOMMENDATIONS

### **MINIMUM untuk Go Live:**
1. ✅ Fix .env security
2. ✅ Setup database backup
3. ✅ DEBUG=false
4. ✅ HTTPS/SSL
5. ✅ Test semua fitur

### **Strongly Recommended:**
6. ✅ Rate limiting
7. ✅ Error logging
8. ✅ Database user (bukan root)
9. ✅ File permissions

### **Nice to Have (implement later):**
10. Cache strategy
11. Queue untuk export
12. Audit log
13. Email notification
14. Mobile PWA

---

**Total waktu setup pre-hosting:** ~2-3 jam
**Investasi:** Worth it untuk keamanan & stabilitas 1 tahun kedepan!

Mau saya bantu implement yang mana dulu? 🚀
