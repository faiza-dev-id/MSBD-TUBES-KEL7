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
            GRANT SELECT ON TABLE public.vw_nilai_rata_siswa TO role_siswa;
            GRANT SELECT ON TABLE public.vw_rekap_absensi_siswa TO role_siswa;
            GRANT SELECT ON TABLE public.vw_statistik_ppdb TO role_tu;
            GRANT EXECUTE ON FUNCTION public.fn_semester_aktif() TO
                role_siswa, role_wali_kelas, role_tu;
            GRANT EXECUTE ON FUNCTION public.fn_persen_kehadiran(bigint) TO role_siswa;
            GRANT EXECUTE ON FUNCTION public.fn_persen_konfirmasi_periode(bigint) TO role_tu;
            GRANT USAGE ON SEQUENCE public.rapor_id_rapor_seq,
                public.detail_rapor_id_detail_rapor_seq TO role_wali_kelas;
            GRANT EXECUTE ON PROCEDURE public.sp_finalisasi_rapor(bigint, character varying)
                TO role_wali_kelas;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            REVOKE SELECT ON TABLE public.vw_nilai_rata_siswa FROM role_siswa;
            REVOKE SELECT ON TABLE public.vw_rekap_absensi_siswa FROM role_siswa;
            REVOKE SELECT ON TABLE public.vw_statistik_ppdb FROM role_tu;
            REVOKE EXECUTE ON FUNCTION public.fn_semester_aktif() FROM
                role_siswa, role_wali_kelas, role_tu;
            REVOKE EXECUTE ON FUNCTION public.fn_persen_kehadiran(bigint) FROM role_siswa;
            REVOKE EXECUTE ON FUNCTION public.fn_persen_konfirmasi_periode(bigint) FROM role_tu;
            REVOKE USAGE ON SEQUENCE public.rapor_id_rapor_seq,
                public.detail_rapor_id_detail_rapor_seq FROM role_wali_kelas;
            REVOKE EXECUTE ON PROCEDURE public.sp_finalisasi_rapor(bigint, character varying)
                FROM role_wali_kelas;
            SQL);
    }
};
