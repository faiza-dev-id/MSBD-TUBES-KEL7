# Catatan Perbaikan Backend - Audit Tahap 1

Perubahan pada paket ini:

1. Mendaftarkan `routes/api.php` di `bootstrap/app.php`.
2. Mendaftarkan alias middleware `permission` ke `CheckPermission`.
3. Mengarahkan provider autentikasi Laravel ke model `App\\Models\\Pengguna`.
4. Menambahkan Form Request yang dirujuk controller API: `StorePpdbRequest`, `VerifyPpdbRequest`, dan `StorePengajuanPerubahanRequest`.
5. Menyamakan key data daftar siswa dengan yang dibaca oleh Blade, memperbaiki route detail siswa, menambahkan form pencarian dan pagination.
6. Mengganti tampilan detail siswa agar menggunakan data yang benar-benar dikirim oleh `SiswaController` dan struktur kolom dari skema SQL proyek.
7. Mengubah link dashboard ke route bernama `tu.siswa.index`.

## Hasil verifikasi yang dilakukan

- `php artisan route:list --except-vendor` berhasil dan menampilkan 53 route termasuk API.
- `php -l` berhasil untuk file PHP yang diubah.
- `php artisan test` dan `php artisan view:cache` belum dapat diselesaikan di lingkungan audit karena ekstensi PHP DOM (`DOMDocument`) tidak tersedia. Ini bukan bukti bahwa test suite lulus.

## Langkah setelah menyalin perubahan ke proyek lokal

1. Pastikan ekstensi PHP `dom` aktif pada PHP yang dipakai Laravel.
2. Sesuaikan `.env` lokal dengan koneksi PostgreSQL yang sudah berhasil kamu gunakan. Jangan menimpa `.env` dengan file contoh.
3. Jalankan `php artisan optimize:clear`.
4. Jalankan `php artisan route:list` dan pastikan endpoint `api/*` muncul.
5. Uji login dan CRUD siswa di browser, lalu lanjut uji PPDB/pengajuan perubahan.

Tidak ada perintah penghapusan database atau `migrate:fresh` yang dijalankan.
