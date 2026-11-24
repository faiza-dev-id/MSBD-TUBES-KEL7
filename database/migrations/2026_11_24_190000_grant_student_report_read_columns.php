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
            GRANT SELECT (
                id_rapor, id_anggota, id_semester, rata_rata, jumlah_sakit,
                jumlah_izin, jumlah_alpa, catatan_wali, status_rapor, tgl_dibuat
            ) ON TABLE public.rapor TO role_siswa;
            GRANT SELECT (id_rapor, id_mapel, id_predikat, nilai_akhir)
                ON TABLE public.detail_rapor TO role_siswa;
            GRANT SELECT (id_predikat, huruf) ON TABLE public.skala_predikat TO role_siswa;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            REVOKE SELECT (
                id_rapor, id_anggota, id_semester, rata_rata, jumlah_sakit,
                jumlah_izin, jumlah_alpa, catatan_wali, status_rapor, tgl_dibuat
            ) ON TABLE public.rapor FROM role_siswa;
            REVOKE SELECT (id_rapor, id_mapel, id_predikat, nilai_akhir)
                ON TABLE public.detail_rapor FROM role_siswa;
            REVOKE SELECT (id_predikat, huruf) ON TABLE public.skala_predikat FROM role_siswa;
            SQL);
    }
};
