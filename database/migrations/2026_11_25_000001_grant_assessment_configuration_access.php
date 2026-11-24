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
            GRANT USAGE ON SEQUENCE
                public.komponen_penilaian_id_komponen_seq,
                public.skala_predikat_id_predikat_seq
                TO role_tu;
            GRANT SELECT (id_predikat) ON TABLE public.detail_rapor TO role_tu;
            GRANT SELECT ON TABLE public.komponen_penilaian, public.skala_predikat
                TO role_wali_kelas;
            GRANT SELECT (nilai_min, nilai_maks)
                ON TABLE public.skala_predikat TO role_siswa;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            REVOKE USAGE ON SEQUENCE
                public.komponen_penilaian_id_komponen_seq,
                public.skala_predikat_id_predikat_seq
                FROM role_tu;
            REVOKE SELECT (id_predikat) ON TABLE public.detail_rapor FROM role_tu;
            REVOKE SELECT ON TABLE public.komponen_penilaian, public.skala_predikat
                FROM role_wali_kelas;
            REVOKE SELECT (nilai_min, nilai_maks)
                ON TABLE public.skala_predikat FROM role_siswa;
            SQL);
    }
};
