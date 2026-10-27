<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\KomponenPenilaian;
use App\Models\MataPelajaran;
use App\Models\Semester;
use App\Models\SkalaPredikat;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DFD 2.1 Konfigurasi Akademik (L2: 2.1.1 - 2.1.4).
 * Semua aturan "cek tumpang tindih", "cek duplikat", dan bobot ada di sini
 * supaya controller tetap tipis.
 */
class MasterAkademikService
{
    /* ---------- 2.1.1 Tahun Ajaran & Semester ---------- */

    public function simpanTahunAjaran(array $d, ?TahunAjaran $ta = null): TahunAjaran
    {
        // "Data lama (cek tumpang tindih)"
        $bentrok = TahunAjaran::query()
            ->when($ta, fn ($q) => $q->where('id_tahun_ajaran', '!=', $ta->id_tahun_ajaran))
            ->where('tanggal_mulai', '<=', $d['tanggal_selesai'])
            ->where('tanggal_selesai', '>=', $d['tanggal_mulai'])
            ->first();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => "Rentang tanggal bertumpang tindih dengan tahun ajaran {$bentrok->nama_tahun_ajaran}.",
            ]);
        }

        return DB::transaction(function () use ($d, $ta) {
            // Index uq_tahun_ajaran_aktif hanya mengizinkan satu baris aktif.
            if (!empty($d['is_aktif'])) {
                TahunAjaran::where('is_aktif', true)
                    ->when($ta, fn ($q) => $q->where('id_tahun_ajaran', '!=', $ta->id_tahun_ajaran))
                    ->get()->each->update(['is_aktif' => false]); // each->update agar observer audit jalan
            }

            return $ta ? tap($ta)->update($d) : TahunAjaran::create($d);
        });
    }

    public function simpanSemester(TahunAjaran $ta, array $d, ?Semester $sem = null): Semester
    {
        $mulai   = $d['tanggal_mulai']   ?? null;
        $selesai = $d['tanggal_selesai'] ?? null;

        if (($mulai && $mulai < $ta->tanggal_mulai->toDateString())
            || ($selesai && $selesai > $ta->tanggal_selesai->toDateString())) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Tanggal semester harus berada di dalam rentang tahun ajaran.',
            ]);
        }

        if (!empty($d['is_aktif']) && !$ta->is_aktif) {
            throw ValidationException::withMessages([
                'is_aktif' => 'Semester hanya bisa diaktifkan pada tahun ajaran yang aktif.',
            ]);
        }

        return DB::transaction(function () use ($ta, $d, $sem) {
            if (!empty($d['is_aktif'])) {
                Semester::where('is_aktif', true)
                    ->when($sem, fn ($q) => $q->where('id_semester', '!=', $sem->id_semester))
                    ->get()->each->update(['is_aktif' => false]);
            }

            return $sem
                ? tap($sem)->update($d)
                : Semester::create($d + ['id_tahun_ajaran' => $ta->id_tahun_ajaran]);
        });
    }

    /* ---------- 2.1.4 Komponen & Skala Nilai ---------- */

    public function simpanKomponen(array $d, ?KomponenPenilaian $k = null): KomponenPenilaian
    {
        $totalLain = (float) KomponenPenilaian::query()
            ->when($k, fn ($q) => $q->where('id_komponen', '!=', $k->id_komponen))
            ->sum('bobot_persen');

        if ($totalLain + (float) $d['bobot_persen'] > 100) {
            throw ValidationException::withMessages([
                'bobot_persen' => sprintf('Total bobot melebihi 100%% (komponen lain sudah %.2f%%).', $totalLain),
            ]);
        }

        return $k ? tap($k)->update($d) : KomponenPenilaian::create($d);
    }

    public function ringkasanBobot(): array
    {
        $total = (float) KomponenPenilaian::sum('bobot_persen');

        return ['total_bobot' => $total, 'lengkap' => abs($total - 100) < 0.005];
    }

    public function simpanPredikat(array $d, ?SkalaPredikat $p = null): SkalaPredikat
    {
        $bentrok = SkalaPredikat::query()
            ->when($p, fn ($q) => $q->where('id_predikat', '!=', $p->id_predikat))
            ->where('nilai_min', '<=', $d['nilai_maks'])
            ->where('nilai_maks', '>=', $d['nilai_min'])
            ->first();

        if ($bentrok) {
            throw ValidationException::withMessages([
                'nilai_min' => "Rentang bertumpang tindih dengan predikat {$bentrok->huruf}.",
            ]);
        }

        return $p ? tap($p)->update($d) : SkalaPredikat::create($d);
    }

    /* ---------- Penghapusan aman ---------- */

    /** Tolak hapus jika masih dipakai tabel lain; kembalikan pesan, atau null jika boleh. */
    public function alasanTidakBisaDihapus(string $jenis, int $id): ?string
    {
        $pakai = match ($jenis) {
            'tahun_ajaran' => DB::table('semester')->where('id_tahun_ajaran', $id)->exists()
                || DB::table('kelas')->where('id_tahun_ajaran', $id)->exists(),
            'semester'     => DB::table('jadwal_pelajaran')->where('id_semester', $id)->exists()
                || DB::table('keanggotaan_kelas')->where('id_semester', $id)->exists()
                || DB::table('nilai_siswa')->where('id_semester', $id)->exists(),
            'kelas'        => DB::table('keanggotaan_kelas')->where('id_kelas', $id)->exists()
                || DB::table('jadwal_pelajaran')->where('id_kelas', $id)->exists(),
            'mapel'        => DB::table('jadwal_pelajaran')->where('id_mata_pelajaran', $id)->exists()
                || DB::table('nilai_siswa')->where('id_mapel', $id)->exists(),
            'komponen'     => DB::table('nilai_siswa')->where('id_komponen', $id)->exists(),
            'predikat'     => DB::table('detail_rapor')->where('id_predikat', $id)->exists(),
            default        => false,
        };

        return $pakai ? 'Data sudah dipakai di tabel lain. Nonaktifkan, jangan hapus.' : null;
    }
}
