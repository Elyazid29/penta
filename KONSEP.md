# PENTA TRI SAKA - TECHNICAL SPECIFICATION & CODEX BLUEPRINT

## 1. Ikhtisar Aplikasi & Domain Bisnis
Penta Tri Saka Enterprise Operations adalah sistem aplikasi web pengelolaan operasional internal terpadu untuk perusahaan Rental Audio Visual (AV) & Dokumentasi Video.

Aplikasi memusatkan alur kerja operasional:
- Pengelolaan master data aset (alat AV satuan & paket).
- Pengelolaan SDM (karyawan/teknisi, leader, driver) dan akun pengguna.
- Pengelolaan database klien & armada operasional.
- Transaksi awal (Form Order & Form Penawaran/Quotation).
- Manajemen penjadwalan operasional.
- Kontrol lapangan (Surat Jalan & checklist/foto delivery).
- Penagihan & keuangan (Invoice, transaksi, laporan).

## 2. Stack Teknologi & Arsitektur
Arsitektur terpisah Frontend dan Backend RESTful API:

- **Frontend**: Next.js (React, App Router) — `frontend/` (`src/app`).
- **Styling**: Tailwind CSS.
- **Icon**: Lucide Icons.
- **Backend**: Laravel (PHP) — `backend/`, REST API (`/api/v1/...`).
- **Autentikasi API**: Laravel Sanctum (Bearer token).
- **Database**: MySQL.
- **Storage**: disk `public` (`storage/app/public`, akses via `storage:link`).
- **Kompresi foto**:
  - Client-side (browser/canvas) sebelum upload: resize maks 1600px, JPEG quality ~0.75 (`frontend/src/app/compress-image.ts`) untuk checkout/checkin/barang susulan.
  - Server-side (`backend/app/Services/PhotoStorage.php`): normalisasi/resize maks 1600px JPEG q75 + thumbnail 320px q70; fallback simpan file asli bila encoding gagal.

## 3. Panduan Style Design (Corporate Minimalist Blue)
- **Primary**: #1E40AF / #2563EB (navbar, CTA, header, status aktif).
- **Secondary**: #EFF6FF / #DBEAFE (background aktif, hover, badge netral).
- **Background utama**: #F8FAFC.
- **Card/container**: #FFFFFF, border #E2E8F0, bayangan halus.
- **Teks**: utama #0F172A, sekunder #64748B. Font sans-serif (Inter/Roboto).
- **Spacing**: lega (negative space) untuk kenyamanan penggunaan lama.

## 4. Dokumentasi Fitur Utama

### A. Dashboard (Overview Operasional & Keuangan)
Ringkasan untuk Owner/Admin: indikator operasional, proyek/order aktif, ringkasan keuangan/piutang/hutang/arus kas, dan tabel aksi (order/invoice) sesuai data API.

### B. Database (Master Data Management)
Modul input & pengelolaan data dasar:

- **Data Barang (Satuan & Paket)**
  - **Satuan** (`master_barangs`): kode, nama, kategori, satuan, deskripsi/spesifikasi, foto, jumlah total/rusak/di sewa, harga rental.
  - **Paket** (`master_pakets` + `master_paket_items`): kode, nama, kategori/sub kategori, satuan, harga paket, catatan. **Item paket berdiri sendiri** (diinput bebas, tidak dipilih dari master barang satuan) karena sebagian barang hanya disewakan per paket/tidak satuan. Tiap item menyimpan `nama_item`, `qty` (volume), `satuan`, `harga`, `urutan`; `barang_id` tidak digunakan/dikosongkan oleh aplikasi. *Status: keputusan final owner masih perlu dikonfirmasi.*
- **Data Karyawan** (`master_karyawans`): nama, NIK, no. telp, jabatan, no. rekening, status (Aktif/Non-Aktif), KTP, dll. Karyawan dapat diberi akun login (email/password, role teknisi) dan ditautkan via `user_id`.
- **Data Klien** (`master_kliens`): nama klien/perusahaan, alamat, beberapa contact person, NPWP, kategori.
- **Data Armada** (`master_armadas`): jenis kendaraan, plat nomor, status STNK, driver, status operasional.
- **Data Perusahaan** (`master_perusahaans`): identitas, alamat, kontak, kop/logo, rekening bank.

### C. Form Order
Input pesanan sewa: klien, event, venue/ruangan/lantai, load-in/load-out, status, catatan, dan daftar item sewa.

### D. Form Penawaran (Quotation)
Konversi Form Order menjadi penawaran harga (nomor unik, item, subtotal/diskon/pajak/total, status) untuk kebutuhan dokumen/kirim/print.

### E. Surat Jalan & Field Checklist (Delivery)
- **Admin/Owner**: penerbitan surat jalan (nomor, order terkait, tanggal berangkat, status, catatan), **daftar barang otomatis dari Form Order** (tanpa input item manual), penugasan teknisi (multi) dari `master_karyawans` (jabatan teknis, aktif). Tabel menampilkan teknisi, jumlah item, status; pencarian tersedia. **Riwayat foto** per surat jalan dapat dilihat (thumbnail + foto asli, tipe checkout/checkin, user, waktu, checklist).
- **Teknisi (mobile)**: scan/input kode surat jalan, lihat detail & item, checklist kondisi, foto checkout/checkin, input barang susulan. Foto dikompres di client & server; server menyimpan foto + thumbnail. Jika surat jalan **tidak** memiliki teknisi bertugas (akun login) → teknisi mana pun boleh checkout/checkin; jika ada penugasan, hanya teknisi yang ditugaskan (atau admin/owner) yang diizinkan.
- **Endpoint terkait**: scan surat jalan, checkout, checkin, barang susulan, dan `GET v1/surat-jalan/{suratJalan}/photos` (riwayat foto).

### F. Penjadwalan (Crew & Event)
Penjadwalan/matriks operasional crew, armada, dan alat (sesuai modul jadwal operasional yang tersedia).

### G. Invoice & Keuangan
Invoice dari operasional/order, pencatatan transaksi/pembayaran (DP/pelunasan/piutang/hutang/biaya), ringkasan keuangan.

### H. Laporan
Ringkasan/rekap operasional dan keuangan; ekspor data (mis. CSV transaksi) sesuai endpoint yang tersedia.

### I. User Management & Akses
- Role utama: **Owner**, **Admin**, **Teknisi** (sumber kebenaran `role_id` → `roles`). Middleware `check.role` membatasi akses.
- Owner dapat mengelola akun pengguna (list/create/update/delete, pilih role) via API/UI; tidak dapat menghapus diri sendiri.
- Akun teknisi dapat dibuat/ditautkan dari form Data Karyawan.

## 5. Skema Database (Ringkasan Implementasi Aktual)
*Nama tabel mengikuti migration (umumnya plural). Detail kolom lengkap lihat `backend/database/migrations`.*

- **master_barangs**: barang satuan (kode unik, nama, kategori, satuan, deskripsi, foto, stok total/rusak/disewa, harga_rental, timestamps).
- **master_pakets**: paket (kode unik, nama, kategori, sub_kategori, harga_paket, satuan default `paket`, catatan, timestamps).
- **master_paket_items**: item paket standalone (paket_id FK, `barang_id` nullable & tidak dipakai aplikasi, `nama_item`, `qty` decimal, `satuan`, `harga`, `urutan`). Tidak bergantung ke `master_barangs`.
- **master_karyawans**: data karyawan (+ `user_id` opsional untuk tautan akun login), status, dokumen KTP, timestamps.
- **master_kliens**: data klien & contact persons.
- **master_armadas**: data armada & status.
- **master_perusahaans**: identitas & rekening perusahaan.
- **form_orders**, **form_order_items**, **form_penawarans**, **form_penawaran_items**: order & penawaran.
- **surat_jalans**, **surat_jalan_items**, **surat_jalan_teknisi** (pivot penugasan karyawan): surat jalan & item dari order.
- **delivery_photos**, **delivery_item_checklists**, **delivery_additional_items**: foto checkout/checkin (path + `foto_thumb_path`), checklist kondisi, barang susulan.
- **invoices**, **transaksi_keuangans**, **jadwal_operasionals**, dan tabel pendukung lain sesuai modul.
- **users**, **roles** (seed owner/admin/teknisi), **personal_access_tokens** (Sanctum).

## 6. Deployment & Operasional
- Template: `backend/.env.production.example`, `frontend/.env.production.example`. CORS dikonfigurasi via `CORS_ALLOWED_ORIGINS`.
- Seeder produksi: `php artisan db:seed --class=ProductionSeeder` (wajib `OWNER_EMAIL`, `OWNER_PASSWORD` ≥ 12).
- Backup DB: `scripts/dump-db.ps1`, `scripts/dump-db.sh` (output `backups/`, di-ignore git).
- Panduan & checklist: `docs/DEPLOYMENT.md`.
- Aset desain besar (`Design System Part 1.pdf`, `design system/`) tidak di-commit; pertimbangkan Git LFS bila perlu.

## 7. Fokus & Catatan Lanjutan
- Konfirmasi owner: kebijakan final item paket standalone (tidak bisa disewa satuan).
- Penyempurnaan: mode offline checklist, konflik penjadwalan, ekspor Excel/PDF lanjutan, audit log, penguatan test hak akses & cakupan fitur.
- Deploy ke hosting mengikuti `docs/DEPLOYMENT.md` (saat infrastruktur tersedia).
