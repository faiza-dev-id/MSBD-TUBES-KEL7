<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Akses tabel penilaian rapor.
 *
 * Semua penulisan memakai SELECT/UPDATE/INSERT biasa. Query Builder Postgres
 * menerjemahkan updateOrInsert menjadi UPDATE ... WHERE ctid IN (SELECT ctid ...),
 * yang memerlukan hak SELECT level tabel. role_wali_kelas hanya memiliki hak
 * SELECT per kolom, sehingga pola ctid harus dihindari.
 */
class RaporService
{
    public function assertSchemaAvailable(): void
    {
        foreach (['nilai_siswa', 'komponen_penilaian', 'skala_predikat', 'rapor', 'detail_rapor', 'cetak_rapor'] as $t) {
            if (! DB::getSchemaBuilder()->hasTable($t)) {
                throw new RuntimeException("Tabel target {$t} belum ada; implementasi final memerlukan schema approval.");
            }
        }
    }

    public function nilaiAkhir(int $anggota, int $mapel, int $semester): array
    {
        $this->assertSchemaAvailable();
        $rows = DB::table('nilai_siswa as n')->join('komponen_penilaian as c', 'c.id_komponen', '=', 'n.id_komponen')->where('n.id_anggota', $anggota)->where('n.id_mapel', $mapel)->where('n.id_semester', $semester)->select('n.nilai', 'c.bobot_persen')->get();
        if ($rows->isEmpty()) {
            throw new RuntimeException('Nilai komponen belum tersedia.');
        } $totalBobot = (float) $rows->sum('bobot_persen');
        if ($totalBobot <= 0.0) {
            throw new RuntimeException('Bobot komponen belum diatur.');
        } $nilai = $rows->sum(fn ($r) => (float) $r->nilai * (float) $r->bobot_persen) / $totalBobot;
        $predikat = DB::table('skala_predikat')->where('nilai_min', '<=', $nilai)->where('nilai_maks', '>=', $nilai)->first();
        if (! $predikat) {
            throw new RuntimeException('Skala predikat tidak ditemukan.');
        }

        return ['nilai_akhir' => round($nilai, 2), 'id_predikat' => $predikat->id_predikat, 'huruf' => $predikat->huruf];
    }

    /** Simpan satu nilai komponen tanpa memakai pola ctid dari updateOrInsert. */
    public function simpanNilaiSiswa(
        int $idAnggota,
        int $idMapel,
        int $idSemester,
        int $idKomponen,
        int $idPenginput,
        float $nilai
    ): void {
        $nilai = round($nilai, 2);
        $kunci = [
            'id_anggota' => $idAnggota,
            'id_mapel' => $idMapel,
            'id_semester' => $idSemester,
            'id_komponen' => $idKomponen,
        ];

        $idNilai = DB::table('nilai_siswa')->where($kunci)->value('id_nilai');

        if ($idNilai !== null) {
            DB::table('nilai_siswa')
                ->where('id_nilai', (int) $idNilai)
                ->update([
                    'id_penginput' => $idPenginput,
                    'nilai' => $nilai,
                    'tgl_input' => now(),
                ]);

            return;
        }

        DB::table('nilai_siswa')->insert($kunci + [
            'id_penginput' => $idPenginput,
            'nilai' => $nilai,
            'tgl_input' => now(),
        ]);
    }

    /** Hapus nilai komponen yang kosong pada satu siswa/mapel/semester. */
    public function hapusNilaiSiswa(int $idAnggota, int $idMapel, int $idSemester, array $idKomponen): void
    {
        if ($idKomponen === []) {
            return;
        }

        DB::table('nilai_siswa')
            ->where('id_anggota', $idAnggota)
            ->where('id_mapel', $idMapel)
            ->where('id_semester', $idSemester)
            ->whereIn('id_komponen', $idKomponen)
            ->delete();
    }

    /** Simpan atau perbarui satu baris detail rapor tanpa pola ctid. */
    public function simpanDetailRapor(
        int $idRapor,
        int $idMapel,
        ?int $idPredikat,
        float $nilaiAkhir
    ): void {
        $nilaiAkhir = round($nilaiAkhir, 2);
        $idDetail = DB::table('detail_rapor')
            ->where('id_rapor', $idRapor)
            ->where('id_mapel', $idMapel)
            ->value('id_detail_rapor');

        if ($idDetail !== null) {
            DB::table('detail_rapor')
                ->where('id_detail_rapor', (int) $idDetail)
                ->update([
                    'id_predikat' => $idPredikat,
                    'nilai_akhir' => $nilaiAkhir,
                ]);

            return;
        }

        DB::table('detail_rapor')->insert([
            'id_rapor' => $idRapor,
            'id_mapel' => $idMapel,
            'id_predikat' => $idPredikat,
            'nilai_akhir' => $nilaiAkhir,
        ]);
    }

    /** Hapus detail rapor untuk satu rapor, atau hanya satu mapelnya. */
    public function hapusDetailRapor(int $idRapor, ?int $idMapel = null): void
    {
        $query = DB::table('detail_rapor')->where('id_rapor', $idRapor);

        if ($idMapel !== null) {
            $query->where('id_mapel', $idMapel);
        }

        $query->delete();
    }

    /** Rata-rata nilai akhir yang sudah tersimpan pada satu rapor. */
    public function rataRataRapor(int $idRapor): ?float
    {
        $rataRata = DB::table('detail_rapor')
            ->where('id_rapor', $idRapor)
            ->avg('nilai_akhir');

        return $rataRata !== null ? round((float) $rataRata, 2) : null;
    }
}
