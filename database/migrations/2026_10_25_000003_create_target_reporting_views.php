<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $views = [
        'vw_rekap_absensi_siswa',
        'vw_nilai_rata_siswa',
        'vw_rapor_siap_cetak',
        'vw_kelengkapan_data_kelas',
    ];

    public function up(): void
    {
        $required = [
            'sesi_absensi',
            'detail_absensi',
            'status_kehadiran',
            'nilai_siswa',
            'komponen_penilaian',
            'skala_predikat',
            'rapor',
            'detail_rapor',
            'cetak_rapor',
        ];

        foreach ($required as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException(
                    "Tabel '{$table}' belum ada. Pastikan migration 0002 berhasil."
                );
            }
        }

        foreach ($this->views as $view) {
            $path = database_path("sql/{$view}.sql");

            if (!is_file($path)) {
                throw new RuntimeException(
                    "File SQL view tidak ditemukan: {$path}"
                );
            }

            DB::unprepared(file_get_contents($path));
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->views) as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }
    }
};
