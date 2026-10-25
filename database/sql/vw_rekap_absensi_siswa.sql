-- BLOCKED: dump does not contain sesi_absensi, detail_absensi, status_kehadiran. Apply approved target-schema migration first.
CREATE OR REPLACE VIEW vw_rekap_absensi_siswa AS
SELECT s.id_siswa, s.nama_lengkap, k.id_kelas, k.nama_kelas, j.id_semester,
       COUNT(*) FILTER (WHERE sh.kode = 'H') AS total_hadir,
       COUNT(*) FILTER (WHERE sh.kode = 'S') AS total_sakit,
       COUNT(*) FILTER (WHERE sh.kode = 'I') AS total_izin,
       COUNT(*) FILTER (WHERE sh.kode = 'A') AS total_alpa,
       ROUND(100.0 * COUNT(*) FILTER (WHERE sh.kode = 'H') / NULLIF(COUNT(*),0), 2) AS persen_hadir
FROM detail_absensi da JOIN sesi_absensi sa ON sa.id_sesi=da.id_sesi
JOIN jadwal_pelajaran j ON j.id_jadwal=sa.id_jadwal JOIN kelas k ON k.id_kelas=j.id_kelas
JOIN siswa s ON s.id_siswa=da.id_siswa JOIN status_kehadiran sh ON sh.id_status_hadir=da.id_status_hadir
GROUP BY s.id_siswa,s.nama_lengkap,k.id_kelas,k.nama_kelas,j.id_semester;
