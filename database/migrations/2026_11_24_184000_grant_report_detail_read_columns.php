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
            'GRANT SELECT (id_detail_rapor, id_predikat) ON TABLE public.detail_rapor TO role_wali_kelas'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(
            'REVOKE SELECT (id_detail_rapor, id_predikat) ON TABLE public.detail_rapor FROM role_wali_kelas'
        );
    }
};
