<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AksiFormController;
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

Route::post('/ppdb/status/berkas', [AksiFormController::class, 'berkasUlang'])
    ->name('ppdb.status.berkas');

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

        Route::post('/konfirmasi', [AksiFormController::class, 'konfirmasiSiswa'])
            ->name('konfirmasi.simpan');

        Route::get('/pengajuan-perubahan', [PageController::class, 'pengajuan'])
            ->name('pengajuan');

        Route::post('/pengajuan-perubahan', [AksiFormController::class, 'pengajuanSiswa'])
            ->name('pengajuan.simpan');

        Route::get('/jadwal', [PageController::class, 'jadwalSiswa'])
            ->name('jadwal');

        Route::get('/kehadiran', [PageController::class, 'absensiSiswa'])
            ->name('absensi');

        Route::get('/rapor', [PageController::class, 'raporSiswa'])
            ->name('rapor');
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

        Route::post('/catatan', [AksiFormController::class, 'catatanSimpan'])
            ->name('catatan.simpan');

        Route::get('/absensi', [PageController::class, 'guruAbsensi'])
            ->name('absensi');

        Route::post('/absensi', [AksiFormController::class, 'absensiSimpan'])
            ->name('absensi.simpan');

        Route::get('/rapor', [PageController::class, 'guruRapor'])
            ->name('rapor');

        Route::post('/rapor', [AksiFormController::class, 'raporSimpan'])
            ->name('rapor.simpan');
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

        Route::post('/pengingat', [AksiFormController::class, 'pengingat'])
            ->name('pengingat');

        Route::get('/antrean', [PageController::class, 'antrean'])
            ->name('antrean');

        Route::get('/verifikasi/{id}', [PageController::class, 'verifikasi'])
            ->name('verifikasi');

        Route::post('/verifikasi', [AksiFormController::class, 'verifikasiSimpan'])
            ->name('verifikasi.simpan');

        Route::get('/persetujuan', [PageController::class, 'persetujuan'])
            ->name('persetujuan');

        Route::post('/persetujuan', [AksiFormController::class, 'persetujuanSimpan'])
            ->name('persetujuan.simpan');

        // Data induk siswa. Nama "siswa" dan "siswa.detail" dipakai menu sidebar,
        // dashboard TU, dan view tu/siswa, jangan diganti tanpa mengubah view.

        Route::get('/siswa', [SiswaController::class, 'index'])
            ->name('siswa');

        Route::get('/siswa/{id}', [SiswaController::class, 'show'])
            ->name('siswa.detail');

        Route::get('/siswa/{id}/dokumen', [PageController::class, 'dokumenSiswa'])
            ->name('siswa.dokumen');

        Route::post('/siswa/{id}/dokumen', [AksiFormController::class, 'dokumenSimpan'])
            ->whereNumber('id')->name('siswa.dokumen.simpan');

        Route::patch('/siswa/{id}/dokumen/{idDokumen}', [AksiFormController::class, 'dokumenNonaktif'])
            ->whereNumber('id')->whereNumber('idDokumen')->name('siswa.dokumen.nonaktif');

        Route::post('/siswa', [SiswaController::class, 'store'])
            ->name('siswa.store');

        Route::put('/siswa/{id}', [SiswaController::class, 'update'])
            ->name('siswa.update');

        Route::delete('/siswa/{id}', [SiswaController::class, 'destroy'])
            ->name('siswa.destroy');

        Route::get('/periode', [PageController::class, 'periode'])
            ->name('periode');

        Route::post('/periode', [AksiFormController::class, 'periodeSimpan'])
            ->name('periode.simpan');

        Route::post('/periode/{id}/tutup', [AksiFormController::class, 'periodeTutup'])
            ->whereNumber('id')->name('periode.tutup');

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

        Route::post('/penempatan', [AksiFormController::class, 'penempatanSimpan'])
            ->name('penempatan.simpan');

        Route::get('/kenaikan', [PageController::class, 'kenaikan'])
            ->name('kenaikan');

        Route::post('/kenaikan', [AksiFormController::class, 'kenaikanSimpan'])
            ->name('kenaikan.simpan');

        Route::get('/reset-sandi', [ResetSandiController::class, 'index'])
            ->name('reset');

        Route::post('/reset-sandi/{id}', [ResetSandiController::class, 'reset'])
            ->middleware('throttle:30,1')
            ->name('reset.proses');

        Route::get('/mutasi', [PageController::class, 'mutasiTu'])
            ->name('mutasi');

        Route::post('/mutasi', [AksiFormController::class, 'mutasiSimpan'])
            ->name('mutasi.simpan');

        Route::post('/mutasi/{id}/keputusan', [AksiFormController::class, 'mutasiPutuskan'])
            ->whereNumber('id')
            ->name('mutasi.putuskan');
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

        Route::get('/mutasi', [PageController::class, 'mutasiKepsek'])
            ->name('kepsek.mutasi');

        Route::post('/mutasi', [AksiFormController::class, 'mutasiSimpan'])
            ->name('kepsek.mutasi.simpan');

        Route::post('/mutasi/{id}/keputusan', [AksiFormController::class, 'mutasiPutuskan'])
            ->whereNumber('id')
            ->name('kepsek.mutasi.putuskan');
    });
