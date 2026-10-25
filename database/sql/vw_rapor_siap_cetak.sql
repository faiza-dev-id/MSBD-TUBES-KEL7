-- BLOCKED: dump does not contain rapor/detail_rapor/cetak_rapor.
CREATE OR REPLACE VIEW vw_rapor_siap_cetak AS
SELECT r.id_rapor, s.nisn, s.nama_lengkap, k.nama_kelas, sm.nama_semester, r.rata_rata, r.peringkat, r.status_rapor,
       EXISTS (SELECT 1 FROM cetak_rapor cr WHERE cr.id_rapor=r.id_rapor) AS sudah_dicetak
FROM rapor r JOIN keanggotaan_kelas a ON a.id_keanggotaan=r.id_anggota
JOIN siswa s ON s.id_siswa=a.id_siswa JOIN kelas k ON k.id_kelas=a.id_kelas
JOIN semester sm ON sm.id_semester=r.id_semester;
