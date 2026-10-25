<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void {
 if(Schema::hasTable('pendaftaran_ppdb')) DB::unprepared(file_get_contents(database_path('sql/views/vw_antrean_verifikasi.sql')));
 if(Schema::hasTable('siswa') && Schema::hasTable('status_siswa') && Schema::hasTable('pendaftaran_ppdb') && Schema::hasTable('pengajuan_perubahan')) DB::unprepared(file_get_contents(database_path('sql/views/vw_dashboard_kepsek.sql')));
 // Remaining four views are intentionally not installed until target tables/semantics are approved.
 } public function down():void { foreach(['vw_antrean_verifikasi','vw_dashboard_kepsek'] as $v) DB::statement('DROP VIEW IF EXISTS '.$v); } };
