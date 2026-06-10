# Buku Tamu Digital

Aplikasi buku tamu sederhana berbasis PHP, MySQL, dan Bootstrap lokal.

## Fitur

- Form input tamu dengan validasi PHP.
- Penyimpanan data ke database MySQL menggunakan `mysqli`.
- Halaman daftar tamu dengan tabel Bootstrap (`table`, `table-striped`, `table-hover`).
- Pencarian data berdasarkan nama atau instansi.
- Seluruh aset UI memakai file lokal agar tetap bisa dibuka tanpa koneksi internet.

## Struktur File

- `index.php` : halaman form input tamu.
- `daftar_tamu.php` : halaman daftar seluruh data tamu.
- `koneksi.php` : konfigurasi koneksi database MySQL.
- `database/db_bukutamu.sql` : script database dan contoh data awal.
- `assets/css/bootstrap.min.css` : Bootstrap lokal.
- `assets/js/bootstrap.bundle.min.js` : JavaScript Bootstrap lokal.
- `assets/images/guestbook-hero.svg` : ilustrasi lokal.

## Cara Menjalankan

1. Aktifkan Apache/PHP dan MySQL di XAMPP atau Laragon.
2. Buat database dengan mengimpor file `database/db_bukutamu.sql`.
3. Sesuaikan username/password MySQL di `koneksi.php` bila diperlukan.
4. Jalankan project ini dari web server lokal.
5. Buka `index.php` untuk form dan `daftar_tamu.php` untuk tabel data.
