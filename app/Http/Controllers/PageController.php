<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Support\DummyData;
use App\Models\Pengguna;
use App\Models\Role;
use App\Models\Guru;
use App\Models\Staf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class PageController extends Controller
{
    /** Arahkan ke beranda sesuai role. */
    public function beranda()
    {
        return match (session('role')) {
            'siswa'  => redirect()->route('siswa.dashboard'),
            'guru'   => redirect()->route('guru.jadwal'),
            'tu'     => redirect()->route('tu.dashboard'),
            'kepsek' => redirect()->route('kepsek.dashboard'),
            default  => redirect()->route('login'),
        };
    }
    //public halaman login
public function masuk()
{
    return view('auth.login', [
        'roles' => DummyData::roles(),
    ]);
}
    // ---- PUBLIK (calon siswa) ----
    public function ppdb()   { return view('siswa.ppdb'); }
    public function status() { return view('siswa.status', ['langkah' => DummyData::statusPendaftaran()]); }

    // ---- SEMUA ROLE ----
    public function sandi() { return view('akun.sandi'); }

    // ---- SISWA ----
    public function siswaDashboard() { return view('siswa.dashboard', ['siswa' => DummyData::siswa(), 'pengajuan' => DummyData::pengajuanSiswa()]); }
    public function profil()         { return view('siswa.profil', ['s' => DummyData::siswa()]); }
    public function pengajuan()      { return view('siswa.pengajuan', ['s' => DummyData::siswa(), 'pengajuan' => DummyData::pengajuanSiswa()]); }
    public function konfirmasi()     { return view('siswa.konfirmasi', ['s' => DummyData::siswa(), 'periode' => DummyData::periodeAktif()]); }

    // ---- GURU ----
    public function jadwalGuru()      { return view('guru.jadwal', ['jadwal' => DummyData::jadwalGuru()]); }
    public function guruKelas()       { return view('guru.kelas', ['siswa' => DummyData::siswaKelas()]); }
    public function guruKelengkapan() { return view('guru.kelengkapan', ['siswa' => DummyData::kelengkapanSiswa()]); }
    public function catatan()         { return view('guru.catatan', ['siswa' => DummyData::siswaKelas(), 'catatan' => DummyData::catatan()]); }

    // ---- TU ----
    public function tuDashboard() { return view('tu.dashboard', ['antrean' => DummyData::antrean(), 'kelas' => DummyData::kelengkapanKelas()]); }
    public function verifikasi(int $id)
    {
        $row = collect(DummyData::antrean())->firstWhere('id', $id) ?? abort(404);

        return view('tu.verifikasi', ['row' => $row, 'checklist' => DummyData::checklist(), 'alasan' => DummyData::alasanPengembalian()]);
    }
    public function persetujuan() { return view('tu.persetujuan', ['pengajuan' => DummyData::pengajuanSiswa()]); }
    public function antrean()     { return view('tu.antrean', ['antrean' => DummyData::antrean()]); }
    public function kelulusan()   { return view('tu.kelulusan', ['pendaftar' => DummyData::kelulusan()]); }
    public function dokumenSiswa(int $id)
    {
        // Nama diambil dari database bila tersedia; kalau tidak, halaman tetap tampil dengan data contoh.
        try {
            $nama = DB::table('siswa')->where('id_siswa', $id)->value('nama_lengkap');
        } catch (\Throwable $e) {
            $nama = null;
        }

        return view('tu.siswa-dokumen', [
            'id'      => $id,
            'nama'    => $nama,
            'dokumen' => DummyData::dokumenSiswa(),
            'jenis'   => ['Kartu Keluarga', 'Akta Kelahiran', 'Ijazah / SKL', 'Pas Foto 3x4', 'Lainnya'],
        ]);
    }
    public function resetSandi() { return view('tu.reset-sandi', ['pengguna' => DummyData::pengguna()]); }
    public function dataSiswa()
{
    $daftar = DB::table('siswa as s')
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
            's.id_siswa as id',
            's.nama_lengkap as nama',
            's.nisn',
            'k.nama_kelas as kelas',
            'st.nama_status as status'
        )
        ->orderBy('s.nama_lengkap')
        ->get()
        ->map(function ($siswa) {
            return [
                'id' => $siswa->id,
                'nama' => $siswa->nama,
                'nisn' => $siswa->nisn ?? '-',
                'kelas' => $siswa->kelas ?? '-',
                'status' => $siswa->status ?? '-',
            ];
        });

    return view('tu.siswa', [
        'daftar' => $daftar,
    ]);
}

public function detailSiswa(int $id)
{
    $siswa = DB::table('siswa as s')
        ->leftJoin('status_siswa as st', 's.id_status_siswa', '=', 'st.id_status_siswa')
        ->leftJoin('keanggotaan_kelas as kk', 's.id_siswa', '=', 'kk.id_siswa')
        ->leftJoin('kelas as k', 'kk.id_kelas', '=', 'k.id_kelas')
        ->leftJoin('pengguna as p', 's.id_pengguna', '=', 'p.id_pengguna')
        ->where('s.id_siswa', $id)
        ->select(
            's.id_siswa as id',
            's.nis',
            's.nisn',
            's.nik',
            's.nama_lengkap as nama',
            's.jenis_kelamin',
            's.tempat_lahir',
            's.tanggal_lahir',
            's.email',
            's.no_telepon',
            's.tanggal_masuk',
            'st.nama_status as status',
            'k.nama_kelas as kelas',
            'p.username'
        )
        ->first();

    abort_if(!$siswa, 404);

    $row = [
        'id' => $siswa->id,
        'nama' => $siswa->nama,
        'nisn' => $siswa->nisn ?? '-',
        'kelas' => $siswa->kelas ?? '-',
        'status' => $siswa->status ?? '-',
    ];

    $s = [
        'nik' => $siswa->nik ?? '-',
        'no_kk' => '-',
        'jenis_kelamin' => $siswa->jenis_kelamin ?? '-',
        'tempat_lahir' => $siswa->tempat_lahir ?? '-',
        'tanggal_lahir' => $siswa->tanggal_lahir ?? '-',
        'agama' => '-',
        'no_hp' => $siswa->no_telepon ?? '-',
        'sekolah_asal' => '-',
        'alamat' => '-',
        'ayah' => '-',
        'ibu' => '-',
    ];

    return view('tu.siswa-detail', [
        'row' => $row,
        's' => $s,
        'dokumen' => [],
    ]);
}
    public function periode()     { return view('tu.periode', ['periode' => DummyData::periodeUpdate()]); }
    public function kelas()       { return view('tu.kelas', ['kelas' => DummyData::kelas()]); }
    public function kelasDetail(int $id)
    {
        $row = collect(DummyData::kelas())->firstWhere('id', $id) ?? abort(404);

        return view('tu.kelas-detail', ['row' => $row, 'siswa' => DummyData::siswaKelas()]);
    }
    public function penempatan()  { return view('tu.penempatan', ['belum' => DummyData::belumBerkelas(), 'kelas' => DummyData::kelas()]); }
    public function kenaikan()    { return view('tu.kenaikan', ['siswa' => DummyData::siswaKelas(), 'kelas' => DummyData::kelas()]); }

    // ---- DATA MASTER (TU) ----
    public function referensi(string $tab = 'agama')
    {
        $semua = DummyData::referensi();
        abort_unless(isset($semua[$tab]), 404);

        return view('admin.referensi', ['tab' => $tab, 'semua' => $semua]);
    }
    public function checklistRef() { return view('admin.checklist', ['checklist' => DummyData::checklist(), 'alasan' => DummyData::alasanPengembalian()]); }
    public function aturan()       { return view('admin.aturan-field', ['field' => DummyData::aturanField()]); }

    // ---- AKADEMIK (TU) ----
    public function konfigurasi(string $tab = 'tahun-ajaran')
    {
        $semua = DummyData::akademik();
        abort_unless(isset($semua[$tab]), 404);

        return view('akademik.konfigurasi', ['tab' => $tab, 'semua' => $semua]);
    }
    public function jadwal()
    {
        return view('akademik.jadwal', [
            'kelas' => DummyData::kelas(), 'mapel' => DummyData::daftarMapel(),
            'guru' => DummyData::daftarGuru(), 'semester' => DummyData::akademik()['semester']['baris'],
        ]);
    }

    // ---- KEPALA SEKOLAH ----
    public function kepsekDashboard() { return view('kepsek.dashboard', ['kelas' => DummyData::kelengkapanKelas()]); }
    public function pengguna(Request $request)
{
    $cari = trim($request->query('cari', ''));

    $pengguna = Pengguna::with(['role', 'guru', 'staf'])
        ->orderBy('username')
        ->get()
        ->map(function ($u) {
            $guru = $u->guru->first();
            $staf = $u->staf->first();

            return [
                'id_pengguna' => $u->id_pengguna,
                'nama' => $guru->nama_guru
                    ?? $staf->nama_staf
                    ?? $u->username,
                'username' => $u->username,
                'role' => $u->role->nama_role ?? '-',
                'login' => $u->terakhir_login ?? '-',
                'aktif' => (bool) $u->is_aktif,
            ];
        });

    if ($cari !== '') {
        $pengguna = $pengguna->filter(function ($u) use ($cari) {
            return stripos($u['nama'], $cari) !== false
                || stripos($u['username'], $cari) !== false
                || stripos($u['role'], $cari) !== false;
        })->values();
    }

    return view('admin.pengguna', compact('pengguna', 'cari'));
}

public function penggunaSimpan(Request $request)
{
    $data = $request->validate([
        'nama' => ['required', 'string', 'max:150'],
        'username' => ['required', 'string', 'max:100', 'unique:pengguna,username'],
        'id_role' => ['required', 'integer', 'exists:role,id_role'],
        'nip' => ['nullable', 'string', 'max:40'],
        'no_hp' => ['nullable', 'string', 'max:30'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    $role = Role::findOrFail($data['id_role']);

    if ($role->nama_role === 'siswa') {
        return back()
            ->withInput()
            ->withErrors([
                'id_role' => 'Akun siswa dibuat melalui proses pendaftaran siswa.',
            ]);
    }

    DB::transaction(function () use ($data, $role) {
        $pengguna = Pengguna::create([
            'id_role' => $role->id_role,
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'is_aktif' => true,
            'dibuat_pada' => now(),
            'diubah_pada' => now(),
        ]);

        if ($role->nama_role === 'guru') {
            Guru::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'nip' => $data['nip'] ?: null,
                'nama_guru' => $data['nama'],
                'no_telepon' => $data['no_hp'] ?: null,
                'is_aktif' => true,
            ]);
        } elseif ($role->nama_role === 'tu' || $role->nama_role === 'kepsek') {
            Staf::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'nama_staf' => $data['nama'],
                'jabatan' => $role->nama_role === 'tu'
                    ? 'Tata Usaha'
                    : 'Kepala Sekolah',
                'no_telepon' => $data['no_hp'] ?: null,
                'is_aktif' => true,
            ]);
        }
    });

    return redirect()
        ->route('admin.pengguna')
        ->with('success', 'Pengguna berhasil ditambahkan.');
}
    public function penggunaTambah()
{
    $roles = Role::orderBy('id_role')->get();

    return view('admin.pengguna-tambah', compact('roles'));
}
public function penggunaEdit($id)
{
    $user = Pengguna::with(['role', 'guru', 'staf'])
        ->findOrFail($id);

    $roles = Role::orderBy('id_role')->get();

    $guru = $user->guru->first();
    $staf = $user->staf->first();

    $dataPengguna = [
        'id_pengguna' => $user->id_pengguna,
        'nama' => $guru->nama_guru
            ?? $staf->nama_staf
            ?? $user->username,
        'username' => $user->username,
        'id_role' => $user->id_role,
        'nip' => $guru->nip ?? '',
        'no_hp' => $guru->no_telepon
            ?? $staf->no_telepon
            ?? '',
    ];

    return view('admin.pengguna-edit', [
        'user' => $dataPengguna,
        'roles' => $roles,
    ]);
}

public function penggunaUpdate(Request $request, $id)
{
    $user = Pengguna::findOrFail($id);

    $data = $request->validate([
        'nama' => ['required', 'string', 'max:150'],
        'username' => [
            'required',
            'string',
            'max:100',
            \Illuminate\Validation\Rule::unique('pengguna', 'username')
                ->ignore($user->id_pengguna, 'id_pengguna'),
        ],
        'id_role' => ['required', 'integer', 'exists:role,id_role'],
        'nip' => ['nullable', 'string', 'max:40'],
        'no_hp' => ['nullable', 'string', 'max:30'],
    ]);

    $role = Role::findOrFail($data['id_role']);

    if ($role->nama_role === 'siswa') {
        return back()->withInput()->withErrors([
            'id_role' => 'Peran siswa tidak dapat dipilih melalui form ini.',
        ]);
    }

    DB::transaction(function () use ($user, $data, $role) {
        $user->update([
            'id_role' => $role->id_role,
            'username' => $data['username'],
            'diubah_pada' => now(),
        ]);

        $guru = Guru::where(
            'id_pengguna',
            $user->id_pengguna
        )->first();

        $staf = Staf::where(
            'id_pengguna',
            $user->id_pengguna
        )->first();

        if ($role->nama_role === 'guru') {
            if ($staf) {
                $staf->delete();
            }

            Guru::updateOrCreate(
                ['id_pengguna' => $user->id_pengguna],
                [
                    'nip' => $data['nip'] ?: null,
                    'nama_guru' => $data['nama'],
                    'no_telepon' => $data['no_hp'] ?: null,
                    'is_aktif' => true,
                ]
            );
        } elseif (in_array($role->nama_role, ['tu', 'kepsek'])) {
            if ($guru) {
                $guru->delete();
            }

            Staf::updateOrCreate(
                ['id_pengguna' => $user->id_pengguna],
                [
                    'nama_staf' => $data['nama'],
                    'jabatan' => $role->nama_role === 'tu'
                        ? 'Tata Usaha'
                        : 'Kepala Sekolah',
                    'no_telepon' => $data['no_hp'] ?: null,
                    'is_aktif' => true,
                ]
            );
        }
    });

    return redirect()
        ->route('admin.pengguna')
        ->with('success', 'Pengguna berhasil diperbarui.');
}

public function penggunaUbahStatus($id)
{
    $user = Pengguna::findOrFail($id);

    if ((int) session('id_pengguna') === (int) $user->id_pengguna) {
        return back()->withErrors([
            'status' => 'Kamu tidak dapat menonaktifkan akun sendiri.',
        ]);
    }

    $user->is_aktif = !$user->is_aktif;
    $user->diubah_pada = now();
    $user->save();

    $pesan = $user->is_aktif
        ? 'Akun berhasil diaktifkan.'
        : 'Akun berhasil dinonaktifkan.';

    return redirect()
        ->route('admin.pengguna')
        ->with('success', $pesan);
}

    public function staf()            { return view('admin.staf', DummyData::guruStaf()); }
    public function peran()           { return view('admin.peran', DummyData::hakAkses()); }
    public function pengaturan()      { return view('admin.pengaturan', ['set' => DummyData::pengaturan()]); }
    public function logLogin()
{
    $log = \App\Models\LogLogin::query()
        ->orderByDesc('waktu')
        ->limit(100)
        ->get()
        ->map(function ($l) {
            return [
                'waktu' => $l->waktu
                    ? \Illuminate\Support\Carbon::parse($l->waktu)
                        ->format('d-m-Y H:i:s')
                    : '-',
                'user' => $l->username_dicoba ?? '-',
                'ok' => (bool) $l->berhasil,
                'ip' => $l->ip_address ?? '-',
                'perangkat' => $l->user_agent ?? '-',
            ];
        });

    return view('admin.log-login', compact('log'));
}

public function audit()
{
    $log = \App\Models\LogAudit::query()
        ->orderByDesc('waktu')
        ->limit(100)
        ->get();

    return view('admin.audit', compact('log'));
}

}