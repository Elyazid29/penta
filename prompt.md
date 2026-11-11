Halo Agent, fokus utama kita sekarang adalah membuat form input Master Data (Owner View) agar Owner bisa langsung memasukkan data riil barang, karyawan, dan klien.

Tolong perbarui file form/modal di Next.js frontend dan pastikan terhubung ke API backend Laravel local (MySQL).

Gunakan style Corporate Minimalist White & Blue:
- Background: Light Gray/Slate (#F8FAFC)
- Card & Modal Background: Pure White (#FFFFFF) dengan border tipis (#E2E8F0) dan shadow-sm
- Button SIMPAN & CTA: Corporate Blue (#2563EB)

Buatkan modal form dengan struktur field 100% presisi sesuai wireframe:

1. Modal "Tambahkan Barang / Edit Data Barang" (Satuan):
   - Fields: Code (text), Nama Barang (text), Deskripsi (textarea), Kategori (select: Camera, Sound System, Lighting System, TV, LED Videotron, Streaming & Hybrid, Produksi), Jumlah (number), Harga (number), Upload Foto (file dropzone).
   - Action: Tombol SIMPAN.

2. Modal "Tambahkan Paket / Edit Paket":
   - Fields: Code (text), Kategori (select), Nama Paket (text), List Barang (dynamic multi-item row dengan tombol +), Harga (number).
   - Action: Tombol SIMPAN.

3. Modal "Tambahkan Data Karyawan / Edit Data Karyawan":
   - Fields: Nama (text), Jabatan (select: Teknisi, Leader, Driver), NIK (text), No Telp (text), No Rekening (text), Upload KTP (file dropzone), Status (select/radio: Aktif, Non-Aktif).
   - Action: Tombol SIMPAN.

4. Modal "Tambahkan Data Klien":
   - Fields: Nama Klien (text), Nama Perusahaan (text), Alamat (textarea), Contact Person 1 & No Telepon 1, Contact Person 2 & No Telepon 2, Contact Person 3 & No Telepon 3, NPWP (text), Kategori (select).
   - Action: Tombol SIMPAN.

Persyaratan Wajib:
- Semua form memiliki validasi input dasar (required fields).
- Kirim data via HTTP POST/PUT ke backend API Laravel dan tampilkan feedback toast/alert saat berhasil disimpan.
- Pastikan `npm run build` di frontend dan `php artisan test` di backend tidak menghasilkan error.