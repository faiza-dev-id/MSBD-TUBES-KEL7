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
            GRANT SELECT ON TABLE public.pengaturan_sistem TO role_kepsek;
            GRANT INSERT (kunci, nilai, deskripsi, id_pengguna_pengubah, tgl_diubah)
                ON TABLE public.pengaturan_sistem TO role_kepsek;
            GRANT UPDATE (nilai, deskripsi, id_pengguna_pengubah, tgl_diubah)
                ON TABLE public.pengaturan_sistem TO role_kepsek;
            GRANT USAGE, SELECT ON SEQUENCE public.pengaturan_sistem_id_pengaturan_seq TO role_kepsek;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            REVOKE SELECT ON TABLE public.pengaturan_sistem FROM role_kepsek;
            REVOKE INSERT (kunci, nilai, deskripsi, id_pengguna_pengubah, tgl_diubah)
                ON TABLE public.pengaturan_sistem FROM role_kepsek;
            REVOKE UPDATE (nilai, deskripsi, id_pengguna_pengubah, tgl_diubah)
                ON TABLE public.pengaturan_sistem FROM role_kepsek;
            REVOKE USAGE, SELECT ON SEQUENCE public.pengaturan_sistem_id_pengaturan_seq FROM role_kepsek;
            SQL);
    }
};
