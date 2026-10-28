<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Pengguna;
use App\Models\Role;
use App\Models\Staf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * DFD 1.2 Kelola Akun Pengguna (Kepala Sekolah): ubah akun, aktif/nonaktif, daftar guru & staf.
 * Tambah akun (penggunaSimpan) dan form edit tetap di PageController buatan tim.
 * Versi di sini menambah pengaman: tidak menghapus data guru, tidak mengunci Kepala Sekolah.
 */
class PenggunaController extends Controller
{
    public function update(Request $r, int $id)
    {
        $user = Pengguna::with('role')->findOrFail($id);

        $data = $r->validate([
            'nama'     => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100',
                Rule::unique('pengguna', 'username')->ignore($user->id_pengguna, 'id_pengguna')],
            'id_role'  => ['required', 'integer', 'exists:role,id_role'],
            'nip'      => ['nullable', 'string', 'max:40'],
            'no_hp'    => ['nullable', 'string', 'max:30'],
        ]);

        $roleBaru = Role::findOrFail($data['id_role']);
        $roleLama = $user->role->nama_role ?? null;
        $diriSendiri = (int) $r->session()->get('id_pengguna') === (int) $user->id_pengguna;

        if ($roleBaru->nama_role === 'siswa' || $roleLama === 'siswa') {
            return $this->tolak('id_role', 'Akun siswa dikelola dari Data Induk Siswa, bukan dari form ini.');
        }
        if ($diriSendiri && (int) $roleBaru->id_role !== (int) $user->id_role) {
            return $this->tolak('id_role', 'Peran akun sendiri tidak dapat diubah. Minta Kepala Sekolah lain jika perlu.');
        }
        if ($roleLama === 'kepsek' && $roleBaru->nama_role !== 'kepsek' && !$this->adaKepsekAktifLain($user->id_pengguna)) {
            return $this->tolak('id_role', 'Harus tersisa minimal satu Kepala Sekolah aktif.');
        }

        $guru = Guru::where('id_pengguna', $user->id_pengguna)->first();
        if ($roleLama === 'guru' && $roleBaru->nama_role !== 'guru' && $guru && $this->guruMasihDipakai($guru->id_guru)) {
            return $this->tolak('id_role', 'Guru ini masih punya jadwal atau tugas wali kelas. Pindahkan dulu sebelum mengubah perannya.');
        }

        DB::transaction(function () use ($user, $data, $roleBaru) {
            $user->update([
                'id_role'     => $roleBaru->id_role,
                'username'    => $data['username'],
                'diubah_pada' => now(),
            ]);

            if ($roleBaru->nama_role === 'guru') {
                Staf::where('id_pengguna', $user->id_pengguna)->get()->each->update(['is_aktif' => false]);
                Guru::updateOrCreate(['id_pengguna' => $user->id_pengguna], [
                    'nip'        => $data['nip'] ?: null,
                    'nama_guru'  => $data['nama'],
                    'no_telepon' => $data['no_hp'] ?: null,
                    'is_aktif'   => true,
                ]);
            } elseif (in_array($roleBaru->nama_role, ['tu', 'kepsek'], true)) {
                // Dinonaktifkan, bukan dihapus: riwayat tetap ada dan bisa diaktifkan lagi.
                Guru::where('id_pengguna', $user->id_pengguna)->get()->each->update(['is_aktif' => false]);
                Staf::updateOrCreate(['id_pengguna' => $user->id_pengguna], [
                    'nama_staf'  => $data['nama'],
                    'jabatan'    => $roleBaru->nama_role === 'tu' ? 'Tata Usaha' : 'Kepala Sekolah',
                    'no_telepon' => $data['no_hp'] ?: null,
                    'is_aktif'   => true,
                ]);
            }
        });

        return redirect()->route('admin.pengguna')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function ubahStatus(Request $r, int $id)
    {
        $user = Pengguna::with('role')->findOrFail($id);

        if ((int) $r->session()->get('id_pengguna') === (int) $user->id_pengguna) {
            return back()->withErrors(['status' => 'Kamu tidak dapat menonaktifkan akun sendiri.']);
        }
        if ($user->is_aktif && ($user->role->nama_role ?? null) === 'kepsek' && !$this->adaKepsekAktifLain($user->id_pengguna)) {
            return back()->withErrors(['status' => 'Harus tersisa minimal satu Kepala Sekolah aktif.']);
        }

        $user->is_aktif = !$user->is_aktif;
        $user->diubah_pada = now();
        $user->save();

        return redirect()->route('admin.pengguna')->with(
            'success',
            $user->is_aktif ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.'
        );
    }

    /** DFD 1.2: data pegawai (read-only). Perubahan data dilakukan lewat menu Pengguna. */
    public function staf(Request $r)
    {
        $cari = trim((string) $r->query('cari', ''));
        $like = '%'.addcslashes($cari, '%_\\').'%';

        $guru = DB::table('guru as g')
            ->leftJoin('pengguna as p', 'p.id_pengguna', '=', 'g.id_pengguna')
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('g.nama_guru', 'ilike', $like)->orWhere('g.nip', 'ilike', $like)->orWhere('p.username', 'ilike', $like)))
            ->orderBy('g.nama_guru')
            ->get(['g.nama_guru', 'g.nip', 'g.no_telepon', 'g.is_aktif', 'p.id_pengguna', 'p.username']);

        $staf = DB::table('staf as s')
            ->leftJoin('pengguna as p', 'p.id_pengguna', '=', 's.id_pengguna')
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('s.nama_staf', 'ilike', $like)->orWhere('s.jabatan', 'ilike', $like)->orWhere('p.username', 'ilike', $like)))
            ->orderBy('s.nama_staf')
            ->get(['s.nama_staf', 's.jabatan', 's.no_telepon', 's.is_aktif', 'p.id_pengguna', 'p.username']);

        return view('admin.staf', compact('guru', 'staf', 'cari'));
    }

    /* ---------------- helper ---------------- */

    private function tolak(string $kolom, string $pesan)
    {
        return back()->withInput()->withErrors([$kolom => $pesan]);
    }

    private function adaKepsekAktifLain(int $idPengguna): bool
    {
        return Pengguna::query()
            ->where('is_aktif', true)
            ->where('id_pengguna', '!=', $idPengguna)
            ->whereHas('role', fn ($q) => $q->where('nama_role', 'kepsek'))
            ->exists();
    }

    private function guruMasihDipakai(int $idGuru): bool
    {
        return DB::table('jadwal_pelajaran')->where('id_guru', $idGuru)->exists()
            || DB::table('penugasan_wali_kelas')->where('id_guru', $idGuru)->exists();
    }
}
