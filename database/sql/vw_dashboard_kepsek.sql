-- Partial actual-schema dashboard. Percent completeness and mean verification duration need a school-approved definition.
CREATE OR REPLACE VIEW vw_dashboard_kepsek AS
SELECT (SELECT COUNT(*) FROM siswa s JOIN status_siswa st ON st.id_status_siswa=s.id_status_siswa WHERE st.kode_status='aktif')::bigint AS total_siswa_aktif,
 (SELECT COUNT(*) FROM pendaftaran_ppdb)::bigint AS total_pendaftar,
 (SELECT COUNT(*) FROM pendaftaran_ppdb WHERE status_pendaftaran='diterima')::bigint AS total_disahkan,
 NULL::numeric AS persen_kelengkapan, NULL::numeric AS rata_hari_verifikasi,
 (SELECT COUNT(*) FROM pengajuan_perubahan WHERE status_pengajuan='menunggu')::bigint AS mutasi_menunggu;
