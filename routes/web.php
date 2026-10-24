<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/masuk');

Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
Route::post('/masuk', [AuthController::class, 'login'])->name('login.proses');
Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

Route::get('/beranda', [PageController::class, 'beranda'])->name('beranda');

// ---- SEMUA ROLE ----
Route::middleware('role:siswa,guru,tu,kepsek,admin')->get('/akun/sandi', [PageController::class, 'sandi'])->name('akun.sandi');

// ---- SISWA ----
Route::middleware('role:siswa')->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/', [PageController::class, 'siswaDashboard'])->name('dashboard');
    Route::get('/ppdb', [PageController::class, 'ppdb'])->name('ppdb');
    Route::get('/status', [PageController::class, 'status'])->name('status');
    Route::get('/profil', [PageController::class, 'profil'])->name('profil');
    Route::get('/pengajuan-perubahan', [PageController::class, 'pengajuan'])->name('pengajuan');
});

// ---- GURU ----
Route::middleware('role:guru')->prefix('guru')->name('guru.')->group(function () {
    Route::get('/jadwal', [PageController::class, 'jadwalGuru'])->name('jadwal');
    Route::get('/kelas', [PageController::class, 'guruKelas'])->name('kelas');
    Route::get('/catatan', [PageController::class, 'catatan'])->name('catatan');
});

// ---- TU (admin juga boleh) ----
Route::middleware('role:tu,admin')->prefix('tu')->name('tu.')->group(function () {
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

Route::middleware('role:tu,admin')->prefix('akademik')->name('akademik.')->group(function () {
    Route::get('/konfigurasi/{tab?}', [PageController::class, 'konfigurasi'])->name('konfigurasi');
    Route::get('/jadwal', [PageController::class, 'jadwal'])->name('jadwal');
});

Route::middleware('role:kepsek,admin')->get('/kepsek', [PageController::class, 'kepsekDashboard'])->name('kepsek.dashboard');

// ---- ADMIN ----
Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/pengguna', [PageController::class, 'pengguna'])->name('pengguna');
    Route::get('/pengguna/tambah', [PageController::class, 'penggunaTambah'])->name('pengguna.tambah');
    Route::get('/staf', [PageController::class, 'staf'])->name('staf');
    Route::get('/peran', [PageController::class, 'peran'])->name('peran');
    Route::get('/referensi/{tab?}', [PageController::class, 'referensi'])->name('referensi');
    Route::get('/checklist', [PageController::class, 'checklistRef'])->name('checklist');
    Route::get('/aturan-field', [PageController::class, 'aturan'])->name('aturan');
    Route::get('/pengaturan', [PageController::class, 'pengaturan'])->name('pengaturan');
    Route::get('/log-login', [PageController::class, 'logLogin'])->name('log');
    Route::get('/audit', [PageController::class, 'audit'])->name('audit');
});