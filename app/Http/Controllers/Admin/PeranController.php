<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HakAkses;
use App\Models\LogAudit;
use App\Models\Role;
use App\Support\AuditPelaku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DFD 1.3 Kelola Peran dan Hak Akses (Kepala Sekolah).
 * Peran bersifat tetap (siswa, guru, tu, kepsek) karena nama peran dipakai middleware;
 * yang bisa diatur hanya pemetaan peran -> hak akses (tabel role_hak_akses).
 */
class PeranController extends Controller
{
    /** Hak yang tidak boleh dicabut dari suatu peran, agar tidak ada yang terkunci dari pengelolaan sistem. */
    public const TERKUNCI = ['kepsek' => ['user.manage']];

    public function index()
    {
        $roles = Role::orderBy('id_role')->get();
        $hak = HakAkses::orderBy('kode_hak_akses')->get();
        $punya = DB::table('role_hak_akses')->get()
            ->groupBy('id_role')
            ->map(fn ($g) => $g->pluck('id_hak_akses')->map(fn ($x) => (int) $x)->all());

        return view('admin.peran', ['roles' => $roles, 'hak' => $hak, 'punya' => $punya, 'terkunci' => self::TERKUNCI]);
    }

    public function update(Request $r)
    {
        $r->validate([
            'akses'     => 'nullable|array',
            'akses.*'   => 'nullable|array',
            'akses.*.*' => 'integer|exists:hak_akses,id_hak_akses',
        ]);

        $roles = Role::all()->keyBy('id_role');
        $kodeHak = HakAkses::pluck('kode_hak_akses', 'id_hak_akses');      // id => kode
        $input = $r->input('akses', []);
        $pelaku = AuditPelaku::sekarang();
        $berubah = 0;

        DB::transaction(function () use ($roles, $kodeHak, $input, $pelaku, &$berubah) {
            foreach ($roles as $idRole => $role) {
                $baru = collect($input[$idRole] ?? [])->map(fn ($x) => (int) $x)->unique();

                // Hak terkunci selalu dipertahankan di sisi server, walau checkbox dimanipulasi.
                foreach (self::TERKUNCI[$role->nama_role] ?? [] as $kode) {
                    $id = $kodeHak->search($kode);
                    if ($id !== false) $baru->push((int) $id);
                }
                $baru = $baru->unique();

                $lama = DB::table('role_hak_akses')->where('id_role', $idRole)->pluck('id_hak_akses')->map(fn ($x) => (int) $x);
                $tambah = $baru->diff($lama);
                $hapus = $lama->diff($baru);
                if ($tambah->isEmpty() && $hapus->isEmpty()) continue;

                if ($hapus->isNotEmpty()) {
                    DB::table('role_hak_akses')->where('id_role', $idRole)->whereIn('id_hak_akses', $hapus->all())->delete();
                }
                if ($tambah->isNotEmpty()) {
                    DB::table('role_hak_akses')->insert(
                        $tambah->map(fn ($h) => ['id_role' => $idRole, 'id_hak_akses' => $h])->values()->all()
                    );
                }

                $daftar = fn ($c) => $c->map(fn ($i) => $kodeHak[$i] ?? (string) $i)->sort()->values()->all();
                LogAudit::create([
                    'nama_tabel'           => 'role_hak_akses',
                    'operasi'              => 'UPDATE',
                    'id_data'              => (string) $idRole,
                    'nilai_lama'           => ['peran' => $role->nama_role, 'hak_akses' => $daftar($lama)],
                    'nilai_baru'           => ['peran' => $role->nama_role, 'hak_akses' => $daftar($baru)],
                    'id_pengguna_pengubah' => $pelaku['id'] ?? null,
                    'username_pengubah'    => $pelaku['username'] ?? null,
                    'waktu'                => now(),
                ]);
                $berubah++;
            }
        });

        return redirect()->route('admin.peran')->with(
            'success',
            $berubah ? "Hak akses {$berubah} peran diperbarui." : 'Tidak ada perubahan.'
        );
    }
}
