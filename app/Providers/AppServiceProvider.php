<?php

namespace App\Providers;

use App\Models\Kelas;
use App\Models\KomponenPenilaian;
use App\Models\MataPelajaran;
use App\Models\PengaturanSistem;
use App\Models\Semester;
use App\Models\SkalaPredikat;
use App\Models\TahunAjaran;
use App\Observers\AuditObserver;
use App\Services\PengaturanService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Trigger DB hanya ada di siswa/pengguna/pengajuan_perubahan; tabel lain memakai observer.
        foreach ([TahunAjaran::class, Semester::class, Kelas::class, MataPelajaran::class,
                  KomponenPenilaian::class, SkalaPredikat::class, PengaturanSistem::class] as $model) {
            $model::observe(AuditObserver::class);
        }

        // Nama sekolah dari pengaturan (header & judul halaman memakai config('sekolah.nama')).
        if (!$this->app->runningInConsole()) {
            try {
                $nama = $this->app->make(PengaturanService::class)->nilai('nama_sekolah');
                if ($nama) config(['sekolah.nama' => $nama]);
            } catch (\Throwable $e) {
                // DB/cache belum siap: pakai nilai di config/sekolah.php
            }
        }
    }
}
