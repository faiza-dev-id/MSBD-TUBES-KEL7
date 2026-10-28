<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Support\AuditCatat;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * DFD 2.5: daftar periksa verifikasi berkas (ref_checklist_item, per jenis dokumen)
 * dan alasan pengembalian berkas (ref_alasan_pengembalian). Dipakai di verifikasi PPDB.
 */
class ChecklistController extends Controller
{
    public function index()
    {
        $item = DB::table('ref_checklist_item')->orderBy('id_item')->get()->groupBy('id_jenis_dokumen');
        $dokumen = DB::table('jenis_dokumen')->orderBy('nama_dokumen')->get();
        $alasan = DB::table('ref_alasan_pengembalian')->orderBy('id_alasan')->get();

        return view('admin.checklist', compact('item', 'dokumen', 'alasan'));
    }

    /* ---------- item checklist ---------- */

    public function simpanItem(Request $r)
    {
        $d = $r->validate([
            'id_jenis_dokumen' => 'required|integer|exists:jenis_dokumen,id_jenis_dokumen',
            'nama_item' => ['required', 'string', 'max:180',
                Rule::unique('ref_checklist_item', 'nama_item')->where('id_jenis_dokumen', $r->input('id_jenis_dokumen'))],
            'wajib' => 'sometimes|boolean',
        ]);
        $data = ['id_jenis_dokumen' => (int) $d['id_jenis_dokumen'], 'nama_item' => $d['nama_item'], 'wajib' => $r->boolean('wajib')];

        return $this->tulis(fn () => DB::transaction(function () use ($data) {
            $id = DB::table('ref_checklist_item')->insertGetId($data, 'id_item');
            AuditCatat::tulis('ref_checklist_item', 'INSERT', $id, null, $data + ['id_item' => $id]);
        }), 'Item checklist ditambahkan.');
    }

    public function ubahItem(Request $r, int $id)
    {
        $lama = DB::table('ref_checklist_item')->where('id_item', $id)->first();
        abort_unless($lama, 404);

        $d = $r->validate([
            'nama_item' => ['required', 'string', 'max:180',
                Rule::unique('ref_checklist_item', 'nama_item')->where('id_jenis_dokumen', $lama->id_jenis_dokumen)->ignore($id, 'id_item')],
            'wajib' => 'sometimes|boolean',
        ]);
        $data = ['nama_item' => $d['nama_item'], 'wajib' => $r->boolean('wajib')];

        return $this->tulis(fn () => DB::transaction(function () use ($id, $lama, $data) {
            if (array_intersect_key((array) $lama, $data) == $data) return;
            DB::table('ref_checklist_item')->where('id_item', $id)->update($data);
            AuditCatat::tulis('ref_checklist_item', 'UPDATE', $id, array_intersect_key((array) $lama, $data), $data);
        }), 'Item checklist disimpan.');
    }

    public function hapusItem(int $id)
    {
        return $this->hapus('ref_checklist_item', 'id_item', $id, [['detail_verifikasi', 'id_item']], 'Item checklist dihapus.');
    }

    /* ---------- alasan pengembalian ---------- */

    public function simpanAlasan(Request $r)
    {
        $d = $r->validate(['nama_alasan' => 'required|string|max:180|unique:ref_alasan_pengembalian,nama_alasan']);

        return $this->tulis(fn () => DB::transaction(function () use ($d) {
            $id = DB::table('ref_alasan_pengembalian')->insertGetId(['nama_alasan' => $d['nama_alasan']], 'id_alasan');
            AuditCatat::tulis('ref_alasan_pengembalian', 'INSERT', $id, null, ['id_alasan' => $id, 'nama_alasan' => $d['nama_alasan']]);
        }), 'Alasan ditambahkan.');
    }

    public function ubahAlasan(Request $r, int $id)
    {
        $lama = DB::table('ref_alasan_pengembalian')->where('id_alasan', $id)->first();
        abort_unless($lama, 404);
        $d = $r->validate(['nama_alasan' => ['required', 'string', 'max:180',
            Rule::unique('ref_alasan_pengembalian', 'nama_alasan')->ignore($id, 'id_alasan')]]);

        return $this->tulis(fn () => DB::transaction(function () use ($id, $lama, $d) {
            if ($lama->nama_alasan === $d['nama_alasan']) return;
            DB::table('ref_alasan_pengembalian')->where('id_alasan', $id)->update(['nama_alasan' => $d['nama_alasan']]);
            AuditCatat::tulis('ref_alasan_pengembalian', 'UPDATE', $id, ['nama_alasan' => $lama->nama_alasan], ['nama_alasan' => $d['nama_alasan']]);
        }), 'Alasan disimpan.');
    }

    public function hapusAlasan(int $id)
    {
        return $this->hapus('ref_alasan_pengembalian', 'id_alasan', $id, [['verifikasi_pendaftaran', 'id_alasan']], 'Alasan dihapus.');
    }

    /* ---------- helper ---------- */

    private function tulis(callable $aksi, string $pesan)
    {
        try {
            $aksi();
        } catch (UniqueConstraintViolationException $e) {
            return back()->withErrors(['umum' => 'Data yang sama sudah ada.']);
        }

        return redirect()->route('admin.checklist')->with('success', $pesan);
    }

    private function hapus(string $tabel, string $pk, int $id, array $dipakai, string $pesan)
    {
        $galat = DB::transaction(function () use ($tabel, $pk, $id, $dipakai) {
            $lama = DB::table($tabel)->where($pk, $id)->lockForUpdate()->first();
            if (!$lama) return 'Data tidak ditemukan.';
            foreach ($dipakai as [$t, $k]) {
                if (DB::table($t)->where($k, $id)->exists()) {
                    return 'Masih dipakai pada data verifikasi, jadi tidak dapat dihapus.';
                }
            }
            DB::table($tabel)->where($pk, $id)->delete();
            AuditCatat::tulis($tabel, 'DELETE', $id, (array) $lama, null);

            return null;
        });

        return redirect()->route('admin.checklist')->with($galat ? 'galat' : 'success', $galat ?? $pesan);
    }
}
