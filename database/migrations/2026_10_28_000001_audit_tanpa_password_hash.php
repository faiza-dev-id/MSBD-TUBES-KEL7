<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Jejak audit tabel pengguna tidak boleh menyimpan password_hash.
 * - fn_audit_trail membuang password_hash dan menambah penanda "sandi_diubah" bila sandi berganti.
 * - Catatan lama yang terlanjur memuat hash dibersihkan (hanya kunci password_hash yang dibuang).
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION public.fn_audit_trail() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id_data TEXT;
    v_pengguna_id BIGINT;
    v_username TEXT;
    v_old JSONB;
    v_new JSONB;
    v_sandi_diubah BOOLEAN := FALSE;
BEGIN
    v_pengguna_id := NULLIF(current_setting('app.user_id', TRUE), '')::BIGINT;
    v_username := NULLIF(current_setting('app.username', TRUE), '');

    IF TG_OP IN ('UPDATE', 'DELETE') THEN
        v_old := to_jsonb(OLD);
    END IF;

    IF TG_OP IN ('INSERT', 'UPDATE') THEN
        v_new := to_jsonb(NEW);
    END IF;

    IF TG_TABLE_NAME = 'pengguna' THEN
        IF TG_OP = 'UPDATE' THEN
            v_sandi_diubah := (v_old->>'password_hash') IS DISTINCT FROM (v_new->>'password_hash');
        END IF;
        v_old := v_old - 'password_hash';
        v_new := v_new - 'password_hash';
        IF v_sandi_diubah THEN
            v_new := v_new || jsonb_build_object('sandi_diubah', TRUE);
        END IF;
    END IF;

    CASE TG_TABLE_NAME
        WHEN 'siswa' THEN
            v_id_data := COALESCE(v_new, v_old)->>'id_siswa';
        WHEN 'pengguna' THEN
            v_id_data := COALESCE(v_new, v_old)->>'id_pengguna';
        WHEN 'pengajuan_perubahan' THEN
            v_id_data := COALESCE(v_new, v_old)->>'id_pengajuan';
        ELSE
            v_id_data := NULL;
    END CASE;

    INSERT INTO log_audit (
        nama_tabel, operasi, id_data, nilai_lama, nilai_baru,
        id_pengguna_pengubah, username_pengubah, waktu
    )
    VALUES (
        TG_TABLE_NAME, TG_OP, v_id_data, v_old, v_new,
        v_pengguna_id, v_username, CURRENT_TIMESTAMP
    );

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$;
SQL);

        DB::unprepared(<<<'SQL'
UPDATE log_audit
   SET nilai_lama = nilai_lama - 'password_hash',
       nilai_baru = nilai_baru - 'password_hash'
 WHERE nama_tabel = 'pengguna'
   AND (jsonb_exists(nilai_lama, 'password_hash') OR jsonb_exists(nilai_baru, 'password_hash'));
SQL);
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan: versi lama menyimpan password_hash di log audit.
    }
};
