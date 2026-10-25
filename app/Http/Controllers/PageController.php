<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Support\DummyData;


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
    public function pengguna()        { return view('admin.pengguna', ['pengguna' => DummyData::pengguna()]); }
    public function penggunaTambah()  { return view('admin.pengguna-tambah', ['roles' => DummyData::roles()]); }
    public function staf()            { return view('admin.staf', DummyData::guruStaf()); }
    public function peran()           { return view('admin.peran', DummyData::hakAkses()); }
    public function pengaturan()      { return view('admin.pengaturan', ['set' => DummyData::pengaturan()]); }
    public function logLogin()        { return view('admin.log-login', ['log' => DummyData::logLogin()]); }
    public function audit()           { return view('admin.audit', ['log' => DummyData::audit()]); }
}