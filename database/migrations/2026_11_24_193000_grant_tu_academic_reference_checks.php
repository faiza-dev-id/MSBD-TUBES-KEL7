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

        DB::unprepared(<<<'SQL'
            GRANT SELECT (id_semester) ON TABLE public.rapor TO role_tu;
            GRANT SELECT (id_mapel, id_predikat) ON TABLE public.detail_rapor TO role_tu;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            REVOKE SELECT (id_semester) ON TABLE public.rapor FROM role_tu;
            REVOKE SELECT (id_mapel, id_predikat) ON TABLE public.detail_rapor FROM role_tu;
            SQL);
    }
};
