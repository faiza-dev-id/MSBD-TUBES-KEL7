<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * DFD 1.2 (Tata Usaha): reset sandi akun siswa dan guru.
 * Akun Tata Usaha dan Kepala Sekolah SENGAJA tidak bisa direset di sini; kalau bisa,
 * TU dapat mengambil alih akun Kepala Sekolah (eskalasi hak).
 */
class ResetSandiController extends Controller
{
    private const BOLEH = ['siswa', 'guru'];

    public function index(Request $r)
    {
        return view('tu.reset-sandi', $this->data($r));
    }

    public function reset(Request $r, int $id)
    {
        $user = Pengguna::with('role')->findOrFail($id);
        abort_unless($user->role && in_array($user->role->nama_role, self::BOLEH, true), 403);

        if (!$user->is_aktif) {
            return back()->withErrors(['reset' => 'Akun nonaktif tidak dapat direset. Aktifkan dulu.']);
        }

        $sandi = $this->buatSandi();
        $user->password_hash = Hash::make($sandi);
        $user->diubah_pada = now();
        $user->save();   // trigger audit mencatat "sandi_diubah" tanpa isi hash

        $nama = DB::table('pengguna as p')
            ->leftJoin('siswa as s', 's.id_pengguna', '=', 'p.id_pengguna')
            ->leftJoin('guru as g', 'g.id_pengguna', '=', 'p.id_pengguna')
            ->where('p.id_pengguna', $id)
            ->value(DB::raw('COALESCE(s.nama_lengkap, g.nama_guru, p.username)'));

        // Sandi dikirim langsung di respons ini saja (tidak disimpan di session atau cache).
        return response()
            ->view('tu.reset-sandi', $this->data($r) + ['hasil' => [
                'nama' => $nama, 'username' => $user->username, 'sandi' => $sandi,
            ]])
            ->header('Cache-Control', 'no-store, private');
    }

    private function data(Request $r): array
    {
        $cari = trim((string) $r->query('cari', ''));
        $like = '%'.addcslashes($cari, '%_\\').'%';

        $pengguna = DB::table('pengguna as p')
            ->join('role as r', 'r.id_role', '=', 'p.id_role')
            ->leftJoin('siswa as s', 's.id_pengguna', '=', 'p.id_pengguna')
            ->leftJoin('guru as g', 'g.id_pengguna', '=', 'p.id_pengguna')
            ->whereIn('r.nama_role', self::BOLEH)
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('p.username', 'ilike', $like)
                ->orWhere('s.nama_lengkap', 'ilike', $like)
                ->orWhere('g.nama_guru', 'ilike', $like)))
            ->selectRaw('p.id_pengguna, p.username, p.is_aktif, r.nama_role, COALESCE(s.nama_lengkap, g.nama_guru, p.username) as nama')
            ->orderByRaw('COALESCE(s.nama_lengkap, g.nama_guru, p.username)')
            ->paginate(25)
            ->withQueryString();
        $pengguna->setPath(route('tu.reset'));   // agar tautan halaman benar walau dirender dari POST

        return ['pengguna' => $pengguna, 'cari' => $cari];
    }

    /** 10 karakter tanpa karakter membingungkan (0/O, 1/l/I), selalu memuat huruf dan angka. */
    private function buatSandi(): string
    {
        $huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
        $angka = '23456789';
        $c = [];
        for ($i = 0; $i < 7; $i++) $c[] = $huruf[random_int(0, strlen($huruf) - 1)];
        for ($i = 0; $i < 3; $i++) $c[] = $angka[random_int(0, strlen($angka) - 1)];
        for ($i = count($c) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$c[$i], $c[$j]] = [$c[$j], $c[$i]];
        }

        return implode('', $c);
    }
}
