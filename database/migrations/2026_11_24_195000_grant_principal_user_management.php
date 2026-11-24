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
            GRANT UPDATE (is_aktif, id_role, username, diubah_pada)
                ON TABLE public.pengguna TO role_kepsek;

            GRANT SELECT ON TABLE public.guru, public.staf TO role_kepsek;
            GRANT INSERT (id_pengguna, nip, nama_guru, no_telepon, is_aktif),
                  UPDATE (nip, nama_guru, no_telepon, is_aktif)
                ON TABLE public.guru TO role_kepsek;
            GRANT INSERT (id_pengguna, nama_staf, jabatan, no_telepon, is_aktif),
                  UPDATE (nama_staf, jabatan, no_telepon, is_aktif)
                ON TABLE public.staf TO role_kepsek;
            GRANT USAGE, SELECT ON SEQUENCE public.guru_id_guru_seq, public.staf_id_staf_seq TO role_kepsek;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            REVOKE UPDATE (is_aktif, id_role, username, diubah_pada)
                ON TABLE public.pengguna FROM role_kepsek;

            REVOKE SELECT ON TABLE public.guru, public.staf FROM role_kepsek;
            REVOKE INSERT (id_pengguna, nip, nama_guru, no_telepon, is_aktif),
                   UPDATE (nip, nama_guru, no_telepon, is_aktif)
                ON TABLE public.guru FROM role_kepsek;
            REVOKE INSERT (id_pengguna, nama_staf, jabatan, no_telepon, is_aktif),
                   UPDATE (nama_staf, jabatan, no_telepon, is_aktif)
                ON TABLE public.staf FROM role_kepsek;
            REVOKE USAGE, SELECT ON SEQUENCE public.guru_id_guru_seq, public.staf_id_staf_seq FROM role_kepsek;
            SQL);
    }
};
