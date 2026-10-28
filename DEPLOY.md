# Panduan Hosting: PHP Hosting Biasa + Supabase

Panduan ini untuk memasang aplikasi di hosting PHP bersama (cPanel atau
sejenisnya) dengan database PostgreSQL di Supabase.

## 1. Syarat hosting

- PHP 8.3 atau lebih baru, dengan ekstensi `pdo_pgsql`, `mbstring`, `openssl`,
  `fileinfo`, `gd` (untuk PDF kuitansi), `zip`, `bcmath`.
  Di cPanel: menu *Select PHP Version* lalu centang ekstensi tersebut.
- Document root domain harus mengarah ke folder `public/`.
  Di cPanel: *Domains* (atau *Subdomains*) → ubah *Document Root* menjadi
  `.../keuangan/public`. Bila tidak bisa diubah, lihat bagian 7.
- Akses SSH sangat membantu tetapi tidak wajib. Tanpa SSH, folder `vendor/`
  diunggah dari laptop.

## 2. Siapkan Supabase

1. Buat proyek baru di https://supabase.com (region Singapore,
   `ap-southeast-1`, paling dekat ke Indonesia). Simpan kata sandi database.
2. Buka *Project Settings → Database → Connection string*.
   Pilih **Session pooler** (bukan Transaction pooler, bukan Direct).
   Hosting bersama umumnya hanya punya IPv4, dan Laravel memerlukan
   session mode (port 5432).
3. Catat: host `aws-0-ap-southeast-1.pooler.supabase.com`, port `5432`,
   database `postgres`, user `postgres.<ref-proyek>`, dan kata sandi.

## 3. Uji dulu dari laptop ke Supabase

Sebelum mengunggah apa pun, pastikan skema dan data masuk ke Supabase
langsung dari laptop. Tambahkan ke `.env` laptop (sementara):

```
SUPABASE_HOST=aws-0-ap-southeast-1.pooler.supabase.com
SUPABASE_PORT=5432
SUPABASE_DATABASE=postgres
SUPABASE_USERNAME=postgres.<ref-proyek>
SUPABASE_PASSWORD=<kata sandi>
```

Koneksi bernama `supabase` sudah disiapkan di `config/database.php`.

```bash
php artisan migrate --database=supabase --force
php artisan db:salin --dari=sqlite --ke=supabase
```

Perintah `db:salin` menyalin semua data aplikasi (akun pengurus, santri,
tagihan, pembayaran, kuitansi, kas keluar, guru, bisyarah, riwayat
aktivitas) dengan nomor id yang sama, lalu menyetel ulang penomoran
otomatis di PostgreSQL. Jalankan ulang dengan `--ganti` bila ingin menimpa.

## 4. Berkas yang diunggah

Unggah seluruh folder proyek **kecuali**: `node_modules/`, `tests/`,
`.git/`, `storage/app/private/import/` (berisi data pribadi dan daftar
PIN), berkas `.csv`/`.xlsx` di root, dan `database/database.sqlite`.

Folder `vendor/` wajib ikut bila hosting tidak punya Composer. Buat dulu
versi produksinya di laptop agar lebih ringan:

```bash
composer install --no-dev --optimize-autoloader
```

(Setelah selesai mengunggah, kembalikan dengan `composer install` biasa
supaya tes di laptop tetap jalan.)

Cara unggah tercepat: zip seluruh folder di laptop, unggah lewat *File
Manager* cPanel, lalu *Extract* di server.

## 5. Pengaturan `.env` di server

Salin `.env.production.example` menjadi `.env` di server, lalu isi:

- `APP_KEY`: **salin persis dari `.env` laptop.** Kalau berbeda, PIN santri
  dan data terenkripsi lain tidak terbaca.
- `APP_URL`: alamat domain lengkap dengan `https://`.
- `APP_DEBUG=false`, `APP_DEMO_MODE=false`, `APP_ENV=production`.
- `DB_*`: data Supabase dari langkah 2.
- `QUEUE_CONNECTION=sync` (hosting bersama tidak punya queue worker).
- `TREASURER_CONTACTS`: nomor WhatsApp bendahara.

Pastikan folder `storage/` dan `bootstrap/cache/` bisa ditulis (izin 755
biasanya cukup di cPanel karena PHP berjalan sebagai pemilik akun).

## 6. Perintah setelah unggah

Dengan SSH (atau *Terminal* di cPanel):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Tanpa SSH: lewati saja, aplikasi tetap berjalan tanpa cache ini.

Data tidak perlu dimigrasi lagi dari server karena sudah masuk Supabase di
langkah 3.

## 7. Bila document root tidak bisa diarahkan ke `public/`

Letakkan berkas `.htaccess` berikut di folder utama proyek (sejajar
dengan `artisan`), lalu arahkan domain ke folder utama tersebut:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Tambahkan juga di `.env`: `ASSET_URL=https://<domain-anda>`.

## 8. Daftar periksa sebelum demo

- [ ] Halaman depan terbuka lewat `https://` tanpa peringatan sertifikat
      (aktifkan *AutoSSL / Let's Encrypt* di cPanel).
- [ ] `https://<domain>/up` menampilkan status OK.
- [ ] Login pengurus berhasil; halaman kasir, tagihan, laporan, kas keluar,
      bisyarah terbuka tanpa galat.
- [ ] Unduh satu kuitansi PDF dan satu laporan PDF.
- [ ] Login portal wali dengan akun uji coba `UJI00001` / `100001`.
- [ ] Sandi awal akun pengurus: admin `adminamsa2026`, bendahara
      `bendahara2026`, pengasuh `amsa2026`. Minta Bendahara dan Admin
      menggantinya lewat menu *Akun Pengurus* setelah demo.
- [ ] Blok "Akun untuk Uji Coba" tidak tampil (artinya `APP_DEMO_MODE=false`).

## 9. Cadangan data

Supabase membuat cadangan harian otomatis pada paket berbayar; paket gratis
tidak. Cadangan manual dari laptop kapan saja:

```bash
php artisan db:salin --dari=supabase --ke=sqlite --ganti
```

Perintah itu menarik seluruh isi Supabase ke `database/database.sqlite`
di laptop. Simpan berkas SQLite tersebut dengan nama bertanggal.

## 10. Pembaruan aplikasi berikutnya

1. Di laptop: `git pull`, uji, lalu `composer install --no-dev`.
2. Unggah berkas yang berubah (biasanya `app/`, `resources/`, `routes/`,
   `database/migrations/`, `vendor/` bila ada paket baru).
3. Bila ada migrasi baru: jalankan dari laptop
   `php artisan migrate --database=supabase --force`.
4. Di server: `php artisan config:cache && php artisan view:cache` bila
   SSH tersedia; bila tidak, hapus isi `bootstrap/cache/*.php`.
