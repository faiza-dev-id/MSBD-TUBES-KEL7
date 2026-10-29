<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiswaController extends Controller
{
    // Menampilkan daftar siswa dan pencarian
    public function index(Request $request)
    {
        $query = DB::table('siswa as s')
            ->leftJoin(
                'status_siswa as st',
                's.id_status_siswa',
                '=',
                'st.id_status_siswa'
            )
            ->leftJoin(
                'keanggotaan_kelas as kk',
                's.id_siswa',
                '=',
                'kk.id_siswa'
            )
            ->leftJoin(
                'kelas as k',
                'kk.id_kelas',
                '=',
                'k.id_kelas'
            )
            ->select(
                's.id_siswa',
                's.nis',
                's.nisn',
                's.nama_lengkap',
                's.jenis_kelamin',
                'st.nama_status',
                'k.nama_kelas'
            );

        if ($request->filled('cari')) {
            $cari = $request->cari;

            $query->where(function ($q) use ($cari) {
                $q->where('s.nama_lengkap', 'ilike', "%{$cari}%")
                    ->orWhere('s.nisn', 'ilike', "%{$cari}%")
                    ->orWhere('s.nis', 'ilike', "%{$cari}%");
            });
        }

        $daftar = $query
            ->orderBy('s.nama_lengkap')
            ->paginate(15)
            ->through(fn ($row) => [
                'id' => $row->id_siswa,
                'nis' => $row->nis,
                'nisn' => $row->nisn,
                'nama' => $row->nama_lengkap,
                'jenis_kelamin' => $row->jenis_kelamin,
                'status' => $row->nama_status,
                'kelas' => $row->nama_kelas,
            ])
            ->withQueryString();

        return view('tu.siswa', compact('daftar'));
    }

    // Menampilkan detail siswa
    public function show(int $id)
    {
        $siswa = Siswa::findOrFail($id);

        $alamat = DB::table('alamat_siswa')
            ->where('id_siswa', $id)
            ->first();

        $orangTua = DB::table('orang_tua_wali')
            ->where('id_siswa', $id)
            ->get();

        $dokumen = DB::table('dokumen_siswa')
            ->where('id_siswa', $id)
            ->get();

        return view('tu.siswa-detail', compact(
            'siswa',
            'alamat',
            'orangTua',
            'dokumen'
        ));
    }

    // Menyimpan siswa baru
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nis' => 'nullable|string|max:30',
            'nisn' => 'nullable|string|size:10',
            'nik' => 'nullable|string|size:16',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        $data['dibuat_pada'] = now();
        $data['diubah_pada'] = now();

        Siswa::create($data);

        return redirect()
            ->route('tu.siswa')
            ->with('success', 'Data siswa berhasil ditambahkan.');
    }

    // Memperbarui data siswa
    public function update(Request $request, int $id)
    {
        $siswa = Siswa::findOrFail($id);

        $data = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nis' => 'nullable|string|max:30',
            'nisn' => 'nullable|string|size:10',
            'nik' => 'nullable|string|size:16',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        $data['diubah_pada'] = now();

        $siswa->update($data);

        return redirect()
            ->route('tu.siswa')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    // Menghapus siswa
    public function destroy(int $id)
    {
        $siswa = Siswa::findOrFail($id);

        $siswa->delete();

        return redirect()
            ->route('tu.siswa')
            ->with('success', 'Data siswa berhasil dihapus.');
    }
}