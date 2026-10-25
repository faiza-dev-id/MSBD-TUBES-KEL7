<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('kelas');

        if ($request->filled('cari')) {
            $query->where(
                'nama_kelas',
                'ilike',
                '%' . $request->cari . '%'
            );
        }

        $kelas = $query
            ->orderBy('nama_kelas')
            ->paginate(15)
            ->withQueryString();

        return view('tu.kelas', compact('kelas'));
    }

    public function show(int $id)
    {
        $kelas = DB::table('kelas')
            ->where('id_kelas', $id)
            ->first();

        abort_if(!$kelas, 404);

        $siswa = DB::table('keanggotaan_kelas as kk')
            ->join('siswa as s', 'kk.id_siswa', '=', 's.id_siswa')
            ->where('kk.id_kelas', $id)
            ->select(
                's.id_siswa',
                's.nis',
                's.nisn',
                's.nama_lengkap'
            )
            ->orderBy('s.nama_lengkap')
            ->get();

        return view('tu.kelas-detail', compact('kelas', 'siswa'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_kelas' => 'required|string|max:100',
        ]);

        DB::table('kelas')->insert($data);

        return redirect()
            ->route('tu.kelas.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'nama_kelas' => 'required|string|max:100',
        ]);

        $updated = DB::table('kelas')
            ->where('id_kelas', $id)
            ->update($data);

        abort_if(!$updated, 404);

        return redirect()
            ->route('tu.kelas.index')
            ->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $kelas = DB::table('kelas')
            ->where('id_kelas', $id)
            ->first();

        abort_if(!$kelas, 404);

        DB::table('kelas')
            ->where('id_kelas', $id)
            ->delete();

        return redirect()
            ->route('tu.kelas.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }
}