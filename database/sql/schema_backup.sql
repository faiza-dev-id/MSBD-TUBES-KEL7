--
-- PostgreSQL database dump
--

\restrict eJEEWp8oGgHp9jI4Tr1F6UtUvussCZmpElfmy2PXBn8bIAwGZ5OXHUrxTKFxNLg

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: fn_audit_trail(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_audit_trail() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id_data TEXT;
    v_pengguna_id BIGINT;
    v_username TEXT;
    v_old JSONB;
    v_new JSONB;
BEGIN
    v_pengguna_id :=
        NULLIF(current_setting('app.user_id', TRUE), '')::BIGINT;

    v_username :=
        NULLIF(current_setting('app.username', TRUE), '');

    IF TG_OP IN ('UPDATE', 'DELETE') THEN
        v_old := to_jsonb(OLD);
    END IF;

    IF TG_OP IN ('INSERT', 'UPDATE') THEN
        v_new := to_jsonb(NEW);
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
        v_old,
        v_new,
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


--
-- Name: sp_setujui_pengajuan(bigint, bigint, text); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.sp_setujui_pengajuan(IN p_id_pengajuan bigint, IN p_id_pemeriksa bigint, IN p_catatan text DEFAULT NULL::text)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id_siswa BIGINT;
    v_status VARCHAR(30);
    v_detail RECORD;
    v_jumlah_detail INTEGER := 0;
BEGIN
    -- 1. Periksa pengajuan dan kunci baris agar tidak diproses
    -- bersamaan oleh petugas lain.
    SELECT id_siswa, status_pengajuan
    INTO v_id_siswa, v_status
    FROM pengajuan_perubahan
    WHERE id_pengajuan = p_id_pengajuan
    FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION
            'Pengajuan dengan ID % tidak ditemukan.',
            p_id_pengajuan;
    END IF;

    -- 2. Pastikan pengajuan masih menunggu persetujuan.
    IF v_status <> 'menunggu' THEN
        RAISE EXCEPTION
            'Pengajuan tidak dapat diproses karena statusnya %.',
            v_status;
    END IF;

    -- 3. Pastikan petugas pemeriksa tersedia.
    IF NOT EXISTS (
        SELECT 1
        FROM pengguna
        WHERE id_pengguna = p_id_pemeriksa
    ) THEN
        RAISE EXCEPTION
            'Pengguna pemeriksa dengan ID % tidak ditemukan.',
            p_id_pemeriksa;
    END IF;

    -- 4. Terapkan setiap perubahan yang diizinkan.
    FOR v_detail IN
        SELECT nama_field, nilai_baru
        FROM detail_pengajuan
        WHERE id_pengajuan = p_id_pengajuan
        ORDER BY id_detail_pengajuan
    LOOP
        v_jumlah_detail := v_jumlah_detail + 1;

        CASE v_detail.nama_field

            WHEN 'nama_lengkap' THEN
                UPDATE siswa
                SET nama_lengkap = v_detail.nilai_baru #>> '{}',
                    diubah_pada = CURRENT_TIMESTAMP
                WHERE id_siswa = v_id_siswa;

            WHEN 'jenis_kelamin' THEN
                UPDATE siswa
                SET jenis_kelamin = v_detail.nilai_baru #>> '{}',
                    diubah_pada = CURRENT_TIMESTAMP
                WHERE id_siswa = v_id_siswa;

            WHEN 'tempat_lahir' THEN
                UPDATE siswa
                SET tempat_lahir = v_detail.nilai_baru #>> '{}',
                    diubah_pada = CURRENT_TIMESTAMP
                WHERE id_siswa = v_id_siswa;

            WHEN 'tanggal_lahir' THEN
                UPDATE siswa
                SET tanggal_lahir =
                    NULLIF(v_detail.nilai_baru #>> '{}', '')::DATE,
                    diubah_pada = CURRENT_TIMESTAMP
                WHERE id_siswa = v_id_siswa;

            WHEN 'email' THEN
                UPDATE siswa
                SET email = v_detail.nilai_baru #>> '{}',
                    diubah_pada = CURRENT_TIMESTAMP
                WHERE id_siswa = v_id_siswa;

            WHEN 'no_telepon' THEN
                UPDATE siswa
                SET no_telepon = v_detail.nilai_baru #>> '{}',
                    diubah_pada = CURRENT_TIMESTAMP
                WHERE id_siswa = v_id_siswa;

            WHEN 'nis' THEN
                UPDATE siswa
                SET nis = v_detail.nilai_baru #>> '{}',
                    diubah_pada = CURRENT_TIMESTAMP
                WHERE id_siswa = v_id_siswa;

            WHEN 'nisn' THEN
                UPDATE siswa
                SET nisn = v_detail.nilai_baru #>> '{}',
                    diubah_pada = CURRENT_TIMESTAMP
                WHERE id_siswa = v_id_siswa;

            WHEN 'nik' THEN
                UPDATE siswa
                SET nik = v_detail.nilai_baru #>> '{}',
                    diubah_pada = CURRENT_TIMESTAMP
                WHERE id_siswa = v_id_siswa;

            ELSE
                RAISE EXCEPTION
                    'Field perubahan "%" belum diizinkan.',
                    v_detail.nama_field;

        END CASE;
    END LOOP;

    -- 5. Tolak pengajuan kosong.
    IF v_jumlah_detail = 0 THEN
        RAISE EXCEPTION
            'Pengajuan tidak memiliki detail perubahan.';
    END IF;

    -- 6. Perbarui status pengajuan setelah semua perubahan berhasil.
    UPDATE pengajuan_perubahan
    SET status_pengajuan = 'disetujui',
        id_pemeriksa = p_id_pemeriksa,
        catatan_pemeriksa = p_catatan,
        diputuskan_pada = CURRENT_TIMESTAMP
    WHERE id_pengajuan = p_id_pengajuan;

END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: agama; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agama (
    id_agama bigint NOT NULL,
    nama_agama character varying(80) NOT NULL,
    is_aktif boolean DEFAULT true NOT NULL
);


--
-- Name: agama_id_agama_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.agama ALTER COLUMN id_agama ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.agama_id_agama_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: alamat_siswa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.alamat_siswa (
    id_alamat bigint NOT NULL,
    id_siswa bigint NOT NULL,
    alamat text NOT NULL,
    rt character varying(5),
    rw character varying(5),
    kelurahan character varying(100),
    kecamatan character varying(100),
    kota character varying(100),
    provinsi character varying(100),
    kode_pos character varying(10)
);


--
-- Name: alamat_siswa_id_alamat_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.alamat_siswa ALTER COLUMN id_alamat ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.alamat_siswa_id_alamat_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: catatan_wali_kelas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.catatan_wali_kelas (
    id_catatan bigint NOT NULL,
    id_siswa bigint NOT NULL,
    id_guru bigint NOT NULL,
    id_tahun_ajaran bigint NOT NULL,
    isi_catatan text NOT NULL,
    tgl_dibuat timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: catatan_wali_kelas_id_catatan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.catatan_wali_kelas ALTER COLUMN id_catatan ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.catatan_wali_kelas_id_catatan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cetak_rapor; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cetak_rapor (
    id_cetak bigint NOT NULL,
    id_rapor bigint NOT NULL,
    id_pencetak bigint NOT NULL,
    nomor_seri character varying(100) NOT NULL,
    waktu_cetak timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: cetak_rapor_id_cetak_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.cetak_rapor ALTER COLUMN id_cetak ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.cetak_rapor_id_cetak_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: detail_absensi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.detail_absensi (
    id_detail_absensi bigint NOT NULL,
    id_sesi bigint NOT NULL,
    id_siswa bigint NOT NULL,
    id_status_hadir bigint NOT NULL,
    keterangan text,
    waktu_dicatat timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: detail_absensi_id_detail_absensi_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.detail_absensi ALTER COLUMN id_detail_absensi ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.detail_absensi_id_detail_absensi_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: detail_pengajuan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.detail_pengajuan (
    id_detail_pengajuan bigint NOT NULL,
    id_pengajuan bigint NOT NULL,
    nama_field character varying(100) NOT NULL,
    nilai_lama jsonb,
    nilai_baru jsonb,
    bukti_path character varying(500)
);


--
-- Name: detail_pengajuan_id_detail_pengajuan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.detail_pengajuan ALTER COLUMN id_detail_pengajuan ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.detail_pengajuan_id_detail_pengajuan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: detail_rapor; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.detail_rapor (
    id_detail_rapor bigint NOT NULL,
    id_rapor bigint NOT NULL,
    id_mapel bigint NOT NULL,
    id_predikat bigint,
    nilai_akhir numeric(5,2) NOT NULL,
    CONSTRAINT detail_rapor_nilai_akhir_check CHECK (((nilai_akhir >= (0)::numeric) AND (nilai_akhir <= (100)::numeric)))
);


--
-- Name: detail_rapor_id_detail_rapor_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.detail_rapor ALTER COLUMN id_detail_rapor ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.detail_rapor_id_detail_rapor_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: detail_verifikasi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.detail_verifikasi (
    id_verifikasi bigint NOT NULL,
    id_item bigint NOT NULL,
    lolos boolean NOT NULL
);


--
-- Name: dokumen_siswa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.dokumen_siswa (
    id_dokumen bigint NOT NULL,
    id_siswa bigint,
    id_pendaftaran bigint,
    jenis_dokumen character varying(80) NOT NULL,
    nama_file_asli character varying(255) NOT NULL,
    path_file character varying(500) NOT NULL,
    status_verifikasi character varying(30) DEFAULT 'menunggu'::character varying NOT NULL,
    catatan text,
    diunggah_pada timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT dokumen_siswa_check CHECK (((id_siswa IS NOT NULL) OR (id_pendaftaran IS NOT NULL))),
    CONSTRAINT dokumen_siswa_status_verifikasi_check CHECK (((status_verifikasi)::text = ANY ((ARRAY['menunggu'::character varying, 'valid'::character varying, 'ditolak'::character varying, 'perlu_perbaikan'::character varying])::text[])))
);


--
-- Name: dokumen_siswa_id_dokumen_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.dokumen_siswa ALTER COLUMN id_dokumen ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.dokumen_siswa_id_dokumen_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: ekspor_laporan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ekspor_laporan (
    id_ekspor bigint NOT NULL,
    id_pengguna bigint NOT NULL,
    id_kelas bigint,
    id_tahun_ajaran bigint,
    jenis_laporan character varying(80) NOT NULL,
    format character varying(20) NOT NULL,
    waktu_ekspor timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: ekspor_laporan_id_ekspor_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.ekspor_laporan ALTER COLUMN id_ekspor ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.ekspor_laporan_id_ekspor_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: guru; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.guru (
    id_guru bigint NOT NULL,
    id_pengguna bigint,
    nip character varying(40),
    nama_guru character varying(150) NOT NULL,
    email character varying(255),
    no_telepon character varying(30),
    is_aktif boolean DEFAULT true NOT NULL
);


--
-- Name: guru_id_guru_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.guru ALTER COLUMN id_guru ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.guru_id_guru_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: hak_akses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hak_akses (
    id_hak_akses bigint NOT NULL,
    kode_hak_akses character varying(100) NOT NULL,
    nama_hak_akses character varying(150) NOT NULL,
    deskripsi text
);


--
-- Name: hak_akses_id_hak_akses_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.hak_akses ALTER COLUMN id_hak_akses ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.hak_akses_id_hak_akses_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: izin_siswa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.izin_siswa (
    id_izin bigint NOT NULL,
    id_siswa bigint NOT NULL,
    id_status_hadir bigint NOT NULL,
    id_dokumen_bukti bigint,
    id_penyetuju bigint,
    tgl_mulai date NOT NULL,
    tgl_selesai date NOT NULL,
    alasan text NOT NULL,
    status character varying(30) DEFAULT 'menunggu'::character varying NOT NULL,
    tgl_pengajuan timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT izin_siswa_check CHECK ((tgl_selesai >= tgl_mulai))
);


--
-- Name: izin_siswa_id_izin_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.izin_siswa ALTER COLUMN id_izin ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.izin_siswa_id_izin_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: jadwal_pelajaran; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jadwal_pelajaran (
    id_jadwal bigint NOT NULL,
    id_kelas bigint NOT NULL,
    id_semester bigint NOT NULL,
    id_mata_pelajaran bigint NOT NULL,
    id_guru bigint NOT NULL,
    hari smallint NOT NULL,
    jam_mulai time without time zone NOT NULL,
    jam_selesai time without time zone NOT NULL,
    ruang character varying(50),
    CONSTRAINT jadwal_pelajaran_check CHECK ((jam_selesai > jam_mulai)),
    CONSTRAINT jadwal_pelajaran_hari_check CHECK (((hari >= 1) AND (hari <= 7)))
);


--
-- Name: jadwal_pelajaran_id_jadwal_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.jadwal_pelajaran ALTER COLUMN id_jadwal ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.jadwal_pelajaran_id_jadwal_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: jenis_dokumen; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jenis_dokumen (
    id_jenis_dokumen bigint NOT NULL,
    nama_dokumen character varying(120) NOT NULL
);


--
-- Name: jenis_dokumen_id_jenis_dokumen_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.jenis_dokumen ALTER COLUMN id_jenis_dokumen ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.jenis_dokumen_id_jenis_dokumen_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: jenjang; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jenjang (
    id_jenjang bigint NOT NULL,
    nama_jenjang character varying(80) NOT NULL
);


--
-- Name: jenjang_id_jenjang_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.jenjang ALTER COLUMN id_jenjang ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.jenjang_id_jenjang_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: keanggotaan_kelas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.keanggotaan_kelas (
    id_keanggotaan bigint NOT NULL,
    id_siswa bigint NOT NULL,
    id_kelas bigint NOT NULL,
    id_semester bigint NOT NULL,
    tanggal_mulai date DEFAULT CURRENT_DATE NOT NULL,
    tanggal_selesai date,
    status character varying(30) DEFAULT 'aktif'::character varying NOT NULL,
    CONSTRAINT keanggotaan_kelas_check CHECK (((tanggal_selesai IS NULL) OR (tanggal_selesai >= tanggal_mulai))),
    CONSTRAINT keanggotaan_kelas_status_check CHECK (((status)::text = ANY ((ARRAY['aktif'::character varying, 'pindah'::character varying, 'naik_kelas'::character varying, 'lulus'::character varying, 'keluar'::character varying])::text[])))
);


--
-- Name: keanggotaan_kelas_id_keanggotaan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.keanggotaan_kelas ALTER COLUMN id_keanggotaan ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.keanggotaan_kelas_id_keanggotaan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: kecamatan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kecamatan (
    id_kecamatan bigint NOT NULL,
    id_kota bigint NOT NULL,
    nama_kecamatan character varying(120) NOT NULL
);


--
-- Name: kecamatan_id_kecamatan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.kecamatan ALTER COLUMN id_kecamatan ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.kecamatan_id_kecamatan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: kelas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kelas (
    id_kelas bigint NOT NULL,
    id_tingkat bigint,
    nama_kelas character varying(80) NOT NULL,
    kapasitas integer,
    is_aktif boolean DEFAULT true NOT NULL,
    id_tahun_ajaran bigint,
    CONSTRAINT kelas_kapasitas_check CHECK (((kapasitas IS NULL) OR (kapasitas > 0)))
);


--
-- Name: kelas_id_kelas_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.kelas ALTER COLUMN id_kelas ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.kelas_id_kelas_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: kelurahan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kelurahan (
    id_kelurahan bigint NOT NULL,
    id_kecamatan bigint NOT NULL,
    nama_kelurahan character varying(120) NOT NULL,
    kode_pos character varying(10)
);


--
-- Name: kelurahan_id_kelurahan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.kelurahan ALTER COLUMN id_kelurahan ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.kelurahan_id_kelurahan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: komponen_penilaian; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.komponen_penilaian (
    id_komponen bigint NOT NULL,
    nama_komponen character varying(120) NOT NULL,
    bobot_persen numeric(5,2) NOT NULL,
    CONSTRAINT komponen_penilaian_bobot_persen_check CHECK (((bobot_persen >= (0)::numeric) AND (bobot_persen <= (100)::numeric)))
);


--
-- Name: komponen_penilaian_id_komponen_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.komponen_penilaian ALTER COLUMN id_komponen ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.komponen_penilaian_id_komponen_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: konfirmasi_data; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.konfirmasi_data (
    id_konfirmasi bigint NOT NULL,
    id_periode_update bigint NOT NULL,
    id_siswa bigint NOT NULL,
    status character varying(30) DEFAULT 'belum'::character varying NOT NULL,
    dikonfirmasi_pada timestamp with time zone,
    catatan text,
    CONSTRAINT konfirmasi_data_status_check CHECK (((status)::text = ANY ((ARRAY['belum'::character varying, 'dikonfirmasi'::character varying, 'perlu_perubahan'::character varying])::text[])))
);


--
-- Name: konfirmasi_data_id_konfirmasi_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.konfirmasi_data ALTER COLUMN id_konfirmasi ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.konfirmasi_data_id_konfirmasi_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: kota; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kota (
    id_kota bigint NOT NULL,
    id_provinsi bigint NOT NULL,
    nama_kota character varying(120) NOT NULL
);


--
-- Name: kota_id_kota_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.kota ALTER COLUMN id_kota ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.kota_id_kota_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: log_audit; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.log_audit (
    id_log bigint NOT NULL,
    nama_tabel character varying(100) NOT NULL,
    operasi character varying(10) NOT NULL,
    id_data text,
    nilai_lama jsonb,
    nilai_baru jsonb,
    id_pengguna_pengubah bigint,
    username_pengubah text,
    waktu timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT log_audit_operasi_check CHECK (((operasi)::text = ANY ((ARRAY['INSERT'::character varying, 'UPDATE'::character varying, 'DELETE'::character varying])::text[])))
);


--
-- Name: log_audit_id_log_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.log_audit ALTER COLUMN id_log ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.log_audit_id_log_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: log_login; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.log_login (
    id_log_login bigint NOT NULL,
    id_pengguna bigint,
    username_dicoba character varying(100) NOT NULL,
    berhasil boolean NOT NULL,
    ip_address character varying(45),
    user_agent character varying(255),
    waktu timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: log_login_id_log_login_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.log_login ALTER COLUMN id_log_login ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.log_login_id_log_login_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: mata_pelajaran; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mata_pelajaran (
    id_mata_pelajaran bigint NOT NULL,
    kode_mapel character varying(30),
    nama_mapel character varying(150) NOT NULL,
    is_aktif boolean DEFAULT true NOT NULL
);


--
-- Name: mata_pelajaran_id_mata_pelajaran_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.mata_pelajaran ALTER COLUMN id_mata_pelajaran ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.mata_pelajaran_id_mata_pelajaran_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: mutasi_siswa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mutasi_siswa (
    id_mutasi bigint NOT NULL,
    id_siswa bigint NOT NULL,
    id_pengaju bigint NOT NULL,
    id_penyetuju bigint,
    id_sekolah_tujuan bigint,
    jenis character varying(40) NOT NULL,
    alasan text,
    status character varying(30) DEFAULT 'menunggu'::character varying NOT NULL,
    tgl_pengajuan timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    tgl_keputusan timestamp with time zone
);


--
-- Name: mutasi_siswa_id_mutasi_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.mutasi_siswa ALTER COLUMN id_mutasi ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.mutasi_siswa_id_mutasi_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: nilai_siswa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.nilai_siswa (
    id_nilai bigint NOT NULL,
    id_anggota bigint NOT NULL,
    id_mapel bigint NOT NULL,
    id_semester bigint NOT NULL,
    id_komponen bigint NOT NULL,
    id_penginput bigint NOT NULL,
    nilai numeric(5,2) NOT NULL,
    tgl_input timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT nilai_siswa_nilai_check CHECK (((nilai >= (0)::numeric) AND (nilai <= (100)::numeric)))
);


--
-- Name: nilai_siswa_id_nilai_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.nilai_siswa ALTER COLUMN id_nilai ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.nilai_siswa_id_nilai_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: notifikasi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifikasi (
    id_notifikasi bigint NOT NULL,
    id_pengguna bigint NOT NULL,
    judul character varying(150) NOT NULL,
    pesan text NOT NULL,
    dibaca boolean DEFAULT false NOT NULL,
    dibuat_pada timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: notifikasi_id_notifikasi_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.notifikasi ALTER COLUMN id_notifikasi ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.notifikasi_id_notifikasi_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: orang_tua_wali; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.orang_tua_wali (
    id_orang_tua_wali bigint NOT NULL,
    id_siswa bigint NOT NULL,
    hubungan character varying(30) NOT NULL,
    nama_lengkap character varying(150) NOT NULL,
    id_pekerjaan bigint,
    no_telepon character varying(30),
    email character varying(255),
    alamat text,
    CONSTRAINT orang_tua_wali_hubungan_check CHECK (((hubungan)::text = ANY ((ARRAY['ayah'::character varying, 'ibu'::character varying, 'wali'::character varying])::text[])))
);


--
-- Name: orang_tua_wali_id_orang_tua_wali_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.orang_tua_wali ALTER COLUMN id_orang_tua_wali ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.orang_tua_wali_id_orang_tua_wali_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: pekerjaan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pekerjaan (
    id_pekerjaan bigint NOT NULL,
    nama_pekerjaan character varying(120) NOT NULL,
    is_aktif boolean DEFAULT true NOT NULL
);


--
-- Name: pekerjaan_id_pekerjaan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.pekerjaan ALTER COLUMN id_pekerjaan ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.pekerjaan_id_pekerjaan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: pendaftaran_ppdb; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pendaftaran_ppdb (
    id_pendaftaran bigint NOT NULL,
    nomor_pendaftaran character varying(40) NOT NULL,
    nama_calon_siswa character varying(150) NOT NULL,
    nisn character varying(20),
    tanggal_lahir date,
    sekolah_asal character varying(200),
    alamat text,
    no_telepon character varying(30),
    status_pendaftaran character varying(30) DEFAULT 'menunggu'::character varying NOT NULL,
    catatan_verifikasi text,
    id_pengguna_pemeriksa bigint,
    diajukan_pada timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    diperiksa_pada timestamp with time zone,
    CONSTRAINT pendaftaran_ppdb_status_pendaftaran_check CHECK (((status_pendaftaran)::text = ANY ((ARRAY['menunggu'::character varying, 'diverifikasi'::character varying, 'diterima'::character varying, 'ditolak'::character varying, 'perlu_perbaikan'::character varying])::text[])))
);


--
-- Name: pendaftaran_ppdb_id_pendaftaran_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.pendaftaran_ppdb ALTER COLUMN id_pendaftaran ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.pendaftaran_ppdb_id_pendaftaran_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: pengajuan_perubahan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pengajuan_perubahan (
    id_pengajuan bigint NOT NULL,
    id_siswa bigint NOT NULL,
    id_periode_update bigint,
    tgl_pengajuan timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    status_pengajuan character varying(30) DEFAULT 'menunggu'::character varying NOT NULL,
    alasan text,
    catatan_pemeriksa text,
    id_pemeriksa bigint,
    diputuskan_pada timestamp with time zone,
    CONSTRAINT pengajuan_perubahan_status_pengajuan_check CHECK (((status_pengajuan)::text = ANY ((ARRAY['menunggu'::character varying, 'disetujui'::character varying, 'ditolak'::character varying, 'perlu_perbaikan'::character varying])::text[])))
);


--
-- Name: pengajuan_perubahan_id_pengajuan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.pengajuan_perubahan ALTER COLUMN id_pengajuan ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.pengajuan_perubahan_id_pengajuan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: pengaturan_sistem; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pengaturan_sistem (
    id_pengaturan bigint NOT NULL,
    id_pengguna_pengubah bigint,
    kunci character varying(120) NOT NULL,
    nilai text,
    deskripsi text,
    tgl_diubah timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: pengaturan_sistem_id_pengaturan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.pengaturan_sistem ALTER COLUMN id_pengaturan ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.pengaturan_sistem_id_pengaturan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: pengguna; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pengguna (
    id_pengguna bigint NOT NULL,
    id_role bigint NOT NULL,
    username character varying(100) NOT NULL,
    password_hash character varying(255) NOT NULL,
    is_aktif boolean DEFAULT true NOT NULL,
    terakhir_login timestamp with time zone,
    dibuat_pada timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    diubah_pada timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: pengguna_id_pengguna_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.pengguna ALTER COLUMN id_pengguna ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.pengguna_id_pengguna_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: penugasan_wali_kelas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penugasan_wali_kelas (
    id_penugasan_wali_kelas bigint NOT NULL,
    id_kelas bigint NOT NULL,
    id_guru bigint NOT NULL,
    id_semester bigint NOT NULL,
    ditugaskan_pada timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: penugasan_wali_kelas_id_penugasan_wali_kelas_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.penugasan_wali_kelas ALTER COLUMN id_penugasan_wali_kelas ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.penugasan_wali_kelas_id_penugasan_wali_kelas_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: periode_update; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.periode_update (
    id_periode_update bigint NOT NULL,
    id_tahun_ajaran bigint NOT NULL,
    nama_periode character varying(150) NOT NULL,
    tanggal_mulai date NOT NULL,
    tanggal_selesai date NOT NULL,
    status character varying(30) DEFAULT 'draft'::character varying NOT NULL,
    dibuat_oleh bigint,
    CONSTRAINT periode_update_check CHECK ((tanggal_selesai >= tanggal_mulai)),
    CONSTRAINT periode_update_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'aktif'::character varying, 'ditutup'::character varying])::text[])))
);


--
-- Name: periode_update_id_periode_update_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.periode_update ALTER COLUMN id_periode_update ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.periode_update_id_periode_update_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: provinsi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.provinsi (
    id_provinsi bigint NOT NULL,
    nama_provinsi character varying(120) NOT NULL
);


--
-- Name: provinsi_id_provinsi_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.provinsi ALTER COLUMN id_provinsi ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.provinsi_id_provinsi_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: rapor; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rapor (
    id_rapor bigint NOT NULL,
    id_anggota bigint NOT NULL,
    id_semester bigint NOT NULL,
    id_penyusun bigint NOT NULL,
    rata_rata numeric(5,2),
    peringkat integer,
    jumlah_sakit integer DEFAULT 0 NOT NULL,
    jumlah_izin integer DEFAULT 0 NOT NULL,
    jumlah_alpa integer DEFAULT 0 NOT NULL,
    catatan_wali text,
    status_rapor character varying(30) DEFAULT 'draft'::character varying NOT NULL,
    tgl_dibuat timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT rapor_check CHECK (((jumlah_sakit >= 0) AND (jumlah_izin >= 0) AND (jumlah_alpa >= 0))),
    CONSTRAINT rapor_peringkat_check CHECK (((peringkat IS NULL) OR (peringkat > 0)))
);


--
-- Name: rapor_id_rapor_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.rapor ALTER COLUMN id_rapor ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.rapor_id_rapor_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: ref_alasan_pengembalian; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ref_alasan_pengembalian (
    id_alasan bigint NOT NULL,
    nama_alasan character varying(180) NOT NULL
);


--
-- Name: ref_alasan_pengembalian_id_alasan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.ref_alasan_pengembalian ALTER COLUMN id_alasan ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.ref_alasan_pengembalian_id_alasan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: ref_checklist_item; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ref_checklist_item (
    id_item bigint NOT NULL,
    id_jenis_dokumen bigint NOT NULL,
    nama_item character varying(180) NOT NULL,
    wajib boolean DEFAULT true NOT NULL
);


--
-- Name: ref_checklist_item_id_item_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.ref_checklist_item ALTER COLUMN id_item ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.ref_checklist_item_id_item_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: ref_field_data; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ref_field_data (
    id_field bigint NOT NULL,
    id_jenis_dokumen_bukti bigint,
    nama_tabel character varying(100) NOT NULL,
    nama_kolom character varying(100) NOT NULL,
    label_field character varying(180) NOT NULL,
    boleh_diubah_siswa boolean DEFAULT false NOT NULL,
    perlu_bukti boolean DEFAULT false NOT NULL
);


--
-- Name: ref_field_data_id_field_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.ref_field_data ALTER COLUMN id_field ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.ref_field_data_id_field_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: role; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role (
    id_role bigint NOT NULL,
    nama_role character varying(50) NOT NULL,
    deskripsi text
);


--
-- Name: role_hak_akses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_hak_akses (
    id_role bigint NOT NULL,
    id_hak_akses bigint NOT NULL
);


--
-- Name: role_id_role_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.role ALTER COLUMN id_role ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.role_id_role_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: sekolah; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sekolah (
    id_sekolah bigint NOT NULL,
    nama_sekolah character varying(200) NOT NULL,
    npsn character varying(20),
    alamat text,
    is_aktif boolean DEFAULT true NOT NULL
);


--
-- Name: sekolah_id_sekolah_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.sekolah ALTER COLUMN id_sekolah ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.sekolah_id_sekolah_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: semester; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.semester (
    id_semester bigint NOT NULL,
    id_tahun_ajaran bigint NOT NULL,
    nama_semester character varying(30) NOT NULL,
    tanggal_mulai date,
    tanggal_selesai date,
    is_aktif boolean DEFAULT false NOT NULL,
    CONSTRAINT semester_check CHECK (((tanggal_selesai IS NULL) OR (tanggal_mulai IS NULL) OR (tanggal_selesai > tanggal_mulai)))
);


--
-- Name: semester_id_semester_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.semester ALTER COLUMN id_semester ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.semester_id_semester_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: sesi_absensi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sesi_absensi (
    id_sesi bigint NOT NULL,
    id_jadwal bigint NOT NULL,
    id_guru_pencatat bigint NOT NULL,
    tanggal date NOT NULL,
    topik_materi text,
    jam_dibuka time without time zone,
    jam_ditutup time without time zone,
    status_sesi character varying(20) DEFAULT 'dibuka'::character varying NOT NULL
);


--
-- Name: sesi_absensi_id_sesi_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.sesi_absensi ALTER COLUMN id_sesi ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.sesi_absensi_id_sesi_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: siswa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.siswa (
    id_siswa bigint NOT NULL,
    id_pengguna bigint,
    id_status_siswa bigint,
    id_agama bigint,
    id_sekolah_asal bigint,
    nis character varying(30),
    nisn character varying(20),
    nik character varying(30),
    nama_lengkap character varying(150) NOT NULL,
    jenis_kelamin character(1),
    tempat_lahir character varying(100),
    tanggal_lahir date,
    email character varying(255),
    no_telepon character varying(30),
    tanggal_masuk date,
    dibuat_pada timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    diubah_pada timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT siswa_jenis_kelamin_check CHECK ((jenis_kelamin = ANY (ARRAY['L'::bpchar, 'P'::bpchar])))
);


--
-- Name: siswa_id_siswa_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.siswa ALTER COLUMN id_siswa ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.siswa_id_siswa_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: skala_predikat; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.skala_predikat (
    id_predikat bigint NOT NULL,
    huruf character varying(5) NOT NULL,
    nilai_min numeric(5,2) NOT NULL,
    nilai_maks numeric(5,2) NOT NULL,
    deskripsi character varying(255),
    CONSTRAINT skala_predikat_check CHECK ((nilai_min <= nilai_maks))
);


--
-- Name: skala_predikat_id_predikat_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.skala_predikat ALTER COLUMN id_predikat ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.skala_predikat_id_predikat_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: staf; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.staf (
    id_staf bigint NOT NULL,
    id_pengguna bigint,
    nama_staf character varying(150) NOT NULL,
    jabatan character varying(100),
    email character varying(255),
    no_telepon character varying(30),
    is_aktif boolean DEFAULT true NOT NULL
);


--
-- Name: staf_id_staf_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.staf ALTER COLUMN id_staf ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.staf_id_staf_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: status_kehadiran; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.status_kehadiran (
    id_status_hadir bigint NOT NULL,
    kode character varying(10) NOT NULL,
    nama_status character varying(60) NOT NULL
);


--
-- Name: status_kehadiran_id_status_hadir_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.status_kehadiran ALTER COLUMN id_status_hadir ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.status_kehadiran_id_status_hadir_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: status_siswa; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.status_siswa (
    id_status_siswa bigint NOT NULL,
    kode_status character varying(40) NOT NULL,
    nama_status character varying(80) NOT NULL,
    boleh_diubah_siswa boolean DEFAULT false NOT NULL
);


--
-- Name: status_siswa_id_status_siswa_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.status_siswa ALTER COLUMN id_status_siswa ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.status_siswa_id_status_siswa_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: tahun_ajaran; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tahun_ajaran (
    id_tahun_ajaran bigint NOT NULL,
    nama_tahun_ajaran character varying(20) NOT NULL,
    tanggal_mulai date NOT NULL,
    tanggal_selesai date NOT NULL,
    is_aktif boolean DEFAULT false NOT NULL,
    CONSTRAINT tahun_ajaran_check CHECK ((tanggal_selesai > tanggal_mulai))
);


--
-- Name: tahun_ajaran_id_tahun_ajaran_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.tahun_ajaran ALTER COLUMN id_tahun_ajaran ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.tahun_ajaran_id_tahun_ajaran_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: tingkat; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tingkat (
    id_tingkat bigint NOT NULL,
    nama_tingkat character varying(50) NOT NULL,
    urutan smallint NOT NULL,
    id_jenjang bigint,
    CONSTRAINT tingkat_urutan_check CHECK ((urutan > 0))
);


--
-- Name: tingkat_id_tingkat_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.tingkat ALTER COLUMN id_tingkat ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.tingkat_id_tingkat_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: verifikasi_pendaftaran; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.verifikasi_pendaftaran (
    id_verifikasi bigint NOT NULL,
    id_pendaftaran bigint NOT NULL,
    id_petugas bigint NOT NULL,
    id_alasan bigint,
    hasil character varying(30) NOT NULL,
    catatan text,
    tgl_verifikasi timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: verifikasi_pendaftaran_id_verifikasi_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.verifikasi_pendaftaran ALTER COLUMN id_verifikasi ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.verifikasi_pendaftaran_id_verifikasi_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: vw_antrean_verifikasi; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.vw_antrean_verifikasi AS
 SELECT id_pendaftaran,
    status_pendaftaran,
    diajukan_pada AS tanggal_daftar
   FROM public.pendaftaran_ppdb pp
  WHERE (lower((COALESCE(status_pendaftaran, ''::character varying))::text) = ANY (ARRAY['menunggu'::text, 'diajukan'::text, 'perlu_verifikasi'::text]));


--
-- Name: vw_dashboard_kepsek; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.vw_dashboard_kepsek AS
 SELECT ( SELECT count(*) AS count
           FROM public.siswa) AS total_siswa,
    ( SELECT count(*) AS count
           FROM public.guru) AS total_guru,
    ( SELECT count(*) AS count
           FROM public.kelas) AS total_kelas,
    ( SELECT count(*) AS count
           FROM public.pendaftaran_ppdb) AS total_pendaftar,
    ( SELECT count(*) AS count
           FROM public.rapor
          WHERE ((rapor.status_rapor)::text = ANY ((ARRAY['final'::character varying, 'disetujui'::character varying, 'siap_cetak'::character varying])::text[]))) AS total_rapor_siap_cetak;


--
-- Name: vw_kelengkapan_data_kelas; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.vw_kelengkapan_data_kelas AS
 SELECT k.id_kelas,
    k.nama_kelas,
    count(DISTINCT kk.id_siswa) AS jumlah_siswa,
    count(DISTINCT kk.id_siswa) FILTER (WHERE (s.id_siswa IS NOT NULL)) AS siswa_terdata
   FROM ((public.kelas k
     LEFT JOIN public.keanggotaan_kelas kk ON ((kk.id_kelas = k.id_kelas)))
     LEFT JOIN public.siswa s ON ((s.id_siswa = kk.id_siswa)))
  GROUP BY k.id_kelas, k.nama_kelas;


--
-- Name: vw_nilai_rata_siswa; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.vw_nilai_rata_siswa AS
 WITH nilai_berbobot AS (
         SELECT ns.id_anggota,
            ns.id_mapel,
            ns.id_semester,
            (sum((ns.nilai * kp.bobot_persen)) / NULLIF(sum(kp.bobot_persen), (0)::numeric)) AS nilai_rata_rata
           FROM (public.nilai_siswa ns
             JOIN public.komponen_penilaian kp ON ((kp.id_komponen = ns.id_komponen)))
          GROUP BY ns.id_anggota, ns.id_mapel, ns.id_semester
        )
 SELECT id_anggota,
    id_mapel,
    id_semester,
    round(nilai_rata_rata, 2) AS nilai_rata_rata
   FROM nilai_berbobot;


--
-- Name: vw_rapor_siap_cetak; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.vw_rapor_siap_cetak AS
 SELECT id_rapor,
    id_anggota,
    id_semester,
    rata_rata,
    status_rapor,
    tgl_dibuat
   FROM public.rapor r
  WHERE ((status_rapor)::text = ANY ((ARRAY['final'::character varying, 'disetujui'::character varying, 'siap_cetak'::character varying])::text[]));


--
-- Name: vw_rekap_absensi_siswa; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.vw_rekap_absensi_siswa AS
 SELECT da.id_siswa,
    sa.tanggal,
    count(*) FILTER (WHERE ((sh.kode)::text = 'H'::text)) AS hadir,
    count(*) FILTER (WHERE ((sh.kode)::text = 'S'::text)) AS sakit,
    count(*) FILTER (WHERE ((sh.kode)::text = 'I'::text)) AS izin,
    count(*) FILTER (WHERE ((sh.kode)::text = 'A'::text)) AS alpa
   FROM ((public.detail_absensi da
     JOIN public.sesi_absensi sa ON ((sa.id_sesi = da.id_sesi)))
     JOIN public.status_kehadiran sh ON ((sh.id_status_hadir = da.id_status_hadir)))
  GROUP BY da.id_siswa, sa.tanggal;


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: agama agama_nama_agama_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agama
    ADD CONSTRAINT agama_nama_agama_key UNIQUE (nama_agama);


--
-- Name: agama agama_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agama
    ADD CONSTRAINT agama_pkey PRIMARY KEY (id_agama);


--
-- Name: alamat_siswa alamat_siswa_id_siswa_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alamat_siswa
    ADD CONSTRAINT alamat_siswa_id_siswa_key UNIQUE (id_siswa);


--
-- Name: alamat_siswa alamat_siswa_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alamat_siswa
    ADD CONSTRAINT alamat_siswa_pkey PRIMARY KEY (id_alamat);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: catatan_wali_kelas catatan_wali_kelas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.catatan_wali_kelas
    ADD CONSTRAINT catatan_wali_kelas_pkey PRIMARY KEY (id_catatan);


--
-- Name: cetak_rapor cetak_rapor_nomor_seri_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cetak_rapor
    ADD CONSTRAINT cetak_rapor_nomor_seri_key UNIQUE (nomor_seri);


--
-- Name: cetak_rapor cetak_rapor_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cetak_rapor
    ADD CONSTRAINT cetak_rapor_pkey PRIMARY KEY (id_cetak);


--
-- Name: detail_absensi detail_absensi_id_sesi_id_siswa_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_absensi
    ADD CONSTRAINT detail_absensi_id_sesi_id_siswa_key UNIQUE (id_sesi, id_siswa);


--
-- Name: detail_absensi detail_absensi_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_absensi
    ADD CONSTRAINT detail_absensi_pkey PRIMARY KEY (id_detail_absensi);


--
-- Name: detail_pengajuan detail_pengajuan_id_pengajuan_nama_field_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_pengajuan
    ADD CONSTRAINT detail_pengajuan_id_pengajuan_nama_field_key UNIQUE (id_pengajuan, nama_field);


--
-- Name: detail_pengajuan detail_pengajuan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_pengajuan
    ADD CONSTRAINT detail_pengajuan_pkey PRIMARY KEY (id_detail_pengajuan);


--
-- Name: detail_rapor detail_rapor_id_rapor_id_mapel_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_rapor
    ADD CONSTRAINT detail_rapor_id_rapor_id_mapel_key UNIQUE (id_rapor, id_mapel);


--
-- Name: detail_rapor detail_rapor_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_rapor
    ADD CONSTRAINT detail_rapor_pkey PRIMARY KEY (id_detail_rapor);


--
-- Name: detail_verifikasi detail_verifikasi_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_verifikasi
    ADD CONSTRAINT detail_verifikasi_pkey PRIMARY KEY (id_verifikasi, id_item);


--
-- Name: dokumen_siswa dokumen_siswa_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dokumen_siswa
    ADD CONSTRAINT dokumen_siswa_pkey PRIMARY KEY (id_dokumen);


--
-- Name: ekspor_laporan ekspor_laporan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ekspor_laporan
    ADD CONSTRAINT ekspor_laporan_pkey PRIMARY KEY (id_ekspor);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: guru guru_id_pengguna_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.guru
    ADD CONSTRAINT guru_id_pengguna_key UNIQUE (id_pengguna);


--
-- Name: guru guru_nip_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.guru
    ADD CONSTRAINT guru_nip_key UNIQUE (nip);


--
-- Name: guru guru_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.guru
    ADD CONSTRAINT guru_pkey PRIMARY KEY (id_guru);


--
-- Name: hak_akses hak_akses_kode_hak_akses_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hak_akses
    ADD CONSTRAINT hak_akses_kode_hak_akses_key UNIQUE (kode_hak_akses);


--
-- Name: hak_akses hak_akses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hak_akses
    ADD CONSTRAINT hak_akses_pkey PRIMARY KEY (id_hak_akses);


--
-- Name: izin_siswa izin_siswa_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.izin_siswa
    ADD CONSTRAINT izin_siswa_pkey PRIMARY KEY (id_izin);


--
-- Name: jadwal_pelajaran jadwal_pelajaran_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jadwal_pelajaran
    ADD CONSTRAINT jadwal_pelajaran_pkey PRIMARY KEY (id_jadwal);


--
-- Name: jenis_dokumen jenis_dokumen_nama_dokumen_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jenis_dokumen
    ADD CONSTRAINT jenis_dokumen_nama_dokumen_key UNIQUE (nama_dokumen);


--
-- Name: jenis_dokumen jenis_dokumen_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jenis_dokumen
    ADD CONSTRAINT jenis_dokumen_pkey PRIMARY KEY (id_jenis_dokumen);


--
-- Name: jenjang jenjang_nama_jenjang_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jenjang
    ADD CONSTRAINT jenjang_nama_jenjang_key UNIQUE (nama_jenjang);


--
-- Name: jenjang jenjang_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jenjang
    ADD CONSTRAINT jenjang_pkey PRIMARY KEY (id_jenjang);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: keanggotaan_kelas keanggotaan_kelas_id_siswa_id_semester_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.keanggotaan_kelas
    ADD CONSTRAINT keanggotaan_kelas_id_siswa_id_semester_key UNIQUE (id_siswa, id_semester);


--
-- Name: keanggotaan_kelas keanggotaan_kelas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.keanggotaan_kelas
    ADD CONSTRAINT keanggotaan_kelas_pkey PRIMARY KEY (id_keanggotaan);


--
-- Name: kecamatan kecamatan_id_kota_nama_kecamatan_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kecamatan
    ADD CONSTRAINT kecamatan_id_kota_nama_kecamatan_key UNIQUE (id_kota, nama_kecamatan);


--
-- Name: kecamatan kecamatan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kecamatan
    ADD CONSTRAINT kecamatan_pkey PRIMARY KEY (id_kecamatan);


--
-- Name: kelas kelas_id_tingkat_nama_kelas_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kelas
    ADD CONSTRAINT kelas_id_tingkat_nama_kelas_key UNIQUE (id_tingkat, nama_kelas);


--
-- Name: kelas kelas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kelas
    ADD CONSTRAINT kelas_pkey PRIMARY KEY (id_kelas);


--
-- Name: kelurahan kelurahan_id_kecamatan_nama_kelurahan_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kelurahan
    ADD CONSTRAINT kelurahan_id_kecamatan_nama_kelurahan_key UNIQUE (id_kecamatan, nama_kelurahan);


--
-- Name: kelurahan kelurahan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kelurahan
    ADD CONSTRAINT kelurahan_pkey PRIMARY KEY (id_kelurahan);


--
-- Name: komponen_penilaian komponen_penilaian_nama_komponen_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.komponen_penilaian
    ADD CONSTRAINT komponen_penilaian_nama_komponen_key UNIQUE (nama_komponen);


--
-- Name: komponen_penilaian komponen_penilaian_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.komponen_penilaian
    ADD CONSTRAINT komponen_penilaian_pkey PRIMARY KEY (id_komponen);


--
-- Name: konfirmasi_data konfirmasi_data_id_periode_update_id_siswa_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.konfirmasi_data
    ADD CONSTRAINT konfirmasi_data_id_periode_update_id_siswa_key UNIQUE (id_periode_update, id_siswa);


--
-- Name: konfirmasi_data konfirmasi_data_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.konfirmasi_data
    ADD CONSTRAINT konfirmasi_data_pkey PRIMARY KEY (id_konfirmasi);


--
-- Name: kota kota_id_provinsi_nama_kota_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kota
    ADD CONSTRAINT kota_id_provinsi_nama_kota_key UNIQUE (id_provinsi, nama_kota);


--
-- Name: kota kota_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kota
    ADD CONSTRAINT kota_pkey PRIMARY KEY (id_kota);


--
-- Name: log_audit log_audit_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.log_audit
    ADD CONSTRAINT log_audit_pkey PRIMARY KEY (id_log);


--
-- Name: log_login log_login_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.log_login
    ADD CONSTRAINT log_login_pkey PRIMARY KEY (id_log_login);


--
-- Name: mata_pelajaran mata_pelajaran_kode_mapel_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mata_pelajaran
    ADD CONSTRAINT mata_pelajaran_kode_mapel_key UNIQUE (kode_mapel);


--
-- Name: mata_pelajaran mata_pelajaran_nama_mapel_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mata_pelajaran
    ADD CONSTRAINT mata_pelajaran_nama_mapel_key UNIQUE (nama_mapel);


--
-- Name: mata_pelajaran mata_pelajaran_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mata_pelajaran
    ADD CONSTRAINT mata_pelajaran_pkey PRIMARY KEY (id_mata_pelajaran);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: mutasi_siswa mutasi_siswa_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mutasi_siswa
    ADD CONSTRAINT mutasi_siswa_pkey PRIMARY KEY (id_mutasi);


--
-- Name: nilai_siswa nilai_siswa_id_anggota_id_mapel_id_semester_id_komponen_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nilai_siswa
    ADD CONSTRAINT nilai_siswa_id_anggota_id_mapel_id_semester_id_komponen_key UNIQUE (id_anggota, id_mapel, id_semester, id_komponen);


--
-- Name: nilai_siswa nilai_siswa_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nilai_siswa
    ADD CONSTRAINT nilai_siswa_pkey PRIMARY KEY (id_nilai);


--
-- Name: notifikasi notifikasi_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifikasi
    ADD CONSTRAINT notifikasi_pkey PRIMARY KEY (id_notifikasi);


--
-- Name: orang_tua_wali orang_tua_wali_id_siswa_hubungan_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orang_tua_wali
    ADD CONSTRAINT orang_tua_wali_id_siswa_hubungan_key UNIQUE (id_siswa, hubungan);


--
-- Name: orang_tua_wali orang_tua_wali_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orang_tua_wali
    ADD CONSTRAINT orang_tua_wali_pkey PRIMARY KEY (id_orang_tua_wali);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: pekerjaan pekerjaan_nama_pekerjaan_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pekerjaan
    ADD CONSTRAINT pekerjaan_nama_pekerjaan_key UNIQUE (nama_pekerjaan);


--
-- Name: pekerjaan pekerjaan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pekerjaan
    ADD CONSTRAINT pekerjaan_pkey PRIMARY KEY (id_pekerjaan);


--
-- Name: pendaftaran_ppdb pendaftaran_ppdb_nomor_pendaftaran_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pendaftaran_ppdb
    ADD CONSTRAINT pendaftaran_ppdb_nomor_pendaftaran_key UNIQUE (nomor_pendaftaran);


--
-- Name: pendaftaran_ppdb pendaftaran_ppdb_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pendaftaran_ppdb
    ADD CONSTRAINT pendaftaran_ppdb_pkey PRIMARY KEY (id_pendaftaran);


--
-- Name: pengajuan_perubahan pengajuan_perubahan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengajuan_perubahan
    ADD CONSTRAINT pengajuan_perubahan_pkey PRIMARY KEY (id_pengajuan);


--
-- Name: pengaturan_sistem pengaturan_sistem_kunci_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengaturan_sistem
    ADD CONSTRAINT pengaturan_sistem_kunci_key UNIQUE (kunci);


--
-- Name: pengaturan_sistem pengaturan_sistem_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengaturan_sistem
    ADD CONSTRAINT pengaturan_sistem_pkey PRIMARY KEY (id_pengaturan);


--
-- Name: pengguna pengguna_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengguna
    ADD CONSTRAINT pengguna_pkey PRIMARY KEY (id_pengguna);


--
-- Name: pengguna pengguna_username_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengguna
    ADD CONSTRAINT pengguna_username_key UNIQUE (username);


--
-- Name: penugasan_wali_kelas penugasan_wali_kelas_id_guru_id_semester_id_kelas_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penugasan_wali_kelas
    ADD CONSTRAINT penugasan_wali_kelas_id_guru_id_semester_id_kelas_key UNIQUE (id_guru, id_semester, id_kelas);


--
-- Name: penugasan_wali_kelas penugasan_wali_kelas_id_kelas_id_semester_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penugasan_wali_kelas
    ADD CONSTRAINT penugasan_wali_kelas_id_kelas_id_semester_key UNIQUE (id_kelas, id_semester);


--
-- Name: penugasan_wali_kelas penugasan_wali_kelas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penugasan_wali_kelas
    ADD CONSTRAINT penugasan_wali_kelas_pkey PRIMARY KEY (id_penugasan_wali_kelas);


--
-- Name: periode_update periode_update_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.periode_update
    ADD CONSTRAINT periode_update_pkey PRIMARY KEY (id_periode_update);


--
-- Name: provinsi provinsi_nama_provinsi_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provinsi
    ADD CONSTRAINT provinsi_nama_provinsi_key UNIQUE (nama_provinsi);


--
-- Name: provinsi provinsi_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.provinsi
    ADD CONSTRAINT provinsi_pkey PRIMARY KEY (id_provinsi);


--
-- Name: rapor rapor_id_anggota_id_semester_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rapor
    ADD CONSTRAINT rapor_id_anggota_id_semester_key UNIQUE (id_anggota, id_semester);


--
-- Name: rapor rapor_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rapor
    ADD CONSTRAINT rapor_pkey PRIMARY KEY (id_rapor);


--
-- Name: ref_alasan_pengembalian ref_alasan_pengembalian_nama_alasan_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ref_alasan_pengembalian
    ADD CONSTRAINT ref_alasan_pengembalian_nama_alasan_key UNIQUE (nama_alasan);


--
-- Name: ref_alasan_pengembalian ref_alasan_pengembalian_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ref_alasan_pengembalian
    ADD CONSTRAINT ref_alasan_pengembalian_pkey PRIMARY KEY (id_alasan);


--
-- Name: ref_checklist_item ref_checklist_item_id_jenis_dokumen_nama_item_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ref_checklist_item
    ADD CONSTRAINT ref_checklist_item_id_jenis_dokumen_nama_item_key UNIQUE (id_jenis_dokumen, nama_item);


--
-- Name: ref_checklist_item ref_checklist_item_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ref_checklist_item
    ADD CONSTRAINT ref_checklist_item_pkey PRIMARY KEY (id_item);


--
-- Name: ref_field_data ref_field_data_nama_tabel_nama_kolom_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ref_field_data
    ADD CONSTRAINT ref_field_data_nama_tabel_nama_kolom_key UNIQUE (nama_tabel, nama_kolom);


--
-- Name: ref_field_data ref_field_data_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ref_field_data
    ADD CONSTRAINT ref_field_data_pkey PRIMARY KEY (id_field);


--
-- Name: role_hak_akses role_hak_akses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_hak_akses
    ADD CONSTRAINT role_hak_akses_pkey PRIMARY KEY (id_role, id_hak_akses);


--
-- Name: role role_nama_role_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role
    ADD CONSTRAINT role_nama_role_key UNIQUE (nama_role);


--
-- Name: role role_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role
    ADD CONSTRAINT role_pkey PRIMARY KEY (id_role);


--
-- Name: sekolah sekolah_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sekolah
    ADD CONSTRAINT sekolah_pkey PRIMARY KEY (id_sekolah);


--
-- Name: semester semester_id_tahun_ajaran_nama_semester_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.semester
    ADD CONSTRAINT semester_id_tahun_ajaran_nama_semester_key UNIQUE (id_tahun_ajaran, nama_semester);


--
-- Name: semester semester_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.semester
    ADD CONSTRAINT semester_pkey PRIMARY KEY (id_semester);


--
-- Name: sesi_absensi sesi_absensi_id_jadwal_tanggal_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sesi_absensi
    ADD CONSTRAINT sesi_absensi_id_jadwal_tanggal_key UNIQUE (id_jadwal, tanggal);


--
-- Name: sesi_absensi sesi_absensi_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sesi_absensi
    ADD CONSTRAINT sesi_absensi_pkey PRIMARY KEY (id_sesi);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: siswa siswa_id_pengguna_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siswa
    ADD CONSTRAINT siswa_id_pengguna_key UNIQUE (id_pengguna);


--
-- Name: siswa siswa_nik_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siswa
    ADD CONSTRAINT siswa_nik_key UNIQUE (nik);


--
-- Name: siswa siswa_nis_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siswa
    ADD CONSTRAINT siswa_nis_key UNIQUE (nis);


--
-- Name: siswa siswa_nisn_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siswa
    ADD CONSTRAINT siswa_nisn_key UNIQUE (nisn);


--
-- Name: siswa siswa_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siswa
    ADD CONSTRAINT siswa_pkey PRIMARY KEY (id_siswa);


--
-- Name: skala_predikat skala_predikat_huruf_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.skala_predikat
    ADD CONSTRAINT skala_predikat_huruf_key UNIQUE (huruf);


--
-- Name: skala_predikat skala_predikat_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.skala_predikat
    ADD CONSTRAINT skala_predikat_pkey PRIMARY KEY (id_predikat);


--
-- Name: staf staf_id_pengguna_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staf
    ADD CONSTRAINT staf_id_pengguna_key UNIQUE (id_pengguna);


--
-- Name: staf staf_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staf
    ADD CONSTRAINT staf_pkey PRIMARY KEY (id_staf);


--
-- Name: status_kehadiran status_kehadiran_kode_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_kehadiran
    ADD CONSTRAINT status_kehadiran_kode_key UNIQUE (kode);


--
-- Name: status_kehadiran status_kehadiran_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_kehadiran
    ADD CONSTRAINT status_kehadiran_pkey PRIMARY KEY (id_status_hadir);


--
-- Name: status_siswa status_siswa_kode_status_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_siswa
    ADD CONSTRAINT status_siswa_kode_status_key UNIQUE (kode_status);


--
-- Name: status_siswa status_siswa_nama_status_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_siswa
    ADD CONSTRAINT status_siswa_nama_status_key UNIQUE (nama_status);


--
-- Name: status_siswa status_siswa_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.status_siswa
    ADD CONSTRAINT status_siswa_pkey PRIMARY KEY (id_status_siswa);


--
-- Name: tahun_ajaran tahun_ajaran_nama_tahun_ajaran_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tahun_ajaran
    ADD CONSTRAINT tahun_ajaran_nama_tahun_ajaran_key UNIQUE (nama_tahun_ajaran);


--
-- Name: tahun_ajaran tahun_ajaran_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tahun_ajaran
    ADD CONSTRAINT tahun_ajaran_pkey PRIMARY KEY (id_tahun_ajaran);


--
-- Name: tingkat tingkat_nama_tingkat_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tingkat
    ADD CONSTRAINT tingkat_nama_tingkat_key UNIQUE (nama_tingkat);


--
-- Name: tingkat tingkat_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tingkat
    ADD CONSTRAINT tingkat_pkey PRIMARY KEY (id_tingkat);


--
-- Name: tingkat tingkat_urutan_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tingkat
    ADD CONSTRAINT tingkat_urutan_key UNIQUE (urutan);


--
-- Name: sekolah uq_sekolah_npsn; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sekolah
    ADD CONSTRAINT uq_sekolah_npsn UNIQUE (npsn);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: verifikasi_pendaftaran verifikasi_pendaftaran_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.verifikasi_pendaftaran
    ADD CONSTRAINT verifikasi_pendaftaran_pkey PRIMARY KEY (id_verifikasi);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: idx_catatan_siswa_tahun; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_catatan_siswa_tahun ON public.catatan_wali_kelas USING btree (id_siswa, id_tahun_ajaran);


--
-- Name: idx_izin_siswa_tanggal; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_izin_siswa_tanggal ON public.izin_siswa USING btree (id_siswa, tgl_mulai, tgl_selesai);


--
-- Name: idx_jadwal_kelas_semester; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_jadwal_kelas_semester ON public.jadwal_pelajaran USING btree (id_kelas, id_semester);


--
-- Name: idx_log_audit_waktu; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_log_audit_waktu ON public.log_audit USING btree (waktu DESC);


--
-- Name: idx_log_login_username_waktu; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_log_login_username_waktu ON public.log_login USING btree (username_dicoba, waktu DESC);


--
-- Name: idx_mutasi_status_tanggal; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_mutasi_status_tanggal ON public.mutasi_siswa USING btree (status, tgl_pengajuan);


--
-- Name: idx_pengajuan_status; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pengajuan_status ON public.pengajuan_perubahan USING btree (status_pengajuan, tgl_pengajuan);


--
-- Name: idx_pengguna_role; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_pengguna_role ON public.pengguna USING btree (id_role);


--
-- Name: idx_siswa_nama; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_siswa_nama ON public.siswa USING btree (nama_lengkap);


--
-- Name: idx_verifikasi_pendaftaran_tanggal; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_verifikasi_pendaftaran_tanggal ON public.verifikasi_pendaftaran USING btree (id_pendaftaran, tgl_verifikasi);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: uq_semester_aktif; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_semester_aktif ON public.semester USING btree (is_aktif) WHERE (is_aktif = true);


--
-- Name: uq_tahun_ajaran_aktif; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_tahun_ajaran_aktif ON public.tahun_ajaran USING btree (is_aktif) WHERE (is_aktif = true);


--
-- Name: pengajuan_perubahan trg_audit_pengajuan; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_audit_pengajuan AFTER INSERT OR DELETE OR UPDATE ON public.pengajuan_perubahan FOR EACH ROW EXECUTE FUNCTION public.fn_audit_trail();


--
-- Name: pengguna trg_audit_pengguna; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_audit_pengguna AFTER INSERT OR DELETE OR UPDATE ON public.pengguna FOR EACH ROW EXECUTE FUNCTION public.fn_audit_trail();


--
-- Name: siswa trg_audit_siswa; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_audit_siswa AFTER INSERT OR DELETE OR UPDATE ON public.siswa FOR EACH ROW EXECUTE FUNCTION public.fn_audit_trail();


--
-- Name: alamat_siswa alamat_siswa_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alamat_siswa
    ADD CONSTRAINT alamat_siswa_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE CASCADE;


--
-- Name: catatan_wali_kelas catatan_wali_kelas_id_guru_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.catatan_wali_kelas
    ADD CONSTRAINT catatan_wali_kelas_id_guru_fkey FOREIGN KEY (id_guru) REFERENCES public.guru(id_guru) ON DELETE RESTRICT;


--
-- Name: catatan_wali_kelas catatan_wali_kelas_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.catatan_wali_kelas
    ADD CONSTRAINT catatan_wali_kelas_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE RESTRICT;


--
-- Name: catatan_wali_kelas catatan_wali_kelas_id_tahun_ajaran_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.catatan_wali_kelas
    ADD CONSTRAINT catatan_wali_kelas_id_tahun_ajaran_fkey FOREIGN KEY (id_tahun_ajaran) REFERENCES public.tahun_ajaran(id_tahun_ajaran) ON DELETE RESTRICT;


--
-- Name: cetak_rapor cetak_rapor_id_pencetak_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cetak_rapor
    ADD CONSTRAINT cetak_rapor_id_pencetak_fkey FOREIGN KEY (id_pencetak) REFERENCES public.pengguna(id_pengguna) ON DELETE RESTRICT;


--
-- Name: cetak_rapor cetak_rapor_id_rapor_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cetak_rapor
    ADD CONSTRAINT cetak_rapor_id_rapor_fkey FOREIGN KEY (id_rapor) REFERENCES public.rapor(id_rapor) ON DELETE RESTRICT;


--
-- Name: detail_absensi detail_absensi_id_sesi_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_absensi
    ADD CONSTRAINT detail_absensi_id_sesi_fkey FOREIGN KEY (id_sesi) REFERENCES public.sesi_absensi(id_sesi) ON DELETE CASCADE;


--
-- Name: detail_absensi detail_absensi_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_absensi
    ADD CONSTRAINT detail_absensi_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE RESTRICT;


--
-- Name: detail_absensi detail_absensi_id_status_hadir_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_absensi
    ADD CONSTRAINT detail_absensi_id_status_hadir_fkey FOREIGN KEY (id_status_hadir) REFERENCES public.status_kehadiran(id_status_hadir) ON DELETE RESTRICT;


--
-- Name: detail_pengajuan detail_pengajuan_id_pengajuan_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_pengajuan
    ADD CONSTRAINT detail_pengajuan_id_pengajuan_fkey FOREIGN KEY (id_pengajuan) REFERENCES public.pengajuan_perubahan(id_pengajuan) ON DELETE CASCADE;


--
-- Name: detail_rapor detail_rapor_id_mapel_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_rapor
    ADD CONSTRAINT detail_rapor_id_mapel_fkey FOREIGN KEY (id_mapel) REFERENCES public.mata_pelajaran(id_mata_pelajaran) ON DELETE RESTRICT;


--
-- Name: detail_rapor detail_rapor_id_predikat_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_rapor
    ADD CONSTRAINT detail_rapor_id_predikat_fkey FOREIGN KEY (id_predikat) REFERENCES public.skala_predikat(id_predikat) ON DELETE SET NULL;


--
-- Name: detail_rapor detail_rapor_id_rapor_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_rapor
    ADD CONSTRAINT detail_rapor_id_rapor_fkey FOREIGN KEY (id_rapor) REFERENCES public.rapor(id_rapor) ON DELETE CASCADE;


--
-- Name: detail_verifikasi detail_verifikasi_id_item_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_verifikasi
    ADD CONSTRAINT detail_verifikasi_id_item_fkey FOREIGN KEY (id_item) REFERENCES public.ref_checklist_item(id_item) ON DELETE RESTRICT;


--
-- Name: detail_verifikasi detail_verifikasi_id_verifikasi_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detail_verifikasi
    ADD CONSTRAINT detail_verifikasi_id_verifikasi_fkey FOREIGN KEY (id_verifikasi) REFERENCES public.verifikasi_pendaftaran(id_verifikasi) ON DELETE CASCADE;


--
-- Name: dokumen_siswa dokumen_siswa_id_pendaftaran_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dokumen_siswa
    ADD CONSTRAINT dokumen_siswa_id_pendaftaran_fkey FOREIGN KEY (id_pendaftaran) REFERENCES public.pendaftaran_ppdb(id_pendaftaran) ON DELETE CASCADE;


--
-- Name: dokumen_siswa dokumen_siswa_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.dokumen_siswa
    ADD CONSTRAINT dokumen_siswa_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE CASCADE;


--
-- Name: ekspor_laporan ekspor_laporan_id_kelas_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ekspor_laporan
    ADD CONSTRAINT ekspor_laporan_id_kelas_fkey FOREIGN KEY (id_kelas) REFERENCES public.kelas(id_kelas) ON DELETE SET NULL;


--
-- Name: ekspor_laporan ekspor_laporan_id_pengguna_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ekspor_laporan
    ADD CONSTRAINT ekspor_laporan_id_pengguna_fkey FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna) ON DELETE RESTRICT;


--
-- Name: ekspor_laporan ekspor_laporan_id_tahun_ajaran_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ekspor_laporan
    ADD CONSTRAINT ekspor_laporan_id_tahun_ajaran_fkey FOREIGN KEY (id_tahun_ajaran) REFERENCES public.tahun_ajaran(id_tahun_ajaran) ON DELETE SET NULL;


--
-- Name: kelas fk_kelas_tahun_ajaran; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kelas
    ADD CONSTRAINT fk_kelas_tahun_ajaran FOREIGN KEY (id_tahun_ajaran) REFERENCES public.tahun_ajaran(id_tahun_ajaran) ON DELETE SET NULL;


--
-- Name: tingkat fk_tingkat_jenjang; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tingkat
    ADD CONSTRAINT fk_tingkat_jenjang FOREIGN KEY (id_jenjang) REFERENCES public.jenjang(id_jenjang) ON DELETE SET NULL;


--
-- Name: guru guru_id_pengguna_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.guru
    ADD CONSTRAINT guru_id_pengguna_fkey FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: izin_siswa izin_siswa_id_dokumen_bukti_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.izin_siswa
    ADD CONSTRAINT izin_siswa_id_dokumen_bukti_fkey FOREIGN KEY (id_dokumen_bukti) REFERENCES public.dokumen_siswa(id_dokumen) ON DELETE SET NULL;


--
-- Name: izin_siswa izin_siswa_id_penyetuju_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.izin_siswa
    ADD CONSTRAINT izin_siswa_id_penyetuju_fkey FOREIGN KEY (id_penyetuju) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: izin_siswa izin_siswa_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.izin_siswa
    ADD CONSTRAINT izin_siswa_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE RESTRICT;


--
-- Name: izin_siswa izin_siswa_id_status_hadir_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.izin_siswa
    ADD CONSTRAINT izin_siswa_id_status_hadir_fkey FOREIGN KEY (id_status_hadir) REFERENCES public.status_kehadiran(id_status_hadir) ON DELETE RESTRICT;


--
-- Name: jadwal_pelajaran jadwal_pelajaran_id_guru_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jadwal_pelajaran
    ADD CONSTRAINT jadwal_pelajaran_id_guru_fkey FOREIGN KEY (id_guru) REFERENCES public.guru(id_guru) ON DELETE RESTRICT;


--
-- Name: jadwal_pelajaran jadwal_pelajaran_id_kelas_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jadwal_pelajaran
    ADD CONSTRAINT jadwal_pelajaran_id_kelas_fkey FOREIGN KEY (id_kelas) REFERENCES public.kelas(id_kelas) ON DELETE RESTRICT;


--
-- Name: jadwal_pelajaran jadwal_pelajaran_id_mata_pelajaran_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jadwal_pelajaran
    ADD CONSTRAINT jadwal_pelajaran_id_mata_pelajaran_fkey FOREIGN KEY (id_mata_pelajaran) REFERENCES public.mata_pelajaran(id_mata_pelajaran) ON DELETE RESTRICT;


--
-- Name: jadwal_pelajaran jadwal_pelajaran_id_semester_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jadwal_pelajaran
    ADD CONSTRAINT jadwal_pelajaran_id_semester_fkey FOREIGN KEY (id_semester) REFERENCES public.semester(id_semester) ON DELETE RESTRICT;


--
-- Name: keanggotaan_kelas keanggotaan_kelas_id_kelas_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.keanggotaan_kelas
    ADD CONSTRAINT keanggotaan_kelas_id_kelas_fkey FOREIGN KEY (id_kelas) REFERENCES public.kelas(id_kelas) ON DELETE RESTRICT;


--
-- Name: keanggotaan_kelas keanggotaan_kelas_id_semester_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.keanggotaan_kelas
    ADD CONSTRAINT keanggotaan_kelas_id_semester_fkey FOREIGN KEY (id_semester) REFERENCES public.semester(id_semester) ON DELETE RESTRICT;


--
-- Name: keanggotaan_kelas keanggotaan_kelas_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.keanggotaan_kelas
    ADD CONSTRAINT keanggotaan_kelas_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE RESTRICT;


--
-- Name: kecamatan kecamatan_id_kota_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kecamatan
    ADD CONSTRAINT kecamatan_id_kota_fkey FOREIGN KEY (id_kota) REFERENCES public.kota(id_kota) ON DELETE RESTRICT;


--
-- Name: kelas kelas_id_tingkat_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kelas
    ADD CONSTRAINT kelas_id_tingkat_fkey FOREIGN KEY (id_tingkat) REFERENCES public.tingkat(id_tingkat) ON DELETE RESTRICT;


--
-- Name: kelurahan kelurahan_id_kecamatan_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kelurahan
    ADD CONSTRAINT kelurahan_id_kecamatan_fkey FOREIGN KEY (id_kecamatan) REFERENCES public.kecamatan(id_kecamatan) ON DELETE RESTRICT;


--
-- Name: konfirmasi_data konfirmasi_data_id_periode_update_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.konfirmasi_data
    ADD CONSTRAINT konfirmasi_data_id_periode_update_fkey FOREIGN KEY (id_periode_update) REFERENCES public.periode_update(id_periode_update) ON DELETE RESTRICT;


--
-- Name: konfirmasi_data konfirmasi_data_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.konfirmasi_data
    ADD CONSTRAINT konfirmasi_data_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE RESTRICT;


--
-- Name: kota kota_id_provinsi_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kota
    ADD CONSTRAINT kota_id_provinsi_fkey FOREIGN KEY (id_provinsi) REFERENCES public.provinsi(id_provinsi) ON DELETE RESTRICT;


--
-- Name: log_audit log_audit_id_pengguna_pengubah_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.log_audit
    ADD CONSTRAINT log_audit_id_pengguna_pengubah_fkey FOREIGN KEY (id_pengguna_pengubah) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: log_login log_login_id_pengguna_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.log_login
    ADD CONSTRAINT log_login_id_pengguna_fkey FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: mutasi_siswa mutasi_siswa_id_pengaju_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mutasi_siswa
    ADD CONSTRAINT mutasi_siswa_id_pengaju_fkey FOREIGN KEY (id_pengaju) REFERENCES public.pengguna(id_pengguna) ON DELETE RESTRICT;


--
-- Name: mutasi_siswa mutasi_siswa_id_penyetuju_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mutasi_siswa
    ADD CONSTRAINT mutasi_siswa_id_penyetuju_fkey FOREIGN KEY (id_penyetuju) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: mutasi_siswa mutasi_siswa_id_sekolah_tujuan_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mutasi_siswa
    ADD CONSTRAINT mutasi_siswa_id_sekolah_tujuan_fkey FOREIGN KEY (id_sekolah_tujuan) REFERENCES public.sekolah(id_sekolah) ON DELETE SET NULL;


--
-- Name: mutasi_siswa mutasi_siswa_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mutasi_siswa
    ADD CONSTRAINT mutasi_siswa_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE RESTRICT;


--
-- Name: nilai_siswa nilai_siswa_id_anggota_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nilai_siswa
    ADD CONSTRAINT nilai_siswa_id_anggota_fkey FOREIGN KEY (id_anggota) REFERENCES public.keanggotaan_kelas(id_keanggotaan) ON DELETE RESTRICT;


--
-- Name: nilai_siswa nilai_siswa_id_komponen_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nilai_siswa
    ADD CONSTRAINT nilai_siswa_id_komponen_fkey FOREIGN KEY (id_komponen) REFERENCES public.komponen_penilaian(id_komponen) ON DELETE RESTRICT;


--
-- Name: nilai_siswa nilai_siswa_id_mapel_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nilai_siswa
    ADD CONSTRAINT nilai_siswa_id_mapel_fkey FOREIGN KEY (id_mapel) REFERENCES public.mata_pelajaran(id_mata_pelajaran) ON DELETE RESTRICT;


--
-- Name: nilai_siswa nilai_siswa_id_penginput_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nilai_siswa
    ADD CONSTRAINT nilai_siswa_id_penginput_fkey FOREIGN KEY (id_penginput) REFERENCES public.pengguna(id_pengguna) ON DELETE RESTRICT;


--
-- Name: nilai_siswa nilai_siswa_id_semester_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.nilai_siswa
    ADD CONSTRAINT nilai_siswa_id_semester_fkey FOREIGN KEY (id_semester) REFERENCES public.semester(id_semester) ON DELETE RESTRICT;


--
-- Name: notifikasi notifikasi_id_pengguna_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifikasi
    ADD CONSTRAINT notifikasi_id_pengguna_fkey FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna) ON DELETE CASCADE;


--
-- Name: orang_tua_wali orang_tua_wali_id_pekerjaan_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orang_tua_wali
    ADD CONSTRAINT orang_tua_wali_id_pekerjaan_fkey FOREIGN KEY (id_pekerjaan) REFERENCES public.pekerjaan(id_pekerjaan) ON DELETE SET NULL;


--
-- Name: orang_tua_wali orang_tua_wali_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orang_tua_wali
    ADD CONSTRAINT orang_tua_wali_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE CASCADE;


--
-- Name: pendaftaran_ppdb pendaftaran_ppdb_id_pengguna_pemeriksa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pendaftaran_ppdb
    ADD CONSTRAINT pendaftaran_ppdb_id_pengguna_pemeriksa_fkey FOREIGN KEY (id_pengguna_pemeriksa) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: pengajuan_perubahan pengajuan_perubahan_id_pemeriksa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengajuan_perubahan
    ADD CONSTRAINT pengajuan_perubahan_id_pemeriksa_fkey FOREIGN KEY (id_pemeriksa) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: pengajuan_perubahan pengajuan_perubahan_id_periode_update_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengajuan_perubahan
    ADD CONSTRAINT pengajuan_perubahan_id_periode_update_fkey FOREIGN KEY (id_periode_update) REFERENCES public.periode_update(id_periode_update) ON DELETE RESTRICT;


--
-- Name: pengajuan_perubahan pengajuan_perubahan_id_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengajuan_perubahan
    ADD CONSTRAINT pengajuan_perubahan_id_siswa_fkey FOREIGN KEY (id_siswa) REFERENCES public.siswa(id_siswa) ON DELETE RESTRICT;


--
-- Name: pengaturan_sistem pengaturan_sistem_id_pengguna_pengubah_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengaturan_sistem
    ADD CONSTRAINT pengaturan_sistem_id_pengguna_pengubah_fkey FOREIGN KEY (id_pengguna_pengubah) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: pengguna pengguna_id_role_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pengguna
    ADD CONSTRAINT pengguna_id_role_fkey FOREIGN KEY (id_role) REFERENCES public.role(id_role) ON DELETE RESTRICT;


--
-- Name: penugasan_wali_kelas penugasan_wali_kelas_id_guru_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penugasan_wali_kelas
    ADD CONSTRAINT penugasan_wali_kelas_id_guru_fkey FOREIGN KEY (id_guru) REFERENCES public.guru(id_guru) ON DELETE RESTRICT;


--
-- Name: penugasan_wali_kelas penugasan_wali_kelas_id_kelas_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penugasan_wali_kelas
    ADD CONSTRAINT penugasan_wali_kelas_id_kelas_fkey FOREIGN KEY (id_kelas) REFERENCES public.kelas(id_kelas) ON DELETE RESTRICT;


--
-- Name: penugasan_wali_kelas penugasan_wali_kelas_id_semester_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penugasan_wali_kelas
    ADD CONSTRAINT penugasan_wali_kelas_id_semester_fkey FOREIGN KEY (id_semester) REFERENCES public.semester(id_semester) ON DELETE RESTRICT;


--
-- Name: periode_update periode_update_dibuat_oleh_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.periode_update
    ADD CONSTRAINT periode_update_dibuat_oleh_fkey FOREIGN KEY (dibuat_oleh) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: periode_update periode_update_id_tahun_ajaran_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.periode_update
    ADD CONSTRAINT periode_update_id_tahun_ajaran_fkey FOREIGN KEY (id_tahun_ajaran) REFERENCES public.tahun_ajaran(id_tahun_ajaran) ON DELETE RESTRICT;


--
-- Name: rapor rapor_id_anggota_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rapor
    ADD CONSTRAINT rapor_id_anggota_fkey FOREIGN KEY (id_anggota) REFERENCES public.keanggotaan_kelas(id_keanggotaan) ON DELETE RESTRICT;


--
-- Name: rapor rapor_id_penyusun_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rapor
    ADD CONSTRAINT rapor_id_penyusun_fkey FOREIGN KEY (id_penyusun) REFERENCES public.pengguna(id_pengguna) ON DELETE RESTRICT;


--
-- Name: rapor rapor_id_semester_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rapor
    ADD CONSTRAINT rapor_id_semester_fkey FOREIGN KEY (id_semester) REFERENCES public.semester(id_semester) ON DELETE RESTRICT;


--
-- Name: ref_checklist_item ref_checklist_item_id_jenis_dokumen_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ref_checklist_item
    ADD CONSTRAINT ref_checklist_item_id_jenis_dokumen_fkey FOREIGN KEY (id_jenis_dokumen) REFERENCES public.jenis_dokumen(id_jenis_dokumen) ON DELETE CASCADE;


--
-- Name: ref_field_data ref_field_data_id_jenis_dokumen_bukti_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ref_field_data
    ADD CONSTRAINT ref_field_data_id_jenis_dokumen_bukti_fkey FOREIGN KEY (id_jenis_dokumen_bukti) REFERENCES public.jenis_dokumen(id_jenis_dokumen) ON DELETE SET NULL;


--
-- Name: role_hak_akses role_hak_akses_id_hak_akses_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_hak_akses
    ADD CONSTRAINT role_hak_akses_id_hak_akses_fkey FOREIGN KEY (id_hak_akses) REFERENCES public.hak_akses(id_hak_akses) ON DELETE CASCADE;


--
-- Name: role_hak_akses role_hak_akses_id_role_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_hak_akses
    ADD CONSTRAINT role_hak_akses_id_role_fkey FOREIGN KEY (id_role) REFERENCES public.role(id_role) ON DELETE CASCADE;


--
-- Name: semester semester_id_tahun_ajaran_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.semester
    ADD CONSTRAINT semester_id_tahun_ajaran_fkey FOREIGN KEY (id_tahun_ajaran) REFERENCES public.tahun_ajaran(id_tahun_ajaran) ON DELETE RESTRICT;


--
-- Name: sesi_absensi sesi_absensi_id_guru_pencatat_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sesi_absensi
    ADD CONSTRAINT sesi_absensi_id_guru_pencatat_fkey FOREIGN KEY (id_guru_pencatat) REFERENCES public.guru(id_guru) ON DELETE RESTRICT;


--
-- Name: sesi_absensi sesi_absensi_id_jadwal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sesi_absensi
    ADD CONSTRAINT sesi_absensi_id_jadwal_fkey FOREIGN KEY (id_jadwal) REFERENCES public.jadwal_pelajaran(id_jadwal) ON DELETE RESTRICT;


--
-- Name: siswa siswa_id_agama_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siswa
    ADD CONSTRAINT siswa_id_agama_fkey FOREIGN KEY (id_agama) REFERENCES public.agama(id_agama) ON DELETE SET NULL;


--
-- Name: siswa siswa_id_pengguna_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siswa
    ADD CONSTRAINT siswa_id_pengguna_fkey FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: siswa siswa_id_sekolah_asal_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siswa
    ADD CONSTRAINT siswa_id_sekolah_asal_fkey FOREIGN KEY (id_sekolah_asal) REFERENCES public.sekolah(id_sekolah) ON DELETE SET NULL;


--
-- Name: siswa siswa_id_status_siswa_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siswa
    ADD CONSTRAINT siswa_id_status_siswa_fkey FOREIGN KEY (id_status_siswa) REFERENCES public.status_siswa(id_status_siswa) ON DELETE RESTRICT;


--
-- Name: staf staf_id_pengguna_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.staf
    ADD CONSTRAINT staf_id_pengguna_fkey FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna) ON DELETE SET NULL;


--
-- Name: verifikasi_pendaftaran verifikasi_pendaftaran_id_alasan_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.verifikasi_pendaftaran
    ADD CONSTRAINT verifikasi_pendaftaran_id_alasan_fkey FOREIGN KEY (id_alasan) REFERENCES public.ref_alasan_pengembalian(id_alasan) ON DELETE SET NULL;


--
-- Name: verifikasi_pendaftaran verifikasi_pendaftaran_id_pendaftaran_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.verifikasi_pendaftaran
    ADD CONSTRAINT verifikasi_pendaftaran_id_pendaftaran_fkey FOREIGN KEY (id_pendaftaran) REFERENCES public.pendaftaran_ppdb(id_pendaftaran) ON DELETE CASCADE;


--
-- Name: verifikasi_pendaftaran verifikasi_pendaftaran_id_petugas_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.verifikasi_pendaftaran
    ADD CONSTRAINT verifikasi_pendaftaran_id_petugas_fkey FOREIGN KEY (id_petugas) REFERENCES public.pengguna(id_pengguna) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict eJEEWp8oGgHp9jI4Tr1F6UtUvussCZmpElfmy2PXBn8bIAwGZ5OXHUrxTKFxNLg

