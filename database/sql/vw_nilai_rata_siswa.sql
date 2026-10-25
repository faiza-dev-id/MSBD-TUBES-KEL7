-- Requires target-extension migration. Weighted score per member/subject/semester.
CREATE OR REPLACE VIEW vw_nilai_rata_siswa AS
WITH weighted AS (
 SELECT n.id_anggota, n.id_semester, n.id_mapel,
        SUM(n.nilai * c.bobot_persen / 100.0) AS nilai_akhir
 FROM nilai_siswa n JOIN komponen_penilaian c ON c.id_komponen=n.id_komponen
 GROUP BY n.id_anggota,n.id_semester,n.id_mapel
)
SELECT w.id_anggota,s.nama_lengkap,k.nama_kelas,w.id_semester,w.id_mapel,mp.nama_mapel,w.nilai_akhir,sp.huruf
FROM weighted w JOIN keanggotaan_kelas a ON a.id_keanggotaan=w.id_anggota
JOIN siswa s ON s.id_siswa=a.id_siswa JOIN kelas k ON k.id_kelas=a.id_kelas
JOIN mata_pelajaran mp ON mp.id_mata_pelajaran=w.id_mapel
LEFT JOIN skala_predikat sp ON w.nilai_akhir BETWEEN sp.nilai_min AND sp.nilai_maks;
