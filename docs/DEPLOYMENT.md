# Deployment & Post-Deploy Checklist

Panduan ringkas untuk men-deploy Penta Tri Saka (Laravel API + Next.js frontend).

## Backend (Laravel)

### 1. Siapkan environment
Salin template dan isi nilai produksi:

```bash
cp backend/.env.production.example backend/.env
```

Wajib diperhatikan:
- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://api.domain-anda.com`
- `APP_KEY` — generate: `php artisan key:generate`
- `DB_*` — koneksi MySQL produksi
- `FILESYSTEM_DISK=public` (foto checkout/checkin disimpan di `storage/app/public`)
- `CORS_ALLOWED_ORIGINS=https://app.domain-anda.com` (origin frontend, pisah koma)
- `OWNER_EMAIL`, `OWNER_PASSWORD` (≥ 12 karakter), `OWNER_NAME` — untuk seeder produksi

### 2. Install & build
```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan key:generate   # jika APP_KEY masih kosong
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Database & storage
```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder
php artisan storage:link
```

Pastikan direktori berikut writable oleh web server: `storage/`, `bootstrap/cache/`.

### 4. Verifikasi
- `GET {APP_URL}/up` / endpoint health sesuai konfigurasi server.
- Login owner dengan `OWNER_EMAIL` / `OWNER_PASSWORD`.
- Upload foto checkout → file muncul di `storage/app/public/...` dan bisa diakses via `/storage/...`.
- CORS: request dari domain frontend tidak ditolak.

### 5. Operasional
- Queue: jika `QUEUE_CONNECTION=database`, jalankan worker (`php artisan queue:work`) via supervisor/cron sesuai kebutuhan.
- Backup DB: `scripts/dump-db.ps1` (Windows) atau `scripts/dump-db.sh` (Linux). Simpan hasil di lokasi aman (folder `backups/` di-ignore git).
- Jadwalkan backup berkala dan uji restore.

## Frontend (Next.js)

### 1. Environment
```bash
cp frontend/.env.production.example frontend/.env.local
# set NEXT_PUBLIC_API_URL=https://api.domain-anda.com/api
```

### 2. Build & jalankan
```bash
cd frontend
npm ci
npm run build
npm run start
```

Arahkan reverse proxy/domain ke proses Next.js (default port 3000) atau platform hosting pilihan.

### Static export (opsional)
Aplikasi saat ini mengambil data dari API di sisi client, sehingga berpotensi di-export statis. Jika ingin hosting sebagai file statis (CDN/shared hosting), evaluasi dan tambahkan di `frontend/next.config.ts`:
```ts
const nextConfig = {
  output: "export",
  images: { unoptimized: true },
};
export default nextConfig;
```
Lalu `npm run build` menghasilkan folder `out/`. Uji semua alur (auth, upload foto, fetch API lintas origin) karena static export punya batasan (tidak ada server runtime Next.js).

## Checklist pasca-deploy
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` terisi dan rahasia
- [ ] Migrasi sukses, seeder owner produksi sukses
- [ ] Akun demo lokal (`admin@penta.local`, dll.) tidak ada di DB produksi
- [ ] `storage:link` dibuat, upload & tampil foto berfungsi
- [ ] CORS hanya mengizinkan origin frontend produksi
- [ ] HTTPS aktif (frontend & backend)
- [ ] Backup database terjadwal dan pernah diuji restore
- [ ] Log/error monitoring aktif

## Catatan fitur
- **Item paket berdiri sendiri** (tidak mengambil dari master barang satuan). Konfirmasi final ke owner masih diperlukan.
- **Foto**: dikompres di browser (max 1600px, JPEG) lalu dikompres ulang + thumbnail di server. Riwayat foto per surat jalan tersedia di menu Surat Jalan (tombol **Lihat**).
- **Aset desain besar** (`Design System Part 1.pdf`, `design system/`) tidak ikut git; gunakan Git LFS bila perlu versi-kontrol.
