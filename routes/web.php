<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\PendaftaranPpdbController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\PengaturanController;

// =====================================================
// HALAMAN AWAL
// =====================================================

Route::redirect('/', '/masuk');

// =====================================================
// AUTENTIKASI WEB
// =====================================================

Route::get('/masuk', [PageController::class, 'masuk'])
    ->name('login');

Route::post('/masuk', [WebAuthController::class, 'login'])
    ->name('login.proses');

Route::post('/keluar', [WebAuthController::class, 'logout'])
    ->name('logout');

// =====================================================
// AUTENTIKASI API
// =====================================================

Route::post('/login', [AuthController::class, 'login']);

// =====================================================
// PPDB PUBLIK
// =====================================================

Route::get('/ppdb', [PageController::class, 'ppdb'])
    ->name('ppdb');

Route::post('/ppdb', [PendaftaranPpdbController::class, 'store'])
    ->name('ppdb.store');

Route::get('/ppdb/status', [PageController::class, 'status'])
    ->name('ppdb.status');

// =====================================================
// BERANDA
// =====================================================

Route::get('/beranda', [PageController::class, 'beranda'])
    ->name('beranda');

// =====================================================
// GANTI SANDI
// =====================================================

Route::middleware(
    'role:' . implode(',', config('sekolah.role_ganti_sandi', []))
)->get('/akun/sandi', [PageController::class, 'sandi'])
    ->name('akun.sandi');

// =====================================================
// SISWA
// =====================================================

Route::prefix('siswa')
    ->name('siswa.')
    ->middleware('role:siswa')
    ->group(function () {
        Route::get('/', [PageController::class, 'siswaDashboard'])
            ->name('dashboard');

        Route::get('/profil', [PageController::class, 'profil'])
            ->name('profil');

        Route::get('/pengajuan-perubahan', [PageController::class, 'pengajuan'])
            ->name('pengajuan');
    });

// =====================================================
// GURU / WALI KELAS
// =====================================================

Route::prefix('guru')
    ->name('guru.')
    ->middleware('role:guru')
    ->group(function () {
        Route::get('/jadwal', [PageController::class, 'jadwalGuru'])
            ->name('jadwal');

        Route::get('/kelas', [PageController::class, 'guruKelas'])
            ->name('kelas');

        Route::get('/kelengkapan', [PageController::class, 'guruKelengkapan'])
            ->name('kelengkapan');

        Route::get('/catatan', [PageController::class, 'catatan'])
            ->name('catatan');
    });

// =====================================================
// TATA USAHA (TU)
// =====================================================

Route::prefix('tu')
    ->name('tu.')
    ->middleware('role:tu')
    ->group(function () {
        Route::get('/', [PageController::class, 'tuDashboard'])
            ->name('dashboard');

        Route::get('/verifikasi/{id}', [PageController::class, 'verifikasi'])
            ->name('verifikasi');

        Route::get('/persetujuan', [PageController::class, 'persetujuan'])
            ->name('persetujuan');

        // Data induk siswa. Nama "siswa" dan "siswa.detail" dipakai menu sidebar,
        // dashboard TU, dan view tu/siswa, jangan diganti tanpa mengubah view.
        Route::get('/siswa', [SiswaController::class, 'index'])
            ->name('siswa');

        Route::get('/siswa/{id}', [SiswaController::class, 'show'])
            ->name('siswa.detail');

        Route::post('/siswa', [SiswaController::class, 'store'])
            ->name('siswa.store');

        Route::put('/siswa/{id}', [SiswaController::class, 'update'])
            ->name('siswa.update');

        Route::delete('/siswa/{id}', [SiswaController::class, 'destroy'])
            ->name('siswa.destroy');

        Route::get('/periode', [PageController::class, 'periode'])
            ->name('periode');

        Route::get('/kelas', [PageController::class, 'kelas'])
            ->name('kelas');

        Route::get('/kelas/{id}', [PageController::class, 'kelasDetail'])
            ->name('kelas.detail');

        Route::get('/penempatan', [PageController::class, 'penempatan'])
            ->name('penempatan');

        Route::get('/kenaikan', [PageController::class, 'kenaikan'])
            ->name('kenaikan');

        Route::get('/reset-sandi', [PageController::class, 'resetSandi'])
            ->name('reset');
    });

// =====================================================
// DATA MASTER
// =====================================================

Route::prefix('tu/master')
    ->name('admin.')
    ->middleware('role:tu')
    ->group(function () {
        Route::get('/referensi/{tab?}', [PageController::class, 'referensi'])
            ->name('referensi');

        Route::get('/checklist', [PageController::class, 'checklistRef'])
            ->name('checklist');

        Route::get('/aturan-field', [PageController::class, 'aturan'])
            ->name('aturan');
    });

// =====================================================
// AKADEMIK
// =====================================================

Route::prefix('akademik')
    ->name('akademik.')
    ->middleware('role:tu')
    ->group(function () {
        Route::get('/konfigurasi/{tab?}', [PageController::class, 'konfigurasi'])
            ->name('konfigurasi');

        Route::get('/jadwal', [PageController::class, 'jadwal'])
            ->name('jadwal');
    });

// =====================================================
// KEPALA SEKOLAH
// =====================================================

Route::prefix('kepsek')
    ->middleware('role:kepsek')
    ->group(function () {
        Route::get('/', [PageController::class, 'kepsekDashboard'])
            ->name('kepsek.dashboard');

        Route::prefix('pengguna')
            ->name('admin.')
            ->group(function () {
                Route::get('/', [PageController::class, 'pengguna'])
                    ->name('pengguna');

                Route::get('/tambah', [PageController::class, 'penggunaTambah'])
                    ->name('pengguna.tambah');
            });

        Route::get('/staf', [PageController::class, 'staf'])
            ->name('admin.staf');

        Route::get('/peran', [PageController::class, 'peran'])
            ->name('admin.peran');

        Route::get('/pengaturan', [PengaturanController::class, 'index'])
            ->name('admin.pengaturan');

        Route::put('/pengaturan', [PengaturanController::class, 'update'])
            ->name('admin.pengaturan.simpan');

        Route::get('/log-login', [LogController::class, 'login'])
            ->name('admin.log');

        Route::get('/audit', [LogController::class, 'audit'])
            ->name('admin.audit');
    });
