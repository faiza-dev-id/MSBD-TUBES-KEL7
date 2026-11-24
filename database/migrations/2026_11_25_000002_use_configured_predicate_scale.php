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
            CREATE OR REPLACE FUNCTION public.fn_predikat_nilai(p_nilai numeric)
            RETURNS character varying
            LANGUAGE sql
            STABLE
            AS $function$
                SELECT sp.huruf
                FROM public.skala_predikat AS sp
                WHERE p_nilai BETWEEN sp.nilai_min AND sp.nilai_maks
                ORDER BY sp.nilai_min
                LIMIT 1
            $function$;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.fn_predikat_nilai(p_nilai numeric)
            RETURNS character varying
            LANGUAGE sql
            STABLE
            AS $function$
                SELECT CASE
                    WHEN p_nilai >= 85 THEN 'A'
                    WHEN p_nilai >= 75 THEN 'B'
                    WHEN p_nilai >= 65 THEN 'C'
                    WHEN p_nilai >= 55 THEN 'D'
                    ELSE 'E'
                END
            $function$;
            SQL);
    }
};
