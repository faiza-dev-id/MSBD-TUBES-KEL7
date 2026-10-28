<?php

namespace App\Services;

use App\Support\AuditCatat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * DFD 2.5 Kelola Data Referensi (Tata Usaha). Satu definisi per tabel referensi,
 * lalu validasi, simpan, ubah, hapus, dan audit dijalankan secara generik.
 */
class ReferensiService
{
    public function definisi(): array
    {
        return [
            'jenjang' => [
                'judul' => 'Jenjang', 'tabel' => 'jenjang', 'pk' => 'id_jenjang', 'urut' => 'nama_jenjang',
                'fields' => [
                    ['name' => 'nama_jenjang', 'label' => 'Nama jenjang', 'type' => 'text', 'max' => 80, 'required' => true, 'unique' => true],
                ],
                'dipakai' => [['tingkat', 'id_jenjang']],
            ],
            'tingkat' => [
                'judul' => 'Tingkat', 'tabel' => 'tingkat', 'pk' => 'id_tingkat', 'urut' => 'urutan',
                'fields' => [
                    ['name' => 'nama_tingkat', 'label' => 'Tingkat', 'type' => 'text', 'max' => 50, 'required' => true, 'unique' => true],
                    ['name' => 'urutan', 'label' => 'Urutan', 'type' => 'int', 'required' => true, 'unique' => true],
                    ['name' => 'id_jenjang', 'label' => 'Jenjang', 'type' => 'select',
                        'opsi' => ['tabel' => 'jenjang', 'nilai' => 'id_jenjang', 'label' => 'nama_jenjang']],
                ],
                'dipakai' => [['kelas', 'id_tingkat']],
            ],
            'agama' => [
                'judul' => 'Agama', 'tabel' => 'agama', 'pk' => 'id_agama', 'urut' => 'nama_agama',
                'fields' => [
                    ['name' => 'nama_agama', 'label' => 'Nama agama', 'type' => 'text', 'max' => 80, 'required' => true, 'unique' => true],
                    ['name' => 'is_aktif', 'label' => 'Aktif', 'type' => 'bool'],
                ],
                'dipakai' => [['siswa', 'id_agama']],
            ],
            'pekerjaan' => [
                'judul' => 'Pekerjaan', 'tabel' => 'pekerjaan', 'pk' => 'id_pekerjaan', 'urut' => 'nama_pekerjaan',
                'fields' => [
                    ['name' => 'nama_pekerjaan', 'label' => 'Nama pekerjaan', 'type' => 'text', 'max' => 120, 'required' => true, 'unique' => true],
                    ['name' => 'is_aktif', 'label' => 'Aktif', 'type' => 'bool'],
                ],
                'dipakai' => [['orang_tua_wali', 'id_pekerjaan']],
            ],
            'sekolah' => [
                'judul' => 'Sekolah Asal', 'tabel' => 'sekolah', 'pk' => 'id_sekolah', 'urut' => 'nama_sekolah',
                'fields' => [
                    ['name' => 'nama_sekolah', 'label' => 'Nama sekolah', 'type' => 'text', 'max' => 200, 'required' => true],
                    ['name' => 'npsn', 'label' => 'NPSN', 'type' => 'text', 'max' => 20, 'unique' => true],
                    ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea'],
                    ['name' => 'is_aktif', 'label' => 'Aktif', 'type' => 'bool'],
                ],
                'dipakai' => [['siswa', 'id_sekolah_asal'], ['mutasi_siswa', 'id_sekolah_tujuan']],
            ],
            'status-siswa' => [
                'judul' => 'Status Siswa', 'tabel' => 'status_siswa', 'pk' => 'id_status_siswa', 'urut' => 'id_status_siswa',
                // kode_status dipakai oleh logika alur siswa: tidak boleh diganti atau dihapus dari sini.
                'tanpa_hapus' => true,
                'fields' => [
                    ['name' => 'kode_status', 'label' => 'Kode', 'type' => 'text', 'max' => 40, 'required' => true, 'unique' => true, 'kunci' => true, 'extra' => ['alpha_dash']],
                    ['name' => 'nama_status', 'label' => 'Nama status', 'type' => 'text', 'max' => 80, 'required' => true, 'unique' => true],
                    ['name' => 'boleh_diubah_siswa', 'label' => 'Siswa boleh ubah data', 'type' => 'bool'],
                ],
                'dipakai' => [['siswa', 'id_status_siswa']],
            ],
            'jenis-dokumen' => [
                'judul' => 'Jenis Dokumen', 'tabel' => 'jenis_dokumen', 'pk' => 'id_jenis_dokumen', 'urut' => 'nama_dokumen',
                'fields' => [
                    ['name' => 'nama_dokumen', 'label' => 'Nama dokumen', 'type' => 'text', 'max' => 120, 'required' => true, 'unique' => true],
                ],
                'dipakai' => [['ref_checklist_item', 'id_jenis_dokumen'], ['ref_field_data', 'id_jenis_dokumen_bukti']],
            ],
        ];
    }

    public function rules(array $def, ?int $id = null): array
    {
        $rules = [];
        foreach ($def['fields'] as $f) {
            if ($id !== null && !empty($f['kunci'])) continue;   // tidak diubah setelah dibuat

            $n = $f['name'];
            $req = !empty($f['required']) ? 'required' : 'nullable';
            $r = match ($f['type']) {
                'text'     => [$req, 'string', 'max:'.$f['max'], ...($f['extra'] ?? [])],
                'textarea' => ['nullable', 'string', 'max:1000'],
                'int'      => [$req, 'integer', 'min:1', 'max:32000'],
                'bool'     => ['sometimes', 'boolean'],
                'select'   => [$req, 'integer', 'exists:'.$f['opsi']['tabel'].','.$f['opsi']['nilai']],
            };
            if (!empty($f['unique'])) {
                $r[] = Rule::unique($def['tabel'], $n)->ignore($id, $def['pk']);
            }
            $rules[$n] = $r;
        }

        return $rules;
    }

    /** Pilihan untuk field bertipe select: [namaField => [nilai => label]]. */
    public function opsi(array $def): array
    {
        $hasil = [];
        foreach ($def['fields'] as $f) {
            if ($f['type'] === 'select') {
                $o = $f['opsi'];
                $hasil[$f['name']] = DB::table($o['tabel'])->orderBy($o['label'])->pluck($o['label'], $o['nilai'])->all();
            }
        }

        return $hasil;
    }

    /** Ambil hanya kolom yang ada di definisi dan rapikan nilai boolean. */
    public function bersihkan(array $def, array $validated, bool $ubah): array
    {
        $data = [];
        foreach ($def['fields'] as $f) {
            if ($ubah && !empty($f['kunci'])) continue;
            $n = $f['name'];
            $data[$n] = $f['type'] === 'bool' ? filter_var($validated[$n] ?? false, FILTER_VALIDATE_BOOLEAN) : ($validated[$n] ?? null);
        }

        return $data;
    }

    public function simpan(array $def, array $data): int
    {
        return DB::transaction(function () use ($def, $data) {
            $id = (int) DB::table($def['tabel'])->insertGetId($data, $def['pk']);
            AuditCatat::tulis($def['tabel'], 'INSERT', $id, null, $data + [$def['pk'] => $id]);

            return $id;
        });
    }

    /** @return bool false bila data tidak ditemukan */
    public function ubah(array $def, int $id, array $data): bool
    {
        return DB::transaction(function () use ($def, $id, $data) {
            $lama = DB::table($def['tabel'])->where($def['pk'], $id)->lockForUpdate()->first();
            if (!$lama) return false;

            $lamaArr = array_intersect_key((array) $lama, $data);
            if ($lamaArr == $data) return true;   // tidak ada perubahan

            DB::table($def['tabel'])->where($def['pk'], $id)->update($data);
            AuditCatat::tulis($def['tabel'], 'UPDATE', $id, $lamaArr, $data);

            return true;
        });
    }

    /** @return string|null pesan galat, atau null bila berhasil */
    public function hapus(array $def, int $id): ?string
    {
        if (!empty($def['tanpa_hapus'])) {
            return 'Data ini dipakai oleh logika sistem dan tidak dapat dihapus.';
        }

        return DB::transaction(function () use ($def, $id) {
            $lama = DB::table($def['tabel'])->where($def['pk'], $id)->lockForUpdate()->first();
            if (!$lama) return 'Data tidak ditemukan.';

            foreach ($def['dipakai'] as [$tabel, $kolom]) {
                if (DB::table($tabel)->where($kolom, $id)->exists()) {
                    return 'Data masih dipakai di tabel lain ('.$tabel.'). Nonaktifkan atau ubah, jangan dihapus.';
                }
            }

            DB::table($def['tabel'])->where($def['pk'], $id)->delete();
            AuditCatat::tulis($def['tabel'], 'DELETE', $id, (array) $lama, null);

            return null;
        });
    }
}
