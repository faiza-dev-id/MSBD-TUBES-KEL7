# MSBD-TUBES-KEL7

Project Tugas Besar Mata Kuliah Manajemen Sistem Basis Data (MSBD), dikembangkan menggunakan Laravel dan PostgreSQL.

## Teknologi yang Digunakan

- **Framework:** Laravel
- **Bahasa pemrograman:** PHP
- **Database:** PostgreSQL
- **Frontend build tool:** Vite
- **Version control:** Git dan GitHub

## Persiapan Project

1. Pastikan PHP, Composer, Node.js, npm, dan PostgreSQL sudah terpasang.
2. Clone repository ini atau buka folder project Laravel.
3. Instal dependensi PHP:

   ```bash
   composer install
   ```

4. Salin `.env.example` menjadi `.env`:

   ```bash
   copy .env.example .env
   ```

5. Buat application key:

   ```bash
   php artisan key:generate
   ```

6. Sesuaikan konfigurasi PostgreSQL di file `.env`:

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=msbd_tubes_kel7
   DB_USERNAME=postgres
   DB_PASSWORD=password_postgresql_kamu
   ```

   Ganti nama database, username, dan password sesuai konfigurasi lokal.

7. Instal dependensi frontend dan build aset:

   ```bash
   npm install
   npm run build
   ```

8. Jalankan migrasi setelah database PostgreSQL dibuat dan koneksi berhasil:

   ```bash
   php artisan migrate
   ```

9. Jalankan aplikasi:

   ```bash
   php artisan serve
   ```

## Dokumentasi Laravel

Dokumentasi resmi Laravel tersedia di https://laravel.com/docs.

Project ini dikembangkan secara kolaboratif menggunakan Git dan GitHub.
