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
            if (! empty($d['is_aktif'])) {
                TahunAjaran::where('is_aktif', true)
                    ->when($ta, fn ($q) => $q->where('id_tahun_ajaran', '!=', $ta->id_tahun_ajaran))
                    ->get()->each->update(['is_aktif' => false]); // each->update agar observer audit jalan
            }

            $hasil = $ta ? tap($ta)->update($d) : TahunAjaran::create($d);

            // Semester aktif harus selalu milik tahun ajaran yang aktif.
            if (! empty($d['is_aktif'])) {
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
        $mulai = $d['tanggal_mulai'] ?? null;
        $selesai = $d['tanggal_selesai'] ?? null;

        if (($mulai && $mulai < $ta->tanggal_mulai->toDateString())
            || ($selesai && $selesai > $ta->tanggal_selesai->toDateString())) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Tanggal semester harus berada di dalam rentang tahun ajaran.',
            ]);
        }

        if (! empty($d['is_aktif']) && ! $ta->is_aktif) {
            throw ValidationException::withMessages([
                'is_aktif' => 'Semester hanya bisa diaktifkan pada tahun ajaran yang aktif.',
            ]);
        }

        return DB::transaction(function () use ($ta, $d, $sem) {
            if (! empty($d['is_aktif'])) {
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
        return DB::transaction(function () use ($d, $k) {
            $this->kunciKonfigurasi('komponen-penilaian');
            $totalLain = (float) KomponenPenilaian::query()
                ->when($k, fn ($q) => $q->where('id_komponen', '!=', $k->id_komponen))
                ->sum('bobot_persen');

            if ($totalLain + (float) $d['bobot_persen'] > 100) {
                throw ValidationException::withMessages([
                    'bobot_persen' => sprintf('Total bobot melebihi 100%% (komponen lain sudah %.2f%%).', $totalLain),
                ]);
            }

            return $k ? tap($k)->update($d) : KomponenPenilaian::create($d);
        });
    }

    public function ringkasanBobot(): array
    {
        $total = (float) KomponenPenilaian::sum('bobot_persen');

        return ['total_bobot' => $total, 'lengkap' => abs($total - 100) < 0.005];
    }

    public function simpanPredikat(array $d, ?SkalaPredikat $p = null): SkalaPredikat
    {
        return DB::transaction(function () use ($d, $p) {
            $this->kunciKonfigurasi('skala-predikat');
            $rentang = SkalaPredikat::query()
                ->when($p, fn ($q) => $q->where('id_predikat', '!=', $p->id_predikat))
                ->get()
                ->map(fn (SkalaPredikat $item) => $item->only(['huruf', 'nilai_min', 'nilai_maks']))
                ->all();
            $rentang[] = $d;
            $this->validasiRentangLengkap($rentang);

            return $p ? tap($p)->update($d) : SkalaPredikat::create($d);
        });
    }

    public function simpanRentangPredikat(array $input): void
    {
        DB::transaction(function () use ($input) {
            $this->kunciKonfigurasi('skala-predikat');
            $existing = SkalaPredikat::query()->lockForUpdate()->get()->keyBy('id_predikat');
            $rows = [];
            $ids = [];

            foreach ($input as $row) {
                $id = isset($row['id_predikat']) ? (int) $row['id_predikat'] : null;
                if ($id !== null && ! $existing->has($id)) {
                    throw ValidationException::withMessages(['rentang' => 'Predikat yang dipilih tidak ditemukan. Muat ulang halaman.']);
                }
                if ($id !== null) {
                    $ids[] = $id;
                }
                if (! empty($row['hapus'])) {
                    continue;
                }

                if ($id === null && collect(['huruf', 'nilai_min', 'nilai_maks', 'deskripsi'])
                    ->every(fn ($key) => ! isset($row[$key]) || trim((string) $row[$key]) === '')) {
                    continue;
                }

                if (empty($row['huruf']) || ! isset($row['nilai_min'], $row['nilai_maks'])
                    || ! is_numeric($row['nilai_min']) || ! is_numeric($row['nilai_maks'])) {
                    throw ValidationException::withMessages(['rentang' => 'Setiap skala harus memiliki predikat serta nilai minimum dan maksimum.']);
                }

                $rows[] = [
                    'id_predikat' => $id,
                    'huruf' => trim($row['huruf']),
                    'nilai_min' => $row['nilai_min'],
                    'nilai_maks' => $row['nilai_maks'],
                    'deskripsi' => $row['deskripsi'] ?? null,
                    'hapus' => false,
                ];
            }

            if (count($ids) !== count(array_unique($ids))
                || array_diff($existing->keys()->all(), $ids) !== []) {
                throw ValidationException::withMessages(['rentang' => 'Daftar skala berubah. Muat ulang halaman sebelum menyimpan.']);
            }

            foreach ($input as $row) {
                if (empty($row['hapus']) || empty($row['id_predikat'])) {
                    continue;
                }
                if (DB::table('detail_rapor')->where('id_predikat', $row['id_predikat'])->exists()) {
                    throw ValidationException::withMessages(['rentang' => 'Predikat yang sudah dipakai pada rapor tidak dapat dihapus.']);
                }
            }

            $this->validasiRentangLengkap($rows);

            $huruf = array_map(fn ($row) => mb_strtolower($row['huruf']), $rows);
            if (count($huruf) !== count(array_unique($huruf))) {
                throw ValidationException::withMessages(['rentang' => 'Setiap label predikat harus unik.']);
            }

            foreach ($input as $row) {
                if (! empty($row['hapus']) && ! empty($row['id_predikat'])) {
                    $existing->get((int) $row['id_predikat'])->delete();
                }
            }

            foreach ($rows as $row) {
                $id = $row['id_predikat'];
                unset($row['id_predikat'], $row['hapus']);
                if ($id !== null) {
                    $existing->get($id)->update($row);
                } else {
                    SkalaPredikat::create($row);
                }
            }
        });
    }

    private function validasiRentangLengkap(array $rows): void
    {
        if ($rows === []) {
            throw ValidationException::withMessages(['nilai_min' => 'Skala harus mencakup nilai 0 sampai 100.']);
        }

        usort($rows, fn ($a, $b) => (int) round((float) $a['nilai_min'] * 100) <=> (int) round((float) $b['nilai_min'] * 100));
        $batasSebelumnya = null;

        foreach ($rows as $row) {
            $min = (int) round((float) $row['nilai_min'] * 100);
            $maks = (int) round((float) $row['nilai_maks'] * 100);
            if ($min < 0 || $maks > 10000 || $min > $maks) {
                throw ValidationException::withMessages(['nilai_min' => 'Rentang nilai harus berada di antara 0 dan 100.']);
            }
            if ($batasSebelumnya === null && $min !== 0) {
                throw ValidationException::withMessages(['nilai_min' => 'Skala predikat harus dimulai dari 0.']);
            }
            if ($batasSebelumnya !== null && $min !== $batasSebelumnya + 1) {
                throw ValidationException::withMessages(['nilai_min' => 'Skala predikat tidak boleh tumpang-tindih atau memiliki celah.']);
            }
            $batasSebelumnya = $maks;
        }

        if ($batasSebelumnya !== 10000) {
            throw ValidationException::withMessages(['nilai_maks' => 'Skala predikat harus berakhir pada 100.']);
        }
    }

    private function kunciKonfigurasi(string $nama): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::selectOne('SELECT pg_advisory_xact_lock(hashtext(?))', ['msbd:'.$nama]);
        }
    }

    /* ---------- Penghapusan aman ---------- */

    /** Tolak hapus jika masih dipakai tabel lain; kembalikan pesan, atau null jika boleh. */
    public function alasanTidakBisaDihapus(string $jenis, int $id): ?string
    {
        $ada = fn (string $tabel, string $kolom) => DB::table($tabel)->selectRaw('1')->where($kolom, $id)->exists();

        $pakai = match ($jenis) {
            'tahun_ajaran' => $ada('semester', 'id_tahun_ajaran') || $ada('kelas', 'id_tahun_ajaran') || $ada('periode_update', 'id_tahun_ajaran'),
            'semester' => $ada('jadwal_pelajaran', 'id_semester') || $ada('keanggotaan_kelas', 'id_semester')
                || $ada('penugasan_wali_kelas', 'id_semester') || $ada('nilai_siswa', 'id_semester') || $ada('rapor', 'id_semester'),
            'kelas' => $ada('keanggotaan_kelas', 'id_kelas') || $ada('jadwal_pelajaran', 'id_kelas') || $ada('penugasan_wali_kelas', 'id_kelas'),
            'mapel' => $ada('jadwal_pelajaran', 'id_mata_pelajaran') || $ada('nilai_siswa', 'id_mapel') || $ada('detail_rapor', 'id_mapel'),
            'komponen' => $ada('nilai_siswa', 'id_komponen'),
            'predikat' => $ada('detail_rapor', 'id_predikat'),
            default => false,
        };

        return $pakai ? 'Data sudah dipakai di tabel lain. Nonaktifkan, jangan hapus.' : null;
    }
}
