-- Actual-schema compatible view; `jenis` is a constant because the current PPDB table has no target id_jenjang_tujuan.
CREATE OR REPLACE VIEW vw_antrean_verifikasi AS
SELECT p.id_pendaftaran,p.nama_calon_siswa AS nama_lengkap,p.nisn,'PPDB'::text AS jenis,
 p.status_pendaftaran AS status,p.diajukan_pada AS tgl_daftar
FROM pendaftaran_ppdb p WHERE p.status_pendaftaran IN ('menunggu','perlu_perbaikan');
