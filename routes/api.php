<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\Akademik\KelasController;
use App\Http\Controllers\Api\Akademik\MataPelajaranController;
use App\Http\Controllers\Api\Akademik\PenilaianController;
use App\Http\Controllers\Api\Akademik\TahunAjaranController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PengajuanPerubahanController;
use App\Http\Controllers\Api\PpdbController;
use App\Http\Controllers\Api\RaporController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/ppdb', [PpdbController::class, 'store'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('permission:ppdb.verify')->group(function () {
        Route::get('/ppdb', [PpdbController::class, 'index']);
        Route::post('/ppdb/{id}/verifikasi', [PpdbController::class, 'verifikasi']);
    });

    Route::post('/pengajuan-perubahan', [PengajuanPerubahanController::class, 'store']);
    Route::post('/pengajuan-perubahan/{id}/keputusan', [PengajuanPerubahanController::class, 'keputusan'])
        ->middleware('permission:siswa.update.approve');
    Route::get('/absensi/rekap', [AbsensiController::class, 'rekap'])->middleware('permission:akademik.manage');
    Route::post('/rapor/nilai-akhir', [RaporController::class, 'nilaiAkhir'])->middleware('permission:akademik.manage');

    // ===== DFD 2.1 Konfigurasi Akademik (Tata Usaha) =====
    Route::prefix('akademik')->middleware('permission:akademik.manage')->group(function () {
        Route::apiResource('tahun-ajaran', TahunAjaranController::class)
            ->parameters(['tahun-ajaran' => 'tahunAjaran'])->except('show');
        Route::post('tahun-ajaran/{tahunAjaran}/semester', [TahunAjaranController::class, 'storeSemester']);
        Route::put('tahun-ajaran/{tahunAjaran}/semester/{semester}', [TahunAjaranController::class, 'updateSemester']);
        Route::delete('tahun-ajaran/{tahunAjaran}/semester/{semester}', [TahunAjaranController::class, 'destroySemester']);

        Route::apiResource('kelas', KelasController::class)->parameters(['kelas' => 'kelas'])->except('show');
        Route::apiResource('mata-pelajaran', MataPelajaranController::class)
            ->parameters(['mata-pelajaran' => 'mataPelajaran'])->except('show');

        Route::get('komponen-penilaian', [PenilaianController::class, 'indexKomponen']);
        Route::post('komponen-penilaian', [PenilaianController::class, 'storeKomponen']);
        Route::put('komponen-penilaian/{komponen}', [PenilaianController::class, 'updateKomponen']);
        Route::delete('komponen-penilaian/{komponen}', [PenilaianController::class, 'destroyKomponen']);

        Route::get('skala-predikat', [PenilaianController::class, 'indexPredikat']);
        Route::post('skala-predikat', [PenilaianController::class, 'storePredikat']);
        Route::put('skala-predikat/{predikat}', [PenilaianController::class, 'updatePredikat']);
        Route::delete('skala-predikat/{predikat}', [PenilaianController::class, 'destroyPredikat']);
    });
});