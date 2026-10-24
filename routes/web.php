<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/masuk');

Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
Route::post('/masuk', [AuthController::class, 'login'])->name('login.proses');
Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

// ---- PUBLIK: calon siswa (belum punya akun, DFD 3.1) ----
Route::get('/ppdb', [PageController::class, 'ppdb'])->name('ppdb');
Route::get('/ppdb/status', [PageController::class, 'status'])->name('ppdb.status');

Route::get('/beranda', [PageController::class, 'beranda'])->name('beranda');

// ---- GANTI SANDI (hanya role yang diizinkan di config/sekolah.php) ----
Route::middleware('role:' . implode(',', config('sekolah.role_ganti_sandi')))
    ->get('/akun/sandi', [PageController::class, 'sandi'])->name('akun.sandi');

// ---- SISWA ----
Route::middleware('role:siswa')->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/', [PageController::class, 'siswaDashboard'])->name('dashboard');
    Route::get('/profil', [PageController::class, 'profil'])->name('profil');
    Route::get('/pengajuan-perubahan', [PageController::class, 'pengajuan'])->name('pengajuan');
});

// ---- GURU / WALI KELAS ----
Route::middleware('role:guru')->prefix('guru')->name('guru.')->group(function () {
    Route::get('/jadwal', [PageController::class, 'jadwalGuru'])->name('jadwal');
    Route::get('/kelas', [PageController::class, 'guruKelas'])->name('kelas');
    Route::get('/kelengkapan', [PageController::class, 'guruKelengkapan'])->name('kelengkapan');
    Route::get('/catatan', [PageController::class, 'catatan'])->name('catatan');
});

// ---- TATA USAHA ----
Route::middleware('role:tu')->prefix('tu')->name('tu.')->group(function () {
    Route::get('/', [PageController::class, 'tuDashboard'])->name('dashboard');
    Route::get('/verifikasi/{id}', [PageController::class, 'verifikasi'])->name('verifikasi');
    Route::get('/persetujuan', [PageController::class, 'persetujuan'])->name('persetujuan');
    Route::get('/siswa', [PageController::class, 'dataSiswa'])->name('siswa');
    Route::get('/siswa/{id}', [PageController::class, 'detailSiswa'])->name('siswa.detail');
    Route::get('/periode', [PageController::class, 'periode'])->name('periode');
    Route::get('/kelas', [PageController::class, 'kelas'])->name('kelas');
    Route::get('/kelas/{id}', [PageController::class, 'kelasDetail'])->name('kelas.detail');
    Route::get('/penempatan', [PageController::class, 'penempatan'])->name('penempatan');
    Route::get('/kenaikan', [PageController::class, 'kenaikan'])->name('kenaikan');
});

// Data master (DFD L1-2, proses 2.5): dipegang TU. Nama route tetap "admin.*".
Route::middleware('role:tu')->prefix('tu/master')->name('admin.')->group(function () {
    Route::get('/referensi/{tab?}', [PageController::class, 'referensi'])->name('referensi');
    Route::get('/checklist', [PageController::class, 'checklistRef'])->name('checklist');
    Route::get('/aturan-field', [PageController::class, 'aturan'])->name('aturan');
});

Route::middleware('role:tu')->prefix('akademik')->name('akademik.')->group(function () {
    Route::get('/konfigurasi/{tab?}', [PageController::class, 'konfigurasi'])->name('konfigurasi');
    Route::get('/jadwal', [PageController::class, 'jadwal'])->name('jadwal');
});

// ---- KEPALA SEKOLAH (DFD L1-1: akun, peran, pengaturan, log). Nama route tetap "admin.*". ----
Route::middleware('role:kepsek')->group(function () {
    Route::get('/kepsek', [PageController::class, 'kepsekDashboard'])->name('kepsek.dashboard');

    Route::prefix('kepsek')->name('admin.')->group(function () {
        Route::get('/pengguna', [PageController::class, 'pengguna'])->name('pengguna');
        Route::get('/pengguna/tambah', [PageController::class, 'penggunaTambah'])->name('pengguna.tambah');
        Route::get('/staf', [PageController::class, 'staf'])->name('staf');
        Route::get('/peran', [PageController::class, 'peran'])->name('peran');
        Route::get('/pengaturan', [PageController::class, 'pengaturan'])->name('pengaturan');
        Route::get('/log-login', [PageController::class, 'logLogin'])->name('log');
        Route::get('/audit', [PageController::class, 'audit'])->name('audit');
    });
});