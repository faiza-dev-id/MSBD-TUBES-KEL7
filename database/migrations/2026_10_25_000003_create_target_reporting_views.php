<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  $required = ['sesi_absensi','detail_absensi','status_kehadiran','nilai_siswa','komponen_penilaian','skala_predikat','rapor','detail_rapor','cetak_rapor'];
  foreach ($required as $table) if (!Schema::hasTable($table)) throw new RuntimeException("Missing target table {$table}; target extension migration must run first.");
  foreach (['vw_rekap_absensi_siswa','vw_nilai_rata_siswa','vw_rapor_siap_cetak','vw_kelengkapan_data_kelas'] as $view) DB::unprepared(file_get_contents(database_path("sql/views/{$view}.sql")));
 }
 public function down(): void { foreach (['vw_rekap_absensi_siswa','vw_nilai_rata_siswa','vw_rapor_siap_cetak','vw_kelengkapan_data_kelas'] as $view) DB::statement("DROP VIEW IF EXISTS {$view}"); }
};
