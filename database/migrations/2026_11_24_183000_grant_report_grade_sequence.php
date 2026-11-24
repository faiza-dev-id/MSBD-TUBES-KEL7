<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(
            'GRANT USAGE ON SEQUENCE public.nilai_siswa_id_nilai_seq TO role_wali_kelas'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(
            'REVOKE USAGE ON SEQUENCE public.nilai_siswa_id_nilai_seq FROM role_wali_kelas'
        );
    }
};
