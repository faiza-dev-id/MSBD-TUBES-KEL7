<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Services\ReferensiService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** DFD 2.5 Kelola Data Referensi: agama, pekerjaan, sekolah, status siswa, dokumen, jenjang, tingkat, wilayah. */
class ReferensiController extends Controller
{
    public function __construct(private ReferensiService $svc) {}

    public function index(Request $r, string $tab = 'agama')
    {
        $defs = $this->svc->definisi();
        $tabs = collect($defs)->map(fn ($d) => $d['judul'])->all() + ['wilayah' => 'Wilayah'];
        abort_unless(isset($tabs[$tab]), 404);

        $cari = trim((string) $r->query('cari', ''));
        $like = '%'.addcslashes($cari, '%_\\').'%';

        if ($tab === 'wilayah') {
            $baris = DB::table('kelurahan as kl')
                ->join('kecamatan as kc', 'kc.id_kecamatan', '=', 'kl.id_kecamatan')
                ->join('kota as k', 'k.id_kota', '=', 'kc.id_kota')
                ->join('provinsi as p', 'p.id_provinsi', '=', 'k.id_provinsi')
                ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('kl.nama_kelurahan', 'ilike', $like)->orWhere('kc.nama_kecamatan', 'ilike', $like)
                    ->orWhere('k.nama_kota', 'ilike', $like)->orWhere('p.nama_provinsi', 'ilike', $like)))
                ->orderBy('p.nama_provinsi')->orderBy('k.nama_kota')->orderBy('kc.nama_kecamatan')->orderBy('kl.nama_kelurahan')
                ->select('p.nama_provinsi', 'k.nama_kota', 'kc.nama_kecamatan', 'kl.nama_kelurahan', 'kl.kode_pos')
                ->paginate(25)->withQueryString();

            return view('admin.referensi', ['tab' => $tab, 'tabs' => $tabs, 'cari' => $cari, 'baris' => $baris, 'def' => null, 'opsi' => []]);
        }

        $def = $defs[$tab];
        $kolomCari = collect($def['fields'])->firstWhere('type', 'text')['name'];
        $baris = DB::table($def['tabel'])
            ->when($cari !== '', fn ($q) => $q->where($kolomCari, 'ilike', $like))
            ->orderBy($def['urut'])->orderBy($def['pk'])
            ->paginate(25)->withQueryString();

        return view('admin.referensi', [
            'tab' => $tab, 'tabs' => $tabs, 'cari' => $cari, 'baris' => $baris, 'def' => $def, 'opsi' => $this->svc->opsi($def),
        ]);
    }

    public function store(Request $r, string $tab)
    {
        $def = $this->definisi($tab);
        $data = $this->svc->bersihkan($def, $r->validate($this->svc->rules($def)), false);

        try {
            $this->svc->simpan($def, $data);
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->withErrors(['umum' => 'Data yang sama sudah ada.']);
        }

        return redirect()->route('admin.referensi', $tab)->with('success', $def['judul'].' berhasil ditambahkan.');
    }

    public function update(Request $r, string $tab, int $id)
    {
        $def = $this->definisi($tab);
        $data = $this->svc->bersihkan($def, $r->validate($this->svc->rules($def, $id)), true);

        try {
            $ada = $this->svc->ubah($def, $id, $data);
        } catch (UniqueConstraintViolationException $e) {
            return back()->withErrors(['umum' => 'Data yang sama sudah ada.']);
        }
        abort_unless($ada, 404);

        return redirect()->route('admin.referensi', $tab)->with('success', 'Perubahan disimpan.');
    }

    public function destroy(string $tab, int $id)
    {
        $def = $this->definisi($tab);
        $galat = $this->svc->hapus($def, $id);

        return redirect()->route('admin.referensi', $tab)->with(
            $galat ? 'galat' : 'success',
            $galat ?? 'Data dihapus.'
        );
    }

    private function definisi(string $tab): array
    {
        $defs = $this->svc->definisi();
        abort_unless(isset($defs[$tab]), 404);

        return $defs[$tab];
    }
}
