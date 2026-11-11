PENTA TRI SAKA - TECHNICAL SPECIFICATION & CODEX BLUEPRINT
1. IKHTISAR APLIKASI & DOMAIN BISNIS
Penta Tri Saka Enterprise Operations adalah sistem aplikasi web pengelolaan operasional internal terpadu untuk perusahaan yang bergerak di bidang Rental Audio Visual (AV) & Dokumentasi Video.

Aplikasi ini berfungsi untuk memusatkan seluruh alur kerja operasional perusahaan, mulai dari:

Pengelolaan Master Data Aset (Alat AV satuan & paket).

Pengelolaan Sumber Daya Manusia (Teknisi, Leader, Driver).

Pengelolaan Database Klien & Armada Operasional.

Transaksi awal (Form Order & Form Penawaran / Quotation).

Manajemen Penjadwalan Crew & Alat (Matrix Scheduling).

Kontrol Lapangan (Surat Jalan & Field Checklist).

Penagihan & Keuangan (Invoice, Piutang, Hutang, Laporan Finansial).

2. STACK TEKNOLOGI & ARSITEKTUR
Sistem dibangun menggunakan arsitektur Modern Single-Page Application (SPA) / Progressive Web App (PWA) dengan pemisahan Frontend dan Backend RESTful API:

Frontend Framework: Next.js (React 18+ App Router) / HTML5 Single File Prototype.

Styling Engine: Tailwind CSS 3.x / Vanilla CSS.

Iconography: Lucide Icons / Heroicons.

Backend Framework: Laravel 11+ (PHP 8.3+).

Autentikasi API: Laravel Sanctum (Stateful / Bearer Token).

Database Management System (DBMS): MySQL 8.0+ / MariaDB.

Offline Storage (Mobile Field Checklist): IndexedDB / LocalForage (untuk penyimpanan sementara foto & checklist di lokasi minim sinyal).

Image Processing Engine: Client-side WebP Compression (browser-image-compression) sebelum diunggah ke server.

3. PANDUAN STYLE DESIGN (CORPORATE MINIMALIST BLUE)
Sistem ini menggunakan gaya Corporate Minimalist White & Blue yang bersih, profesional, dan memberikan ruang visual (negative space) yang lega agar nyaman digunakan dalam durasi lama.

Primary Color (Biru Utama): #1E40AF / #2563EB (Royal Blue) – Digunakan untuk Navbar, Button CTA, Header, dan Status Aktif.

Secondary Color (Biru Muda): #EFF6FF / #DBEAFE (Soft Blue Tint) – Digunakan untuk background card aktif, hover state, dan badge netral.

Background Utama: #F8FAFC (Slate Very Light Gray) – Memberikan kontras lembut di mata.

Card & Container Background: #FFFFFF (Pure White) dengan border tipis #E2E8F0 dan bayangan halus (shadow-sm).

Text & Typography: Teks utama #0F172A (Slate Dark), Teks sekunder #64748B (Muted Gray). Font: Inter / Roboto (Sans-serif).

Negative Space: Jarak antar elemen longgar (gap-6, padding-6/8) untuk menghindari kesan padat/sesak.

4. DOKUMENTASI FITUR UTAMA (SHORT SUMMARY)
A. Dashboard (Overview Operasional & Keuangan)
Memberikan ringkasan helikopter bisnis bagi Owner:

Status Teknisi Counters: Kartu indikator SDM bertugas (75), standby (25), dan izin (10).

Projects Active: Monitoring proyek berjalan di berbagai lokasi (18 Lokasi) seperti Tiga Kreasi (HK Tower), Artotel, dan Fairmont.

Keuangan Ringkas: Nilai Piutang Klien (Rp 4.325.867.000), Hutang Vendor (Rp 1.325.867.000), dan grafik arus kas bulanan.

Action Tables: Ringkasan Form Order Belum Confirm dan Invoice Belum Lunas.

B. Database (Master Data Management - FOKUS UTAMA OWNER)
Modul utama tempat Owner menginput dan mengelola seluruh aset dasar perusahaan:

Data Barang (Satuan & Paket):

Mode SATUAN: Input Kode, Nama Barang, Kategori (Camera, Sound, Lighting, TV, LED, Hybrid, Production), Spesifikasi, Upload Foto, Total Stok, Stok Rusak, Di Sewa, dan Harga Rental/hari.

Mode PAKET: Kombinasi beberapa barang satuan menjadi 1 paket harga (contoh: Hybrid System Standar).

Data Karyawan: Input Nama, NIK, No KTP, Jabatan, No Telp/WA, No Rekening Bank, Upload Foto KTP, dan Status (Aktif / Non-Aktif).

Data Klien: Input Nama Klien, Nama Perusahaan (PT/CV), Alamat, Multiple Contact Persons (CP1, CP2, CP3), NPWP, dan Kategori.

Data Armada: Pengelolaan kendaraan operasional (Mobil Box, Pickup, Blindvan), Plat Nomor, Status STNK, Driver, dan Status Jalan/Standby.

Data Perusahaan: Pengaturan Identitas Penta Tri Saka, Alamat Head Office, KOP Surat Resmi, Logo, dan Rekening Bank.

C. Form Order (Pemesanan Masuk)
Input pesanan sewa awal dari klien mencakup data event, lokasi/venue, ruangan/lantai, tanggal load-in/load-out, serta daftar alat yang disewa.

D. Form Penawaran (Quotation)
Modul untuk mengonversi Form Order menjadi dokumen resmi Surat Penawaran Harga (Quotation) ber-KOP perusahaan yang siap dicetak ke PDF atau dikirim via WA.

E. Surat Jalan & Field Checklist
Owner/Admin View: Penerbitan Surat Jalan dengan nomor otomatis (SJ-001), penugasan tim teknisi/driver, jam mulai/selesai, serta daftar barang bawaan.

Teknisi Field Checklist: Antarmuka mobile untuk verifikasi barang berangkat (check-out) & kembali (check-in), input foto kondisi barang (auto-WebP), serta penambahan barang susulan (ad-hoc) di lapangan.

F. Penjadwalan (Crew & Event Matrix)
Tabel matriks kalender yang memetakan:

Teknisi Bertugas: Lokasi venue, ruangan, tim kameramen/soundman, dan jobdesk alat.

Teknisi Standby & Izin: Lokasi standby (misal: Baladewa, Narada), tugas sebelumnya, jam selesai, dan alasan izin.

G. Invoice & Keuangan
Penerbitan tagihan resmi berdasarkan Surat Jalan selesai, pencatatan status pembayaran (Unpaid/DP/Paid), serta pencatatan biaya operasional event.

H. Laporan
Pusat analitik bulanan/tahunan untuk mengekspor rekapitulasi operasional, utilitas alat, serta laporan laba/rugi ke format Excel/PDF.

5. SKEMA DATABASE (DATABASE SCHEMA RELATIONS)
SQL
-- 1. Master Barang Satuan
CREATE TABLE master_barang (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    kode_barang VARCHAR(50) UNIQUE NOT NULL,
    nama_barang VARCHAR(255) NOT NULL,
    kategori ENUM('Camera', 'Sound System', 'Lighting System', 'TV', 'LED Videotron', 'Streaming & Hybrid', 'Produksi') NOT NULL,
    deskripsi TEXT,
    foto_path VARCHAR(255),
    jumlah_total INT DEFAULT 0,
    jumlah_rusak INT DEFAULT 0,
    di_sewa INT DEFAULT 0,
    harga_rental DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- 2. Master Barang Paket
CREATE TABLE master_paket (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    kode_paket VARCHAR(50) UNIQUE NOT NULL,
    nama_paket VARCHAR(255) NOT NULL,
    kategori VARCHAR(100),
    harga_paket DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- 3. Detail Item Paket
CREATE TABLE master_paket_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    paket_id BIGINT,
    barang_id BIGINT,
    qty INT DEFAULT 1,
    FOREIGN KEY (paket_id) REFERENCES master_paket(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES master_barang(id) ON DELETE CASCADE
);

-- 4. Master Karyawan
CREATE TABLE master_karyawan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama_lengkap VARCHAR(255) NOT NULL,
    nik VARCHAR(50),
    no_ktp VARCHAR(50),
    jabatan VARCHAR(100),
    no_telp VARCHAR(50),
    no_rekening VARCHAR(100),
    ktp_path VARCHAR(255),
    status ENUM('Aktif', 'Non-Aktif') DEFAULT 'Aktif',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- 5. Master Klien
CREATE TABLE master_klien (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama_klien VARCHAR(255) NOT NULL,
    nama_perusahaan VARCHAR(255),
    alamat TEXT,
    cp1_nama VARCHAR(100), cp1_telp VARCHAR(50),
    cp2_nama VARCHAR(100), cp2_telp VARCHAR(50),
    cp3_nama VARCHAR(100), cp3_telp VARCHAR(50),
    npwp VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
6. INSTRUKSI EKSEKUSI CODEX / DEVELOPER
Fokus Utama Saat Ini: Selesaikan seluruh Form Input dan CRUD pada Modul DATABASE (Master Data) agar Owner dapat langsung menginput data nyata barang, karyawan, dan klien.

Gunakan Corporate Minimalist White & Blue Design System pada seluruh komponen UI.

Pastikan seluruh modal input (Tambah Barang, Tambah Karyawan, Tambah Klien) berfungsi dengan validasi form yang ketat dan feedback visual yang jelas.