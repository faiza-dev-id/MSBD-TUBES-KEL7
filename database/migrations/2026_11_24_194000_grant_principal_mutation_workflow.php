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
                id_mutasi, id_siswa, id_pengaju, id_penyetuju, jenis, alasan, status,
                tgl_pengajuan, tgl_keputusan, tanggal_efektif, catatan_keputusan,
                path_lampiran, nama_lampiran, nisn_baru, nama_siswa_baru, nama_sekolah_tujuan
            ) ON TABLE public.mutasi_siswa TO role_kepsek;
            GRANT UPDATE (status, id_penyetuju, tgl_keputusan, catatan_keputusan)
                ON TABLE public.mutasi_siswa TO role_kepsek;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            REVOKE SELECT (
                id_mutasi, id_siswa, id_pengaju, id_penyetuju, jenis, alasan, status,
                tgl_pengajuan, tgl_keputusan, tanggal_efektif, catatan_keputusan,
                path_lampiran, nama_lampiran, nisn_baru, nama_siswa_baru, nama_sekolah_tujuan
            ) ON TABLE public.mutasi_siswa FROM role_kepsek;
            REVOKE UPDATE (status, id_penyetuju, tgl_keputusan, catatan_keputusan)
                ON TABLE public.mutasi_siswa FROM role_kepsek;
            SQL);
    }
};
