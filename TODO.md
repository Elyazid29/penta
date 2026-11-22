# TODO — Penta Tri Saka Enterprise Operations

## Status saat ini
- [x] UI corporate minimalist blue (Next.js frontend)
- [x] Monorepo `frontend/` (Next.js App Router) dan `backend/` (Laravel REST API)
- [x] Backend Laravel + MySQL, Sanctum, storage, CORS (env-driven)
- [x] Autentikasi token Sanctum dan role Owner/Admin/Teknisi (`role_id` → `roles`)
- [x] Dashboard ringkasan operasional/keuangan dan pencarian data

## Fase 1 — Fondasi & Master Data
- [x] Migration dan API CRUD: barang, paket, karyawan, klien, armada, perusahaan
- [x] Master Barang satuan & Master Paket (item paket standalone/bebas, bukan dari master barang satuan)
- [x] Master Karyawan (termasuk tautan akun login teknisi opsional) & Master Klien
- [x] Master Armada & Master Perusahaan
- [x] Upload foto aset / dokumen KTP (kompresi client + server, thumbnail)
- [x] User management (Owner): kelola akun & role

## Fase 2 — Operasional
- [x] Form Order (event, venue/ruangan/lantai, load-in/load-out, item sewa)
- [x] Form Penawaran/Quotation dari order (nomor unik, item, subtotal/diskon/pajak/total, status)
- [x] Surat Jalan: nomor, kaitan order, item otomatis dari Form Order, penugasan teknisi dari karyawan, pencarian
- [x] Field checklist teknisi: scan surat jalan, checkout/checkin, checklist kondisi, foto (kompresi + thumbnail), barang susulan
- [x] Riwayat foto per surat jalan (endpoint + tampilan admin, thumbnail/foto asli)
- [x] Aturan akses: tanpa penugasan teknisi → teknisi mana pun boleh; bila ditugaskan → terbatas ke yang ditugaskan/admin/owner
- [x] Mode offline checklist & sinkronisasi (incremental): antrean lokal & sinkronisasi manual checkout/checkin/barang susulan pada UI teknisi saat koneksi pulih; manifest + service worker cache shell dasar (belum background sync penuh)
- [x] Deteksi konflik jadwal crew/armada (tolak bentrok rentang waktu, abaikan status Dibatalkan)
- [x] Filter jadwal operasional (status, karyawan, armada, order, rentang waktu)
- [x] Dukungan penugasan alat (barang) pada jadwal operasional (API + relasi)
- [x] Penyempurnaan UI penjadwalan: filter, penugasan alat, dan tampilan terkelompok per tanggal (crew/armada/alat)

## Fase 3 — Keuangan & Laporan
- [x] Modul Invoice, transaksi keuangan, dan ringkasan/laporan (termasuk ekspor CSV transaksi) — sesuai implementasi saat ini
- [x] Invoice otomatis dibuat saat Surat Jalan berstatus `Selesai` (dari total item order, jika belum ada invoice untuk order)
- [x] Sinkronisasi & refresh status pembayaran invoice (Unpaid/DP/Paid/Overdue) dari transaksi, jatuh tempo, serta saat akses invoice/laporan
- [x] Ringkasan laporan keuangan: saldo/laba-rugi (pemasukan-pengeluaran), piutang berjalan, jumlah invoice overdue, rincian pemasukan/pengeluaran per kategori, dan laba-rugi per event/order
- [x] Ekspor CSV laporan: transaksi (`transactions.csv`, termasuk Order ID) & invoice (`invoices.csv`)
- [x] Ekspor CSV operasional: surat jalan (`surat-jalan.csv`) & jadwal operasional (`jadwal-operasional.csv`), kompatibel Excel (UTF-8 BOM)
- [x] Pelacakan biaya/laba-rugi per event: transaksi dapat dikaitkan ke `form_order` (otomatis dari invoice bila ada), ringkasan per event pada laporan
- [ ] Ekspor Excel/PDF operasional & keuangan lanjutan (format berkas .xlsx/.pdf; butuh persetujuan dependensi)

## Quality & Deployment
- [x] Feature test API (97 test, 522 assertion lulus per verifikasi terakhir): auth (login/me/logout), akses terproteksi & pembatasan role (teknisi vs admin/owner), validasi invoice/transaksi/jadwal/penawaran/form order/master data/absensi/surat jalan (termasuk validasi, unik nomor/kode, & hapus/update), finance/schedule, otorisasi & user management, delivery (scan/assignment, checkout/checkin, barang susulan), surat jalan (auto-item, item/teknisi manual, invoice otomatis saat selesai), form order create/update, kalkulasi & update penawaran, sinkronisasi & resync pembayaran saat hapus/update transaksi (termasuk pindah invoice, kaitan form_order), overdue (termasuk refresh), laporan (summary laba-rugi, rincian kategori & per event, CSV transaksi/invoice/surat jalan/jadwal; akses dibatasi admin/owner, teknisi ditolak), filter & penugasan/update/hapus/validasi alat jadwal, CRUD absensi, pembersihan berkas terkait (foto barang), audit log (pencatatan create/update/delete, filter & akses admin/owner). Rute duplikat surat-jalan/jadwal dibersihkan (admin/owner).
- [x] Template env produksi (`backend/.env.production.example`, `frontend/.env.production.example`), CORS env-driven
- [x] Script backup database (`scripts/dump-db.ps1`, `scripts/dump-db.sh`)
- [x] Panduan deploy & post-deploy checklist (`docs/DEPLOYMENT.md`)
- [x] Flowchart surat jalan & teknisi (`docs/flowchart-*.png`)
- [ ] Perluasan cakupan test (edge case modul lain lanjutan)
- [x] Deteksi & uji konflik penjadwalan crew/armada (tes API)
- [ ] Uji perangkat mobile & skenario offline
- [x] Audit log dasar: pencatatan create/update/delete model utama (`audit_logs`), endpoint & tampilan riwayat admin/owner (filter aksi/tipe objek, detail perubahan)
- [ ] Deploy produksi (menunggu hosting/infrastruktur) — ikuti `docs/DEPLOYMENT.md`

## Catatan / keputusan terbuka
- [ ] Konfirmasi owner: item paket benar-benar standalone (tidak bisa disewa satuan)
- [ ] Aset desain besar (`Design System Part 1.pdf`, `design system/`) di luar git — pertimbangkan Git LFS bila perlu
- [ ] (Opsional) Evaluasi static export Next.js untuk frontend sesuai `docs/DEPLOYMENT.md`
