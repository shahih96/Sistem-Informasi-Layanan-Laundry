# 📦 PRE-HOSTING CHECKLIST & QUICK START
## Persiapan Hosting - Qxpress Laundry System

---

## ✅ **YANG SUDAH DISIAPKAN**

Saya sudah prepare file-file berikut untuk kamu:

### **1. `.env.example`** ✅
Template konfigurasi production (tanpa password asli)
- `APP_DEBUG=false` (production mode)
- `APP_ENV=production`
- `LOG_LEVEL=error`
- Database & email placeholders

### **2. `backup-database.bat`** ✅
Script backup database 1-klik (Windows)
- Auto create folder `backup/`
- Auto delete backup > 7 hari
- Format filename: `laundry_backup_20251104_200741.sql`

**⚠️ Note:** Perlu install MySQL tools di PATH, atau backup manual via phpMyAdmin

### **3. `DEPLOYMENT_GUIDE.md`** ✅
Panduan lengkap step-by-step upload ke Niagahoster
- Screenshot-friendly instructions
- Troubleshooting common errors
- Post-deployment checklist

### **4. `OPTIMIZATION_GUIDE.md`** ✅
Performance & security enhancements
- Rate limiting (anti spam)
- Caching strategy
- Image optimization
- Security headers

### **5. `.gitignore`** ✅
Sudah ada, pastikan `.env` aman tidak ter-upload

---

## 🎯 **LANGKAH CEPAT SEBELUM HOSTING**

### **STEP 1: Backup Database** (5 menit)

**Cara 1: Via phpMyAdmin (Recommended)**
1. Buka http://localhost/phpmyadmin
2. Pilih database `tugasakhir`
3. Tab **Export** → Method: **Quick** → Format: **SQL**
4. Click **Export**
5. Save file: `backup_laundry_2025-11-04.sql`

**Cara 2: Via Script (jika MySQL di PATH)**
```powershell
# Double-click file
backup-database.bat
```

**Hasil:** File backup `.sql` siap untuk di-import ke server ✅

---

### **STEP 2: Compress Project** (10 menit)

**Folder yang PERLU di-zip:**
```
✅ app/
✅ bootstrap/
✅ config/
✅ database/
✅ public/ (semua file di dalamnya)
✅ resources/
✅ routes/
✅ storage/ (folder struktur, bukan file cache)
✅ vendor/ (jika sudah run composer install)
✅ artisan
✅ composer.json
✅ composer.lock
✅ package.json
✅ vite.config.js
✅ tailwind.config.js
✅ .htaccess (di root jika ada)
```

**Folder yang JANGAN di-zip:**
```
❌ node_modules/ (terlalu besar, install ulang di server)
❌ .env (buat baru di server)
❌ .git/ (tidak perlu)
❌ backup/ (database backup terpisah)
❌ .vscode/ (editor config)
❌ .idea/ (editor config)
❌ storage/logs/*.log (log file)
❌ storage/framework/cache/* (cache)
❌ storage/framework/sessions/* (session)
❌ storage/framework/views/* (compiled views)
```

**Cara Compress:**
1. Buka File Explorer → Navigate ke folder project
2. **Pilih folder/file sesuai checklist di atas**
3. Klik kanan → **Send to** → **Compressed (zipped) folder**
4. Nama: `qxpress-laundry-system.zip`

**Ukuran estimasi:** ~30-80 MB (tergantung vendor/)

**Hasil:** File `qxpress-laundry-system.zip` siap upload ✅

---

### **STEP 3: Catat Informasi Penting** (3 menit)

**Buat file `HOSTING_INFO.txt` berisi:**

```
==========================================
QXPRESS LAUNDRY - HOSTING INFORMATION
==========================================

DATABASE (LOCAL):
- Name: tugasakhir
- Backup file: backup_laundry_2025-11-04.sql
- Tables: ~15 tables
- Size: ~[check di phpMyAdmin]

ADMIN LOGIN (LOCAL):
- Email: [email admin kamu]
- Password: [password admin]

RECAPTCHA (TRACKING PAGE):
- Site Key: [dari .env - NOCAPTCHA_SITEKEY]
- Secret Key: [dari .env - NOCAPTCHA_SECRET]

DOMAIN (PLANNED):
- Pilihan 1: qxpresslaundry.com
- Pilihan 2: qxpress-laundry.com
- Pilihan 3: [alternatif]

HOSTING PLAN:
- Provider: Niagahoster
- Paket: Bayi Cloud (Rp 60K/bulan)
- Durasi: 1 tahun
- Gratis: Domain + SSL

==========================================
CHECKLIST SEBELUM BELI HOSTING
==========================================
[✅] Database backup created
[✅] Project compressed (zip)
[✅] .env.example configured
[✅] Admin credentials recorded
[✅] reCAPTCHA keys noted
[✅] Domain name decided
[✅] Budget ready (Rp 720K/tahun)

==========================================
NEXT STEPS
==========================================
1. Beli hosting Niagahoster Bayi Cloud
2. Pilih domain gratis (.com)
3. Tunggu aktivasi (5-10 menit)
4. Login cPanel
5. Follow DEPLOYMENT_GUIDE.md
6. Upload project zip
7. Import database backup
8. Configure .env
9. Enable SSL
10. Test & GO LIVE! 🚀
```

**Save file ini untuk referensi!**

---

## 📋 **QUICK DEPLOYMENT CHECKLIST**

Print ini dan centang saat deploy:

### **Pre-Deployment (Di Laptop)**
- [ ] Database backup created (`.sql` file)
- [ ] Project compressed (`.zip` file, no `node_modules/`)
- [ ] `.env.example` sudah dikonfigurasi
- [ ] Admin credentials dicatat
- [ ] reCAPTCHA keys dicatat
- [ ] Hosting info noted (`HOSTING_INFO.txt`)

### **Beli Hosting**
- [ ] Hosting Niagahoster Bayi Cloud dibeli
- [ ] Domain chosen & registered
- [ ] Email aktivasi diterima
- [ ] cPanel login berhasil

### **Upload Files**
- [ ] Login cPanel → File Manager
- [ ] Hapus file default di `public_html/`
- [ ] Upload `qxpress-laundry-system.zip`
- [ ] Extract ke `public_html/`
- [ ] Pindahkan isi `public/` ke root
- [ ] Edit `index.php` (fix path)
- [ ] Set permission `storage/` = 755
- [ ] Set permission `bootstrap/cache/` = 755

### **Database Setup**
- [ ] Create database via cPanel
- [ ] Create database user
- [ ] Grant all privileges
- [ ] Import `.sql` via phpMyAdmin
- [ ] Verify tables imported

### **Configuration**
- [ ] Create `.env` file di server
- [ ] Copy from `.env.example`
- [ ] Update `APP_URL` (domain kamu)
- [ ] Update `DB_*` (credentials)
- [ ] Update `NOCAPTCHA_*` (reCAPTCHA)
- [ ] Generate `APP_KEY`
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`

### **SSL & Security**
- [ ] SSL certificate installed (cPanel → SSL/TLS Status)
- [ ] Force HTTPS (edit `.htaccess`)
- [ ] Test HTTPS working (gembok hijau)

### **Optimization**
- [ ] Run `php artisan optimize:clear`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Create `storage` symlink

### **Testing**
- [ ] Landing page accessible
- [ ] Tracking page working
- [ ] Daftar harga page working
- [ ] Admin login working
- [ ] Dashboard shows data
- [ ] Pesanan CRUD working
- [ ] Rekap working
- [ ] Images loading
- [ ] reCAPTCHA working
- [ ] No 500 errors
- [ ] Mobile responsive

### **Post-Deployment**
- [ ] Setup auto backup (cron job)
- [ ] Monitor error logs (24 jam pertama)
- [ ] Share link ke team
- [ ] Update DNS jika perlu
- [ ] Dokumentasi akses server

---

## 🚀 **ESTIMASI WAKTU**

| Tahap | Waktu | Status |
|-------|-------|--------|
| **PRE-DEPLOYMENT** |
| Backup database | 5 menit | ✅ Ready |
| Compress project | 10 menit | Pending |
| Catat info penting | 3 menit | Pending |
| **HOSTING** |
| Beli hosting | 10 menit | Pending |
| Aktivasi | 5-10 menit | Auto |
| **DEPLOYMENT** |
| Upload files | 15 menit | Pending |
| Setup database | 10 menit | Pending |
| Configure .env | 5 menit | Pending |
| Enable SSL | 5 menit | Pending |
| Testing | 15 menit | Pending |
| **TOTAL** | **~1.5-2 jam** | |

---

## 📞 **SUPPORT & RESOURCES**

### **Dokumentasi:**
- `DEPLOYMENT_GUIDE.md` - Step-by-step deployment
- `OPTIMIZATION_GUIDE.md` - Performance & security
- `HOSTING_READINESS_CHECKLIST.md` - Risks & mitigation

### **Niagahoster Support:**
- WhatsApp: 0804-1-808-888
- Live Chat: https://www.niagahoster.co.id
- Email: sales@niagahoster.co.id

### **Helpful Links:**
- Laravel Deployment: https://laravel.com/docs/11.x/deployment
- SSL Test: https://www.ssllabs.com/ssltest/
- PageSpeed Test: https://pagespeed.web.dev
- Mobile-Friendly Test: https://search.google.com/test/mobile-friendly

---

## ❓ **FAQ**

**Q: Berapa lama proses deployment?**
A: Pertama kali ~1.5-2 jam. Update berikutnya ~10-15 menit.

**Q: Apakah perlu install Composer/Node.js di server?**
A: Tidak wajib jika `vendor/` dan `public/build/` sudah ada di zip. Tapi recommended untuk update.

**Q: Bagaimana cara update sistem setelah live?**
A: 1) Backup database, 2) Upload file baru, 3) Clear cache, 4) Test.

**Q: Berapa biaya total hosting?**
A: Rp 720K/tahun (domain + SSL gratis) = Rp 60K/bulan.

**Q: Apa yang terjadi jika lupa password cPanel?**
A: Contact Niagahoster support via chat/WA.

**Q: Database local akan terhapus saat hosting?**
A: Tidak! Database local tetap aman. Kamu hanya "copy" data ke server.

**Q: Apakah bisa ubah domain setelah hosting?**
A: Bisa, tapi ribet. Pilih domain yang pasti dari awal.

---

## ✅ **READY TO GO LIVE?**

**Checklist terakhir:**
- [ ] File backup `.sql` sudah ada
- [ ] File project `.zip` sudah dibuat
- [ ] Info penting sudah dicatat
- [ ] Budget hosting ready (Rp 720K)
- [ ] Domain name sudah decided
- [ ] Waktu luang 2 jam untuk deploy

**Kalau semua ✅, kamu siap beli hosting & deploy!**

---

**Next Action:**
1. **Compress project** (ikuti STEP 2 di atas)
2. **Beli hosting** Niagahoster Bayi Cloud
3. **Follow** `DEPLOYMENT_GUIDE.md`
4. **GO LIVE!** 🚀

**Questions?** Tanya sebelum mulai deploy!

🎉 **Good luck with the deployment!**
