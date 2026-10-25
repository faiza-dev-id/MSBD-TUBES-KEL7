-- BLOCKED: target schema needs tahun_ajaran relation on kelas and target field mapping/confirmation policy.
CREATE OR REPLACE VIEW vw_kelengkapan_data_kelas AS
SELECT k.id_kelas,k.nama_kelas,COUNT(DISTINCT kk.id_siswa) AS total_siswa,
 COUNT(DISTINCT kk.id_siswa) FILTER (WHERE cd.status='dikonfirmasi') AS sudah_konfirmasi,
 COUNT(DISTINCT pp.id_siswa) FILTER (WHERE pp.status_pengajuan='menunggu') AS mengajukan_perubahan,
 GREATEST(COUNT(DISTINCT kk.id_siswa)-COUNT(DISTINCT kk.id_siswa) FILTER (WHERE cd.status='dikonfirmasi'),0) AS belum_respons
FROM kelas k LEFT JOIN keanggotaan_kelas kk ON kk.id_kelas=k.id_kelas AND kk.status='aktif'
LEFT JOIN konfirmasi_data cd ON cd.id_siswa=kk.id_siswa
LEFT JOIN pengajuan_perubahan pp ON pp.id_siswa=kk.id_siswa
GROUP BY k.id_kelas,k.nama_kelas;
