<?php

namespace App\Services;

use App\Models\KomponenPenilaian;
use App\Models\Semester;
use App\Models\SkalaPredikat;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DFD 2.1 Konfigurasi Akademik (L2: 2.1.1 - 2.1.4), dipakai bersama oleh API dan halaman web.
 * Semua aturan "cek tumpang tindih", "cek duplikat", dan bobot ada di sini.
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

            $hasil = $ta ? tap($ta)->update($d) : TahunAjaran::create($d);

            // Semester aktif harus selalu milik tahun ajaran yang aktif.
            if (!empty($d['is_aktif'])) {
                Semester::where('is_aktif', true)->where('id_tahun_ajaran', '!=', $hasil->id_tahun_ajaran)
                    ->get()->each->update(['is_aktif' => false]);
            } elseif (array_key_exists('is_aktif', $d)) {
                Semester::where('is_aktif', true)->where('id_tahun_ajaran', $hasil->id_tahun_ajaran)
                    ->get()->each->update(['is_aktif' => false]);
            }

            return $hasil;
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
        $ada = fn (string $tabel, string $kolom) => DB::table($tabel)->where($kolom, $id)->exists();

        $pakai = match ($jenis) {
            'tahun_ajaran' => $ada('semester', 'id_tahun_ajaran') || $ada('kelas', 'id_tahun_ajaran') || $ada('periode_update', 'id_tahun_ajaran'),
            'semester'     => $ada('jadwal_pelajaran', 'id_semester') || $ada('keanggotaan_kelas', 'id_semester')
                || $ada('penugasan_wali_kelas', 'id_semester') || $ada('nilai_siswa', 'id_semester') || $ada('rapor', 'id_semester'),
            'kelas'        => $ada('keanggotaan_kelas', 'id_kelas') || $ada('jadwal_pelajaran', 'id_kelas') || $ada('penugasan_wali_kelas', 'id_kelas'),
            'mapel'        => $ada('jadwal_pelajaran', 'id_mata_pelajaran') || $ada('nilai_siswa', 'id_mapel') || $ada('detail_rapor', 'id_mapel'),
            'komponen'     => $ada('nilai_siswa', 'id_komponen'),
            'predikat'     => $ada('detail_rapor', 'id_predikat'),
            default        => false,
        };

        return $pakai ? 'Data sudah dipakai di tabel lain. Nonaktifkan, jangan hapus.' : null;
    }
}
