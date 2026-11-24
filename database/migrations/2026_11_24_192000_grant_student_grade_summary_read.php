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
            GRANT SELECT (id_komponen, bobot_persen)
                ON TABLE public.komponen_penilaian TO role_siswa;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            REVOKE SELECT (id_komponen, bobot_persen)
                ON TABLE public.komponen_penilaian FROM role_siswa;
            SQL);
    }
};
