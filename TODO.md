# TODO — Penta Tri Saka Enterprise Operations

## Status saat ini
- [x] Prototype UI corporate minimalist blue
- [x] CRUD lokal: Master Barang, Karyawan, dan Klien
- [x] Dashboard ringkasan dan pencarian data
- [ ] Migrasi prototype ke Next.js App Router
- [ ] Backend Laravel REST API dan MySQL

## Phase 1 — Fondasi production
- [ ] Inisialisasi monorepo `frontend` (Next.js) dan `backend` (Laravel)
- [x] Konfigurasi template environment MySQL, Sanctum, storage, dan CORS
- [x] Autentikasi token Sanctum dan role Owner/Admin/Leader/Teknisi/Driver
- [ ] Migration dan API CRUD: barang, paket, karyawan, klien, armada, perusahaan`r`n- [x] Master Barang, Master Paket, Master Karyawan, Master Klien, Master Armada, dan Master Perusahaan (API CRUD)
- [ ] Upload foto aset dan dokumen KTP yang aman

## Phase 2 — Operasional
- [x] Form Order dengan event, load-in/load-out, venue, dan item sewa
- [x] Quotation dari order dengan nomor unik, item, subtotal, diskon, pajak, dan total
- [ ] Matrix jadwal crew, armada, dan alat
- [ ] Surat Jalan, nomor otomatis, penugasan, dan daftar barang
- [ ] Field checklist check-out/check-in, foto WebP, dan mode offline

## Phase 3 — Keuangan dan laporan
- [ ] Invoice dari Surat Jalan selesai
- [ ] Pencatatan DP, pelunasan, piutang, hutang, dan biaya event
- [ ] Dashboard arus kas dan laporan laba/rugi
- [ ] Ekspor Excel/PDF untuk operasional dan keuangan

## Quality & deployment
- [ ] Feature/unit test API dan hak akses role
- [ ] Uji konflik penjadwalan crew/aset
- [ ] Uji perangkat mobile dan sinkronisasi offline
- [ ] Backup database, audit log, dan deployment
- [x] Dashboard Owner dengan sidebar dan ringkasan API