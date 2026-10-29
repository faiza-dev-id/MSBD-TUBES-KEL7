<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\KomponenPenilaian;
use App\Models\MataPelajaran;
use App\Models\Semester;
use App\Models\SkalaPredikat;
use App\Models\TahunAjaran;
use App\Services\MasterAkademikService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * DFD 2.1 Konfigurasi Akademik (Tata Usaha), versi halaman web.
 * Aturan bisnis ada di MasterAkademikService (sama dengan API /api/akademik/...).
 */
class KonfigurasiController extends Controller
{
    public function __construct(private MasterAkademikService $svc) {}

    public function index(Request $r, string $tab = 'tahun-ajaran')
    {
        $sp = $this->sp($tab);
        $cari = trim((string) $r->query('cari', ''));

        return view('akademik.konfigurasi', [
            'tab'   => $tab,
            'tabs'  => collect($this->spek())->map(fn ($s) => $s['judul'])->all(),
            'sp'    => $sp,
            'cari'  => $cari,
            'baris' => $this->baris($tab, $sp, $cari),
            'opsi'  => $this->opsi($sp),
            'bobot' => $tab === 'komponen' ? $this->svc->ringkasanBobot() : null,
        ]);
    }

    public function store(Request $r, string $tab)
    {
        $sp = $this->sp($tab);
        $data = $this->bersihkan($sp, $r->validate($this->rules($tab, $sp, $r, null)), false);

        return $this->jalankan(fn () => $this->simpan($tab, $data, null), $tab, $sp['judul'].' berhasil ditambahkan.', true);
    }

    public function update(Request $r, string $tab, int $id)
    {
        $sp = $this->sp($tab);
        $data = $this->bersihkan($sp, $r->validate($this->rules($tab, $sp, $r, $id)), true);

        return $this->jalankan(fn () => $this->simpan($tab, $data, $id), $tab, 'Perubahan disimpan.', false);
    }

    public function destroy(string $tab, int $id)
    {
        $sp = $this->sp($tab);
        $model = $sp['model']::findOrFail($id);

        if ($alasan = $this->svc->alasanTidakBisaDihapus($sp['jenis'], $id)) {
            return redirect()->route('akademik.konfigurasi', $tab)->with('galat', $alasan);
        }
        $model->delete();

        return redirect()->route('akademik.konfigurasi', $tab)->with('success', 'Data dihapus.');
    }

    /* ---------------- definisi tab ---------------- */

    private function spek(): array
    {
        return [
            'tahun-ajaran' => [
                'judul' => 'Tahun Ajaran', 'model' => TahunAjaran::class, 'pk' => 'id_tahun_ajaran', 'jenis' => 'tahun_ajaran', 'cari' => 't.nama_tahun_ajaran',
                'fields' => [
                    ['name' => 'nama_tahun_ajaran', 'label' => 'Tahun ajaran (mis. 2026/2027)', 'type' => 'text', 'max' => 20, 'required' => true],
                    ['name' => 'tanggal_mulai', 'label' => 'Mulai', 'type' => 'date', 'required' => true],
                    ['name' => 'tanggal_selesai', 'label' => 'Selesai', 'type' => 'date', 'required' => true],
                    ['name' => 'is_aktif', 'label' => 'Aktif', 'type' => 'bool'],
                ],
                'kolom' => [['Tahun ajaran', 'nama_tahun_ajaran'], ['Mulai', 'tanggal_mulai', 'date'], ['Selesai', 'tanggal_selesai', 'date'], ['Status', 'is_aktif', 'aktif']],
            ],
            'semester' => [
                'judul' => 'Semester', 'model' => Semester::class, 'pk' => 'id_semester', 'jenis' => 'semester', 'cari' => 's.nama_semester',
                'fields' => [
                    ['name' => 'id_tahun_ajaran', 'label' => 'Tahun ajaran', 'type' => 'select', 'required' => true, 'kunci' => true,
                        'opsi' => ['tabel' => 'tahun_ajaran', 'nilai' => 'id_tahun_ajaran', 'label' => 'nama_tahun_ajaran']],
                    ['name' => 'nama_semester', 'label' => 'Semester (Ganjil/Genap)', 'type' => 'text', 'max' => 30, 'required' => true],
                    ['name' => 'tanggal_mulai', 'label' => 'Mulai', 'type' => 'date'],
                    ['name' => 'tanggal_selesai', 'label' => 'Selesai', 'type' => 'date'],
                    ['name' => 'is_aktif', 'label' => 'Aktif', 'type' => 'bool'],
                ],
                'kolom' => [['Semester', 'nama_semester'], ['Tahun ajaran', 'nama_tahun_ajaran'], ['Mulai', 'tanggal_mulai', 'date'], ['Selesai', 'tanggal_selesai', 'date'], ['Status', 'is_aktif', 'aktif']],
            ],
            'kelas' => [
                'judul' => 'Kelas', 'model' => Kelas::class, 'pk' => 'id_kelas', 'jenis' => 'kelas', 'cari' => 'k.nama_kelas',
                'fields' => [
                    ['name' => 'nama_kelas', 'label' => 'Nama kelas (mis. X IPA 1)', 'type' => 'text', 'max' => 80, 'required' => true],
                    ['name' => 'id_tahun_ajaran', 'label' => 'Tahun ajaran', 'type' => 'select',
                        'opsi' => ['tabel' => 'tahun_ajaran', 'nilai' => 'id_tahun_ajaran', 'label' => 'nama_tahun_ajaran']],
                    ['name' => 'kapasitas', 'label' => 'Kapasitas', 'type' => 'int', 'min' => 1, 'maks' => 100],
                    ['name' => 'is_aktif', 'label' => 'Aktif', 'type' => 'bool'],
                ],
                'kolom' => [['Kelas', 'nama_kelas'], ['Tingkat', 'nama_tingkat'], ['Tahun ajaran', 'nama_tahun_ajaran'], ['Kapasitas', 'kapasitas'], ['Wali kelas (semester aktif)', 'wali'], ['Status', 'is_aktif', 'aktif']],
            ],
            'mapel' => [
                'judul' => 'Mata Pelajaran', 'model' => MataPelajaran::class, 'pk' => 'id_mata_pelajaran', 'jenis' => 'mapel', 'cari' => 'm.nama_mapel',
                'fields' => [
                    ['name' => 'kode_mapel', 'label' => 'Kode', 'type' => 'text', 'max' => 30],
                    ['name' => 'nama_mapel', 'label' => 'Nama mata pelajaran', 'type' => 'text', 'max' => 150, 'required' => true],
                    ['name' => 'is_aktif', 'label' => 'Aktif', 'type' => 'bool'],
                ],
                'kolom' => [['Kode', 'kode_mapel'], ['Mata pelajaran', 'nama_mapel'], ['Status', 'is_aktif', 'aktif']],
            ],
            'komponen' => [
                'judul' => 'Komponen Penilaian', 'model' => KomponenPenilaian::class, 'pk' => 'id_komponen', 'jenis' => 'komponen', 'cari' => 'k.nama_komponen',
                'fields' => [
                    ['name' => 'nama_komponen', 'label' => 'Komponen', 'type' => 'text', 'max' => 120, 'required' => true],
                    ['name' => 'bobot_persen', 'label' => 'Bobot (%)', 'type' => 'num', 'required' => true],
                ],
                'kolom' => [['Komponen', 'nama_komponen'], ['Bobot (%)', 'bobot_persen', 'num']],
            ],
            'predikat' => [
                'judul' => 'Skala Predikat', 'model' => SkalaPredikat::class, 'pk' => 'id_predikat', 'jenis' => 'predikat', 'cari' => 'p.huruf',
                'fields' => [
                    ['name' => 'huruf', 'label' => 'Huruf', 'type' => 'text', 'max' => 5, 'required' => true],
                    ['name' => 'nilai_min', 'label' => 'Nilai minimum', 'type' => 'num', 'required' => true],
                    ['name' => 'nilai_maks', 'label' => 'Nilai maksimum', 'type' => 'num', 'required' => true],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'text', 'max' => 255],
                ],
                'kolom' => [['Huruf', 'huruf'], ['Nilai min', 'nilai_min', 'num'], ['Nilai maks', 'nilai_maks', 'num'], ['Deskripsi', 'deskripsi']],
            ],
        ];
    }

    private function sp(string $tab): array
    {
        $spek = $this->spek();
        abort_unless(isset($spek[$tab]), 404);

        return $spek[$tab];
    }

    /* ---------------- data tampilan ---------------- */

    private function baris(string $tab, array $sp, string $cari)
    {
        $q = match ($tab) {
            'tahun-ajaran' => DB::table('tahun_ajaran as t')->select('t.*')->orderByDesc('t.tanggal_mulai'),
            'semester'     => DB::table('semester as s')
                ->join('tahun_ajaran as t', 't.id_tahun_ajaran', '=', 's.id_tahun_ajaran')
                ->select('s.*', 't.nama_tahun_ajaran')->orderByDesc('t.tanggal_mulai')->orderBy('s.tanggal_mulai'),
            'kelas'        => $this->queryKelas(),
            'mapel'        => DB::table('mata_pelajaran as m')->select('m.*')->orderBy('m.nama_mapel'),
            'komponen'     => DB::table('komponen_penilaian as k')->select('k.*')->orderBy('k.id_komponen'),
            'predikat'     => DB::table('skala_predikat as p')->select('p.*')->orderByDesc('p.nilai_min'),
        };

        if ($cari !== '') {
            $q->where($sp['cari'], 'ilike', '%'.addcslashes($cari, '%_\\').'%');
        }

        return $q->paginate(25)->withQueryString();
    }

    private function queryKelas()
    {
        $idSemesterAktif = (int) Semester::where('is_aktif', true)->value('id_semester');

        return DB::table('kelas as k')
            ->leftJoin('tingkat as t', 't.id_tingkat', '=', 'k.id_tingkat')
            ->leftJoin('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->leftJoin('penugasan_wali_kelas as pw', function ($j) use ($idSemesterAktif) {
                $j->on('pw.id_kelas', '=', 'k.id_kelas')->where('pw.id_semester', '=', $idSemesterAktif);
            })
            ->leftJoin('guru as g', 'g.id_guru', '=', 'pw.id_guru')
            ->select('k.*', 't.nama_tingkat', 'ta.nama_tahun_ajaran', 'g.nama_guru as wali')
            ->orderBy('t.urutan')->orderBy('k.nama_kelas');
    }

    private function opsi(array $sp): array
    {
        $hasil = [];
        foreach ($sp['fields'] as $f) {
            if ($f['type'] === 'select') {
                $o = $f['opsi'];
                $hasil[$f['name']] = DB::table($o['tabel'])->orderBy($o['label'])->pluck($o['label'], $o['nilai'])->all();
            }
        }

        return $hasil;
    }

    /* ---------------- validasi dan simpan ---------------- */

    private function rules(string $tab, array $sp, Request $r, ?int $id): array
    {
        $rules = [];
        foreach ($sp['fields'] as $f) {
            if ($id !== null && !empty($f['kunci'])) continue;   // tidak berubah setelah dibuat

            $req = !empty($f['required']) ? 'required' : 'nullable';
            $rules[$f['name']] = match ($f['type']) {
                'text'   => [$req, 'string', 'max:'.$f['max']],
                'int'    => [$req, 'integer', 'min:'.($f['min'] ?? 1), 'max:'.($f['maks'] ?? 1000)],
                'num'    => [$req, 'numeric', 'min:0', 'max:100'],
                'date'   => [$req, 'date'],
                'select' => [$req, 'integer', 'exists:'.$f['opsi']['tabel'].','.$f['opsi']['nilai']],
                'bool'   => ['sometimes', 'boolean'],
            };
        }

        $tambahan = match ($tab) {
            'tahun-ajaran' => [
                'nama_tahun_ajaran' => [
                    Rule::unique('tahun_ajaran', 'nama_tahun_ajaran')->ignore($id, 'id_tahun_ajaran'),
                    function ($attr, $v, $fail) {
                        if (!preg_match('/^(\d{4})\/(\d{4})$/', (string) $v, $m) || (int) $m[2] !== (int) $m[1] + 1) {
                            $fail('Format harus tahun berurutan, mis. 2026/2027.');
                        }
                    },
                ],
                'tanggal_selesai' => ['after:tanggal_mulai'],
            ],
            'semester' => [
                'nama_semester' => [Rule::unique('semester', 'nama_semester')
                    ->where('id_tahun_ajaran', $id !== null ? Semester::whereKey($id)->value('id_tahun_ajaran') : $r->input('id_tahun_ajaran'))
                    ->ignore($id, 'id_semester')],
                'tanggal_selesai' => $r->filled('tanggal_mulai') ? ['after:tanggal_mulai'] : [],
            ],
            'kelas' => [
                'nama_kelas' => [Rule::unique('kelas', 'nama_kelas')->ignore($id, 'id_kelas')],
            ],
            'mapel' => [
                'kode_mapel' => ['alpha_dash', Rule::unique('mata_pelajaran', 'kode_mapel')->ignore($id, 'id_mata_pelajaran')],
                'nama_mapel' => [Rule::unique('mata_pelajaran', 'nama_mapel')->ignore($id, 'id_mata_pelajaran')],
            ],
            'komponen' => [
                'nama_komponen' => [Rule::unique('komponen_penilaian', 'nama_komponen')->ignore($id, 'id_komponen')],
            ],
            'predikat' => [
                'huruf' => [Rule::unique('skala_predikat', 'huruf')->ignore($id, 'id_predikat')],
                'nilai_maks' => ['gte:nilai_min'],
            ],
        };
        foreach ($tambahan as $kolom => $aturan) {
            if (isset($rules[$kolom])) $rules[$kolom] = array_merge($rules[$kolom], $aturan);
        }

        return $rules;
    }

    private function bersihkan(array $sp, array $validated, bool $ubah): array
    {
        $data = [];
        foreach ($sp['fields'] as $f) {
            if ($ubah && !empty($f['kunci'])) continue;
            $n = $f['name'];
            $data[$n] = $f['type'] === 'bool'
                ? filter_var($validated[$n] ?? false, FILTER_VALIDATE_BOOLEAN)
                : ($validated[$n] ?? null);
        }

        return $data;
    }

    private function simpan(string $tab, array $data, ?int $id): void
    {
        switch ($tab) {
            case 'tahun-ajaran':
                $this->svc->simpanTahunAjaran($data, $id ? TahunAjaran::findOrFail($id) : null);
                break;
            case 'semester':
                $sem = $id ? Semester::findOrFail($id) : null;
                $ta = $sem ? $sem->tahunAjaran : TahunAjaran::findOrFail($data['id_tahun_ajaran']);
                $this->svc->simpanSemester($ta, Arr::except($data, ['id_tahun_ajaran']), $sem);
                break;
            case 'kelas':
                $id ? Kelas::findOrFail($id)->update($data) : Kelas::create($data);
                break;
            case 'mapel':
                $id ? MataPelajaran::findOrFail($id)->update($data) : MataPelajaran::create($data);
                break;
            case 'komponen':
                $this->svc->simpanKomponen($data, $id ? KomponenPenilaian::findOrFail($id) : null);
                break;
            case 'predikat':
                $this->svc->simpanPredikat($data, $id ? SkalaPredikat::findOrFail($id) : null);
                break;
        }
    }

    /** Jalankan aksi simpan; ValidationException dari service otomatis kembali ke form dengan pesan. */
    private function jalankan(callable $aksi, string $tab, string $pesan, bool $tambah)
    {
        try {
            $aksi();
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->withErrors(['umum' => 'Data yang sama sudah ada.']);
        }

        return redirect()->route('akademik.konfigurasi', $tab)->with('success', $pesan);
    }
}
