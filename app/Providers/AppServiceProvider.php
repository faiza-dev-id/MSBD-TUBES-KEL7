<?php

namespace App\Providers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\KomponenPenilaian;
use App\Models\MataPelajaran;
use App\Models\PengaturanSistem;
use App\Models\Pengguna;
use App\Models\Semester;
use App\Models\SkalaPredikat;
use App\Models\Siswa;
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
        // Observer untuk pengguna, guru, dan siswa.
        foreach ([
            Pengguna::class,
            Guru::class,
            Siswa::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }

        // Observer untuk model akademik dan pengaturan sistem.
        foreach ([
            TahunAjaran::class,
            Semester::class,
            Kelas::class,
            MataPelajaran::class,
            KomponenPenilaian::class,
            SkalaPredikat::class,
            PengaturanSistem::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }

        // Nama sekolah untuk header dan judul halaman.
        if (! $this->app->runningInConsole()) {
            try {
                $nama = $this->app
                    ->make(PengaturanService::class)
                    ->nilai('nama_sekolah');

                if ($nama) {
                    config(['sekolah.nama' => $nama]);
                }
            } catch (\Throwable $e) {
                // Jika DB/cache belum siap, gunakan konfigurasi default.
            }
        }
    }
}
