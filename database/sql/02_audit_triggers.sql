-- =====================================================
-- TRIGGER AUDIT TRAIL
-- Sistem Akademik SMA Methodist 2 Medan
-- =====================================================

CREATE OR REPLACE FUNCTION fn_audit_trail()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_id_data TEXT;
    v_pengguna_id BIGINT;
    v_username TEXT;
BEGIN
    -- Mengambil identitas pengguna aplikasi jika tersedia.
    v_pengguna_id :=
        NULLIF(current_setting('app.user_id', TRUE), '')::BIGINT;

    v_username :=
        NULLIF(current_setting('app.username', TRUE), '');

    IF TG_OP = 'INSERT' THEN
        v_id_data := to_jsonb(NEW)->>'id_' || TG_TABLE_NAME;
    ELSIF TG_OP = 'UPDATE' THEN
        v_id_data := to_jsonb(NEW)->>'id_' || TG_TABLE_NAME;
    ELSE
        v_id_data := to_jsonb(OLD)->>'id_' || TG_TABLE_NAME;
    END IF;

    INSERT INTO log_audit (
        nama_tabel,
        operasi,
        id_data,
        nilai_lama,
        nilai_baru,
        id_pengguna_pengubah,
        username_pengubah,
        waktu
    )
    VALUES (
        TG_TABLE_NAME,
        TG_OP,
        v_id_data,
        CASE
            WHEN TG_OP IN ('UPDATE', 'DELETE')
            THEN to_jsonb(OLD)
            ELSE NULL
        END,
        CASE
            WHEN TG_OP IN ('INSERT', 'UPDATE')
            THEN to_jsonb(NEW)
            ELSE NULL
        END,
        v_pengguna_id,
        v_username,
        CURRENT_TIMESTAMP
    );

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;

    RETURN NEW;
END;
$$;

-- Memasang trigger pada tabel siswa.
DROP TRIGGER IF EXISTS trg_audit_siswa ON siswa;

CREATE TRIGGER trg_audit_siswa
AFTER INSERT OR UPDATE OR DELETE
ON siswa
FOR EACH ROW
EXECUTE FUNCTION fn_audit_trail();

-- Memasang trigger pada tabel pengguna.
DROP TRIGGER IF EXISTS trg_audit_pengguna ON pengguna;

CREATE TRIGGER trg_audit_pengguna
AFTER INSERT OR UPDATE OR DELETE
ON pengguna
FOR EACH ROW
EXECUTE FUNCTION fn_audit_trail();

-- Memasang trigger pada tabel pengajuan perubahan.
DROP TRIGGER IF EXISTS trg_audit_pengajuan ON pengajuan_perubahan;

CREATE TRIGGER trg_audit_pengajuan
AFTER INSERT OR UPDATE OR DELETE
ON pengajuan_perubahan
FOR EACH ROW
EXECUTE FUNCTION fn_audit_trail();