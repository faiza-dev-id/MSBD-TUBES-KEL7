<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\PendaftaranPpdbController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PeranController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Tu\ResetSandiController;
use App\Http\Controllers\Tu\ReferensiController;
use App\Http\Controllers\Tu\ChecklistController;
use App\Http\Controllers\Tu\AturanFieldController;
use App\Http\Controllers\Tu\KonfigurasiController;
use App\Http\Controllers\Tu\KelasController;

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

Route::middleware(
    'role:' . implode(',', config('sekolah.role_ganti_sandi', []))
)->post('/akun/sandi', [WebAuthController::class, 'changePassword'])
    ->name('akun.sandi.update');


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

        Route::get('/konfirmasi', [PageController::class, 'konfirmasi'])
                ->name('konfirmasi');

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

        Route::get('/antrean', [PageController::class, 'antrean'])
            ->name('antrean');

        Route::get('/verifikasi/{id}', [PageController::class, 'verifikasi'])
            ->name('verifikasi');

        Route::get('/kelulusan', [PageController::class, 'kelulusan'])
            ->name('kelulusan');

        Route::get('/persetujuan', [PageController::class, 'persetujuan'])
            ->name('persetujuan');

        // Data induk siswa. Nama "siswa" dan "siswa.detail" dipakai menu sidebar,
        // dashboard TU, dan view tu/siswa, jangan diganti tanpa mengubah view.

        Route::get('/siswa', [SiswaController::class, 'index'])
            ->name('siswa');

        Route::get('/siswa/{id}', [SiswaController::class, 'show'])
            ->name('siswa.detail');

        Route::get('/siswa/{id}/dokumen', [PageController::class, 'dokumenSiswa'])
            ->name('siswa.dokumen');

        Route::post('/siswa', [SiswaController::class, 'store'])
            ->name('siswa.store');

        Route::put('/siswa/{id}', [SiswaController::class, 'update'])
            ->name('siswa.update');

        Route::delete('/siswa/{id}', [SiswaController::class, 'destroy'])
            ->name('siswa.destroy');

        Route::get('/periode', [PageController::class, 'periode'])
            ->name('periode');

        Route::get('/kelas', [KelasController::class, 'index'])
            ->name('kelas');

        Route::post('/kelas/wali', [KelasController::class, 'tetapkan'])
            ->name('kelas.wali');

        Route::delete('/kelas/wali/{id}', [KelasController::class, 'cabut'])
            ->whereNumber('id')->name('kelas.wali.cabut');

        Route::get('/kelas/{id}', [KelasController::class, 'detail'])
            ->whereNumber('id')->name('kelas.detail');

        Route::get('/kelas/{id}/unduh', [KelasController::class, 'unduh'])
            ->whereNumber('id')->name('kelas.unduh');

        Route::get('/penempatan', [PageController::class, 'penempatan'])
            ->name('penempatan');

        Route::get('/kenaikan', [PageController::class, 'kenaikan'])
            ->name('kenaikan');

        Route::get('/reset-sandi', [ResetSandiController::class, 'index'])
            ->name('reset');

        Route::post('/reset-sandi/{id}', [ResetSandiController::class, 'reset'])
            ->middleware('throttle:30,1')
            ->name('reset.proses');
    });

// =====================================================
// DATA MASTER
// =====================================================

Route::prefix('tu/master')
    ->name('admin.')
    ->middleware('role:tu')
    ->group(function () {
        // 2.5 Data referensi
        Route::get('/referensi/{tab?}', [ReferensiController::class, 'index'])
            ->name('referensi');
        Route::post('/referensi/{tab}', [ReferensiController::class, 'store'])
            ->name('referensi.simpan');
        Route::put('/referensi/{tab}/{id}', [ReferensiController::class, 'update'])
            ->whereNumber('id')->name('referensi.ubah');
        Route::delete('/referensi/{tab}/{id}', [ReferensiController::class, 'destroy'])
            ->whereNumber('id')->name('referensi.hapus');

        // Checklist verifikasi dan alasan pengembalian
        Route::get('/checklist', [ChecklistController::class, 'index'])
            ->name('checklist');
        Route::post('/checklist/item', [ChecklistController::class, 'simpanItem'])
            ->name('checklist.item.simpan');
        Route::put('/checklist/item/{id}', [ChecklistController::class, 'ubahItem'])
            ->whereNumber('id')->name('checklist.item.ubah');
        Route::delete('/checklist/item/{id}', [ChecklistController::class, 'hapusItem'])
            ->whereNumber('id')->name('checklist.item.hapus');
        Route::post('/checklist/alasan', [ChecklistController::class, 'simpanAlasan'])
            ->name('checklist.alasan.simpan');
        Route::put('/checklist/alasan/{id}', [ChecklistController::class, 'ubahAlasan'])
            ->whereNumber('id')->name('checklist.alasan.ubah');
        Route::delete('/checklist/alasan/{id}', [ChecklistController::class, 'hapusAlasan'])
            ->whereNumber('id')->name('checklist.alasan.hapus');

        // Aturan perubahan data siswa
        Route::get('/aturan-field', [AturanFieldController::class, 'index'])
            ->name('aturan');
        Route::put('/aturan-field', [AturanFieldController::class, 'update'])
            ->name('aturan.simpan');
    });

// =====================================================
// AKADEMIK
// =====================================================

Route::prefix('akademik')
    ->name('akademik.')
    ->middleware('role:tu')
    ->group(function () {
        Route::get('/konfigurasi/{tab?}', [KonfigurasiController::class, 'index'])
            ->name('konfigurasi');
        Route::post('/konfigurasi/{tab}', [KonfigurasiController::class, 'store'])
            ->name('konfigurasi.simpan');
        Route::put('/konfigurasi/{tab}/{id}', [KonfigurasiController::class, 'update'])
            ->whereNumber('id')->name('konfigurasi.ubah');
        Route::delete('/konfigurasi/{tab}/{id}', [KonfigurasiController::class, 'destroy'])
            ->whereNumber('id')->name('konfigurasi.hapus');

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

                Route::post('/simpan', [PageController::class, 'penggunaSimpan'])
                ->name('pengguna.simpan');

                Route::get('/{id}/edit', [PageController::class, 'penggunaEdit'])
                 ->name('pengguna.edit');

                Route::put('/{id}', [PenggunaController::class, 'update'])
                ->name('pengguna.update');

                Route::patch('/{id}/status', [PenggunaController::class, 'ubahStatus'])
                ->name('pengguna.status');
            });

        Route::get('/staf', [PenggunaController::class, 'staf'])
            ->name('admin.staf');

        Route::get('/peran', [PeranController::class, 'index'])
            ->name('admin.peran');

        Route::put('/peran', [PeranController::class, 'update'])
            ->name('admin.peran.simpan');

        Route::get('/pengaturan', [PengaturanController::class, 'index'])
            ->name('admin.pengaturan');

        Route::put('/pengaturan', [PengaturanController::class, 'update'])
            ->name('admin.pengaturan.simpan');

        Route::get('/log-login', [LogController::class, 'login'])
            ->name('admin.log');

        Route::get('/audit', [LogController::class, 'audit'])
            ->name('admin.audit');
    });
