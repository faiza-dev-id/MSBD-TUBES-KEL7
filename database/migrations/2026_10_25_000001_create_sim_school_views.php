
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $views = [
            'vw_antrean_verifikasi' => [
                'file' => 'vw_antrean_verifikasi.sql',
                'tables' => ['pendaftaran_ppdb'],
            ],
            'vw_dashboard_kepsek' => [
                'file' => 'vw_dashboard_kepsek.sql',
                'tables' => [
                    'siswa',
                    'status_siswa',
                    'pendaftaran_ppdb',
                    'pengajuan_perubahan',
                ],
            ],
        ];

        foreach ($views as $view) {
            foreach ($view['tables'] as $table) {
                if (!Schema::hasTable($table)) {
                    continue 2;
                }
            }

            $path = database_path('sql/' . $view['file']);

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
        DB::statement('DROP VIEW IF EXISTS vw_dashboard_kepsek');
        DB::statement('DROP VIEW IF EXISTS vw_antrean_verifikasi');
    }
};
