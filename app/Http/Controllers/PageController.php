<?php

namespace App\Http\Controllers;

use App\Support\DummyData;

/** Controller halaman (tahap desain). Isinya hanya menyiapkan data tiruan untuk view. */
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
            'admin'  => redirect()->route('admin.pengguna'),
            default  => redirect()->route('login'),
        };
    }

    // ---- SEMUA ROLE ----
    public function sandi() { return view('akun.sandi'); }

    // ---- SISWA ----
    public function siswaDashboard() { return view('siswa.dashboard', ['siswa' => DummyData::siswa(), 'pengajuan' => DummyData::pengajuanSiswa()]); }
    public function ppdb()           { return view('siswa.ppdb'); }
    public function status()         { return view('siswa.status', ['langkah' => DummyData::statusPendaftaran()]); }
    public function profil()         { return view('siswa.profil', ['s' => DummyData::siswa()]); }
    public function pengajuan()      { return view('siswa.pengajuan', ['s' => DummyData::siswa(), 'pengajuan' => DummyData::pengajuanSiswa()]); }

    // ---- GURU ----
    public function jadwalGuru() { return view('guru.jadwal', ['jadwal' => DummyData::jadwalGuru()]); }
    public function guruKelas()  { return view('guru.kelas', ['siswa' => DummyData::siswaKelas()]); }
    public function catatan()    { return view('guru.catatan', ['siswa' => DummyData::siswaKelas(), 'catatan' => DummyData::catatan()]); }

    // ---- TU ----
    public function tuDashboard() { return view('tu.dashboard', ['antrean' => DummyData::antrean(), 'kelas' => DummyData::kelengkapanKelas()]); }
    public function verifikasi(int $id)
    {
        $row = collect(DummyData::antrean())->firstWhere('id', $id) ?? abort(404);

        return view('tu.verifikasi', ['row' => $row, 'checklist' => DummyData::checklist(), 'alasan' => DummyData::alasanPengembalian()]);
    }
    public function persetujuan() { return view('tu.persetujuan', ['pengajuan' => DummyData::pengajuanSiswa()]); }
    public function dataSiswa()   { return view('tu.siswa', ['daftar' => DummyData::daftarSiswa()]); }
    public function detailSiswa(int $id)
    {
        $row = collect(DummyData::daftarSiswa())->firstWhere('id', $id) ?? abort(404);

        return view('tu.siswa-detail', ['row' => $row, 's' => DummyData::siswa(), 'dokumen' => DummyData::dokumenSiswa()]);
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

    // ---- AKADEMIK ----
    public function konfigurasi(string $tab = 'tahun-ajaran')
    {
        $semua = DummyData::akademik();
        abort_unless(isset($semua[$tab]), 404);

        return view('akademik.konfigurasi', ['tab' => $tab, 'semua' => $semua]);
    }
    public function jadwal() { return view('akademik.jadwal', ['kelas' => DummyData::kelas(), 'mapel' => DummyData::daftarMapel()]); }

    // ---- KEPSEK ----
    public function kepsekDashboard() { return view('kepsek.dashboard', ['kelas' => DummyData::kelengkapanKelas()]); }

    // ---- ADMIN ----
    public function pengguna()       { return view('admin.pengguna', ['pengguna' => DummyData::pengguna()]); }
    public function penggunaTambah() { return view('admin.pengguna-tambah', ['roles' => DummyData::roles()]); }
    public function staf()           { return view('admin.staf', DummyData::guruStaf()); }
    public function peran()          { return view('admin.peran', DummyData::hakAkses()); }
    public function referensi(string $tab = 'agama')
    {
        $semua = DummyData::referensi();
        abort_unless(isset($semua[$tab]), 404);

        return view('admin.referensi', ['tab' => $tab, 'semua' => $semua]);
    }
    public function checklistRef() { return view('admin.checklist', ['checklist' => DummyData::checklist(), 'alasan' => DummyData::alasanPengembalian()]); }
    public function aturan()       { return view('admin.aturan-field', ['field' => DummyData::aturanField()]); }
    public function pengaturan()   { return view('admin.pengaturan', ['set' => DummyData::pengaturan()]); }
    public function logLogin()     { return view('admin.log-login', ['log' => DummyData::logLogin()]); }
    public function audit()        { return view('admin.audit', ['log' => DummyData::audit()]); }
}