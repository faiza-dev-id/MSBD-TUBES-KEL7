<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Support\DummyData;
use App\Models\Pengguna;
use App\Models\Role;
use App\Models\Guru;
use App\Models\Staf;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;


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
    public function status(Request $request)
    {
        $langkah = DummyData::statusPendaftaran();
        $hasil = null;

        if ($request->filled('cek_nisn') || $request->filled('cek_kode')) {
            $query = DB::table('pendaftaran_ppdb');

            if ($request->filled('cek_kode')) {
                $query->where('nomor_pendaftaran', trim((string) $request->query('cek_kode')));
            }

            if ($request->filled('cek_nisn')) {
                $query->where('nisn', trim((string) $request->query('cek_nisn')));
            }

            $baris = $query->orderByDesc('diajukan_pada')->first();

            if ($baris) {
                $hasil = $baris;
                $langkah = $this->langkahStatusPendaftaran($baris);
                $request->flash();
            } else {
                session()->now('error', 'Pendaftaran tidak ditemukan. Periksa kembali NISN dan kode pendaftaran.');
            }
        }

        return view('siswa.status', ['langkah' => $langkah, 'hasil' => $hasil]);
    }

    // ---- SEMUA ROLE ----
    public function sandi() { return view('akun.sandi'); }

    // ---- SISWA ----
    public function siswaDashboard() { return view('siswa.dashboard', ['siswa' => DummyData::siswa(), 'pengajuan' => DummyData::pengajuanSiswa()]); }
    public function profil()         { return view('siswa.profil', ['s' => $this->siswaTampilan()]); }
    public function pengajuan()
    {
        $siswa = $this->siswaSesi();

        return view('siswa.pengajuan', [
            's' => $this->siswaTampilan(),
            'pengajuan' => $this->daftarPengajuanSiswa($siswa?->id_siswa),
            'periode' => $this->periodeBerjalan(),
        ]);
    }
    public function konfirmasi()
    {
        return view('siswa.konfirmasi', [
            's' => $this->siswaTampilan(),
            'periode' => $this->periodeBerjalan(),
        ]);
    }

    // ---- GURU ----
    public function jadwalGuru()      { return view('guru.jadwal', ['jadwal' => DummyData::jadwalGuru()]); }
    public function guruKelas()       { return view('guru.kelas', ['siswa' => DummyData::siswaKelas()]); }
    public function guruKelengkapan() { return view('guru.kelengkapan', ['siswa' => DummyData::kelengkapanSiswa()]); }
    public function catatan()
    {
        $idGuru = Guru::where('id_pengguna', session('id_pengguna'))->value('id_guru');

        $catatan = DB::table('catatan_wali_kelas as c')
            ->join('siswa as s', 's.id_siswa', '=', 'c.id_siswa')
            ->when($idGuru, fn ($q) => $q->where('c.id_guru', $idGuru))
            ->orderByDesc('c.tgl_dibuat')
            ->get(['c.isi_catatan', 'c.tgl_dibuat', 's.nama_lengkap'])
            ->map(fn ($c) => [
                'siswa' => $c->nama_lengkap,
                'isi' => $c->isi_catatan,
                'tgl' => Carbon::parse($c->tgl_dibuat)->locale('id')->translatedFormat('d M Y'),
            ])
            ->all();

        return view('guru.catatan', [
            'siswa' => $this->daftarSiswaRingkas(),
            'catatan' => $catatan ?: DummyData::catatan(),
        ]);
    }

    /** Presensi harian kelas yang diwalikan. */
    public function guruAbsensi(Request $request)
    {
        $guru = $this->guruSesi();
        $semester = $this->semesterAktif();
        $kelasWali = ($guru && $semester) ? $this->kelasWali((int) $guru->id_guru, (int) $semester->id_semester) : [];
        $idKelas = (int) $request->query('kelas', $kelasWali[0]['id'] ?? 0);
        $kelas = collect($kelasWali)->firstWhere('id', $idKelas) ?? ($kelasWali[0] ?? null);
        $tanggal = $this->tanggalValid($request->query('tanggal'));
        $siswa = [];
        $presensi = [];
        $adaJadwal = false;

        if ($kelas && $semester) {
            $siswa = DB::table('keanggotaan_kelas as kk')
                ->join('siswa as s', 's.id_siswa', '=', 'kk.id_siswa')
                ->where('kk.id_kelas', $kelas['id'])
                ->where('kk.id_semester', $semester->id_semester)
                ->where('kk.status', 'aktif')
                ->orderBy('s.nama_lengkap')
                ->get(['s.id_siswa', 's.nama_lengkap', 's.nisn'])
                ->map(fn ($s) => [
                    'id' => $s->id_siswa,
                    'nama' => $s->nama_lengkap,
                    'nisn' => $s->nisn ?? '-',
                ])
                ->all();

            $adaJadwal = DB::table('jadwal_pelajaran')
                ->where('id_kelas', $kelas['id'])
                ->where('id_semester', $semester->id_semester)
                ->exists();

            $sesi = DB::table('sesi_absensi as sa')
                ->join('jadwal_pelajaran as j', 'j.id_jadwal', '=', 'sa.id_jadwal')
                ->where('j.id_kelas', $kelas['id'])
                ->where('j.id_semester', $semester->id_semester)
                ->where('sa.tanggal', $tanggal)
                ->orderBy('sa.id_sesi')
                ->select('sa.id_sesi')
                ->first();

            if ($sesi) {
                $detail = DB::table('detail_absensi as da')
                    ->join('status_kehadiran as sh', 'sh.id_status_hadir', '=', 'da.id_status_hadir')
                    ->where('da.id_sesi', $sesi->id_sesi)
                    ->get(['da.id_siswa', 'sh.kode', 'da.keterangan']);

                foreach ($detail as $d) {
                    $presensi[$d->id_siswa] = [
                        'kode' => strtolower((string) $d->kode),
                        'keterangan' => $d->keterangan ?? '',
                    ];
                }
            }
        }

        return view('guru.absensi', compact('kelasWali', 'kelas', 'semester', 'tanggal', 'siswa', 'presensi', 'adaJadwal'));
    }

    /** Rapor siswa di kelas yang diwalikan (nilai diisi wali kelas). */
    public function guruRapor(Request $request)
    {
        $guru = $this->guruSesi();
        $semester = $this->semesterAktif();
        $kelasWali = ($guru && $semester) ? $this->kelasWali((int) $guru->id_guru, (int) $semester->id_semester) : [];
        $idKelas = (int) $request->query('kelas', $kelasWali[0]['id'] ?? 0);
        $kelas = collect($kelasWali)->firstWhere('id', $idKelas) ?? ($kelasWali[0] ?? null);
        $siswa = [];
        $terpilih = null;
        $mapel = [];
        $rapor = null;
        $nilai = [];
        $rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];

        if ($kelas && $semester) {
            $siswa = DB::table('keanggotaan_kelas as kk')
                ->join('siswa as s', 's.id_siswa', '=', 'kk.id_siswa')
                ->where('kk.id_kelas', $kelas['id'])
                ->where('kk.id_semester', $semester->id_semester)
                ->where('kk.status', 'aktif')
                ->orderBy('s.nama_lengkap')
                ->get(['kk.id_keanggotaan', 's.id_siswa', 's.nama_lengkap', 's.nisn'])
                ->map(fn ($s) => [
                    'id' => $s->id_siswa,
                    'id_keanggotaan' => $s->id_keanggotaan,
                    'nama' => $s->nama_lengkap,
                    'nisn' => $s->nisn ?? '-',
                ])
                ->all();

            $idSiswa = (int) $request->query('siswa', $siswa[0]['id'] ?? 0);
            $terpilih = collect($siswa)->firstWhere('id', $idSiswa) ?? ($siswa[0] ?? null);

            $mapel = DB::table('jadwal_pelajaran as j')
                ->join('mata_pelajaran as m', 'm.id_mata_pelajaran', '=', 'j.id_mata_pelajaran')
                ->where('j.id_kelas', $kelas['id'])
                ->where('j.id_semester', $semester->id_semester)
                ->distinct()
                ->orderBy('m.nama_mapel')
                ->get(['m.id_mata_pelajaran', 'm.nama_mapel'])
                ->map(fn ($m) => ['id' => $m->id_mata_pelajaran, 'nama' => $m->nama_mapel])
                ->all();

            if (!$mapel) {
                $mapel = DB::table('mata_pelajaran')
                    ->where('is_aktif', true)
                    ->orderBy('nama_mapel')
                    ->get(['id_mata_pelajaran', 'nama_mapel'])
                    ->map(fn ($m) => ['id' => $m->id_mata_pelajaran, 'nama' => $m->nama_mapel])
                    ->all();
            }

            if ($terpilih) {
                $rapor = DB::table('rapor')
                    ->where('id_anggota', $terpilih['id_keanggotaan'])
                    ->where('id_semester', $semester->id_semester)
                    ->first();

                if ($rapor) {
                    $nilai = DB::table('detail_rapor')
                        ->where('id_rapor', $rapor->id_rapor)
                        ->pluck('nilai_akhir', 'id_mapel')
                        ->all();
                }

                $rekapRows = DB::table('detail_absensi as da')
                    ->join('sesi_absensi as sa', 'sa.id_sesi', '=', 'da.id_sesi')
                    ->join('jadwal_pelajaran as j', 'j.id_jadwal', '=', 'sa.id_jadwal')
                    ->join('status_kehadiran as sh', 'sh.id_status_hadir', '=', 'da.id_status_hadir')
                    ->where('da.id_siswa', $terpilih['id'])
                    ->where('j.id_kelas', $kelas['id'])
                    ->where('j.id_semester', $semester->id_semester)
                    ->select('sh.kode', DB::raw('COUNT(*) AS jumlah'))
                    ->groupBy('sh.kode')
                    ->pluck('jumlah', 'kode')
                    ->all();

                foreach ($rekapRows as $kode => $jumlah) {
                    $kode = strtolower((string) $kode);

                    if (isset($rekap[$kode])) {
                        $rekap[$kode] += (int) $jumlah;
                    }
                }
            }
        }

        return view('guru.rapor', compact('kelasWali', 'kelas', 'semester', 'siswa', 'terpilih', 'mapel', 'rapor', 'nilai', 'rekap'));
    }

    // ---- SISWA: akademik ----

    public function jadwalSiswa()
    {
        $siswa = $this->siswaSesi();
        $semester = $this->semesterAktif();
        $kelas = null;
        $jadwal = [];

        if ($siswa && $semester) {
            $anggota = DB::table('keanggotaan_kelas as kk')
                ->join('kelas as k', 'k.id_kelas', '=', 'kk.id_kelas')
                ->where('kk.id_siswa', $siswa->id_siswa)
                ->where('kk.id_semester', $semester->id_semester)
                ->orderByDesc('kk.id_keanggotaan')
                ->select('kk.id_kelas', 'k.nama_kelas')
                ->first();

            if ($anggota) {
                $kelas = $anggota->nama_kelas;
                $hari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

                $rows = DB::table('jadwal_pelajaran as j')
                    ->join('mata_pelajaran as m', 'm.id_mata_pelajaran', '=', 'j.id_mata_pelajaran')
                    ->join('guru as g', 'g.id_guru', '=', 'j.id_guru')
                    ->where('j.id_kelas', $anggota->id_kelas)
                    ->where('j.id_semester', $semester->id_semester)
                    ->orderBy('j.hari')
                    ->orderBy('j.jam_mulai')
                    ->get(['j.hari', 'j.jam_mulai', 'j.jam_selesai', 'j.ruang', 'm.nama_mapel', 'g.nama_guru']);

                foreach ($rows as $r) {
                    $label = $hari[(int) $r->hari] ?? 'Hari lain';
                    $jadwal[$label][] = [
                        'mapel' => $r->nama_mapel,
                        'guru' => $r->nama_guru,
                        'mulai' => substr((string) $r->jam_mulai, 0, 5),
                        'selesai' => substr((string) $r->jam_selesai, 0, 5),
                        'ruang' => $r->ruang ?: '-',
                    ];
                }
            }
        }

        return view('siswa.jadwal', compact('jadwal', 'kelas', 'semester'));
    }

    public function absensiSiswa()
    {
        $siswa = $this->siswaSesi();
        $rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];
        $riwayat = [];

        if ($siswa) {
            $rows = DB::table('detail_absensi as da')
                ->join('sesi_absensi as sa', 'sa.id_sesi', '=', 'da.id_sesi')
                ->join('status_kehadiran as sh', 'sh.id_status_hadir', '=', 'da.id_status_hadir')
                ->leftJoin('jadwal_pelajaran as j', 'j.id_jadwal', '=', 'sa.id_jadwal')
                ->leftJoin('mata_pelajaran as m', 'm.id_mata_pelajaran', '=', 'j.id_mata_pelajaran')
                ->leftJoin('guru as g', 'g.id_guru', '=', 'j.id_guru')
                ->where('da.id_siswa', $siswa->id_siswa)
                ->orderByDesc('sa.tanggal')
                ->orderByDesc('sa.id_sesi')
                ->limit(200)
                ->get(['sa.tanggal', 'sh.kode', 'sh.nama_status', 'da.keterangan', 'm.nama_mapel', 'g.nama_guru']);

            foreach ($rows as $r) {
                $kode = strtolower((string) $r->kode);

                if (isset($rekap[$kode])) {
                    $rekap[$kode]++;
                }

                $riwayat[] = [
                    'tanggal' => Carbon::parse($r->tanggal)->locale('id')->translatedFormat('d M Y'),
                    'status' => $r->nama_status,
                    'kode' => $kode,
                    'keterangan' => $r->keterangan ?: '-',
                    'mapel' => $r->nama_mapel ?? '-',
                    'guru' => $r->nama_guru ?? '-',
                ];
            }
        }

        return view('siswa.absensi', compact('rekap', 'riwayat'));
    }

    public function raporSiswa()
    {
        $siswa = $this->siswaSesi();
        $semester = $this->semesterAktif();
        $kelas = null;
        $rapor = null;
        $detail = [];

        if ($siswa && $semester) {
            $anggota = DB::table('keanggotaan_kelas as kk')
                ->join('kelas as k', 'k.id_kelas', '=', 'kk.id_kelas')
                ->where('kk.id_siswa', $siswa->id_siswa)
                ->where('kk.id_semester', $semester->id_semester)
                ->orderByDesc('kk.id_keanggotaan')
                ->select('kk.id_keanggotaan', 'k.nama_kelas')
                ->first();

            if ($anggota) {
                $kelas = $anggota->nama_kelas;
                $rapor = DB::table('rapor')
                    ->where('id_anggota', $anggota->id_keanggotaan)
                    ->where('id_semester', $semester->id_semester)
                    ->first();

                if ($rapor) {
                    $detail = DB::table('detail_rapor as dr')
                        ->join('mata_pelajaran as m', 'm.id_mata_pelajaran', '=', 'dr.id_mapel')
                        ->leftJoin('skala_predikat as sp', 'sp.id_predikat', '=', 'dr.id_predikat')
                        ->where('dr.id_rapor', $rapor->id_rapor)
                        ->orderBy('m.nama_mapel')
                        ->get(['m.nama_mapel', 'dr.nilai_akhir', 'sp.huruf'])
                        ->map(fn ($d) => [
                            'mapel' => $d->nama_mapel,
                            'nilai' => rtrim(rtrim(number_format((float) $d->nilai_akhir, 2, '.', ''), '0'), '.'),
                            'predikat' => $d->huruf ?? '-',
                        ])
                        ->all();
                }
            }
        }

        return view('siswa.rapor', compact('semester', 'kelas', 'rapor', 'detail'));
    }

    // ---- TU ----
    public function tuDashboard() { return view('tu.dashboard', ['antrean' => $this->daftarAntrean(), 'kelas' => DummyData::kelengkapanKelas()]); }
    public function verifikasi(int $id)
    {
        $pendaftaran = DB::table('pendaftaran_ppdb')->where('id_pendaftaran', $id)->first();

        if ($pendaftaran) {
            $row = [
                'id' => $pendaftaran->id_pendaftaran,
                'nama' => $pendaftaran->nama_calon_siswa,
                'nisn' => $pendaftaran->nisn ?? '-',
                'jenis' => 'PPDB baru',
                'tgl' => Carbon::parse($pendaftaran->diajukan_pada)->locale('id')->translatedFormat('d M Y'),
                'status' => $this->statusTampilan($pendaftaran->status_pendaftaran),
            ];
        } else {
            $row = collect(DummyData::antrean())->firstWhere('id', $id) ?? abort(404);
        }

        $checklist = [];
        $item = DB::table('ref_checklist_item as ri')
            ->join('jenis_dokumen as jd', 'jd.id_jenis_dokumen', '=', 'ri.id_jenis_dokumen')
            ->orderBy('jd.nama_dokumen')
            ->orderBy('ri.id_item')
            ->get(['jd.nama_dokumen', 'ri.nama_item']);

        foreach ($item as $i) {
            $checklist[$i->nama_dokumen][] = $i->nama_item;
        }

        $alasan = DB::table('ref_alasan_pengembalian')
            ->orderBy('nama_alasan')
            ->pluck('nama_alasan')
            ->all();

        return view('tu.verifikasi', [
            'row' => $row,
            'checklist' => $checklist ?: DummyData::checklist(),
            'alasan' => $alasan ?: DummyData::alasanPengembalian(),
        ]);
    }
    public function persetujuan() { return view('tu.persetujuan', ['pengajuan' => $this->daftarPengajuan()]); }
    public function antrean()     { return view('tu.antrean', ['antrean' => $this->daftarAntrean()]); }
    public function kelulusan()   { return view('tu.kelulusan', ['pendaftar' => DummyData::kelulusan()]); }
    public function dokumenSiswa(int $id)
    {
        // Nama diambil dari database bila tersedia; kalau tidak, halaman tetap tampil dengan data contoh.
        try {
            $nama = DB::table('siswa')->where('id_siswa', $id)->value('nama_lengkap');
        } catch (\Throwable $e) {
            $nama = null;
        }

        $dokumen = DB::table('dokumen_siswa')
            ->where('id_siswa', $id)
            ->orderByDesc('diunggah_pada')
            ->get()
            ->map(function ($d) {
                $path = $d->path_file;
                $ext = strtoupper(pathinfo($path, PATHINFO_EXTENSION) ?: '-');
                $ukuran = '-';

                try {
                    if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                        $ukuran = number_format(\Illuminate\Support\Facades\Storage::disk('public')->size($path) / 1024, 0) . ' KB';
                    }
                } catch (\Throwable $e) {
                    $ukuran = '-';
                }

                return [
                    'id_dokumen' => $d->id_dokumen,
                    'jenis' => $d->jenis_dokumen,
                    'nomor' => $d->catatan ?: '-',
                    'format' => $ext,
                    'ukuran' => $ukuran,
                    'fisik' => $d->status_verifikasi === 'valid',
                    'tgl' => Carbon::parse($d->diunggah_pada)->locale('id')->translatedFormat('d M Y'),
                ];
            })
            ->all();

        $jenis = DB::table('jenis_dokumen')->orderBy('nama_dokumen')->pluck('nama_dokumen')->all();

        return view('tu.siswa-dokumen', [
            'id'      => $id,
            'nama'    => $nama,
            'dokumen' => $dokumen ?: DummyData::dokumenSiswa(),
            'jenis'   => $jenis ?: ['Kartu Keluarga', 'Akta Kelahiran', 'Ijazah / SKL', 'Pas Foto 3x4', 'Lainnya'],
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
    public function periode()
    {
        $periode = DB::table('periode_update as p')
            ->leftJoin('tahun_ajaran as t', 't.id_tahun_ajaran', '=', 'p.id_tahun_ajaran')
            ->orderByDesc('p.tanggal_mulai')
            ->get(['p.id_periode_update', 'p.tanggal_mulai', 'p.tanggal_selesai', 'p.status', 't.nama_tahun_ajaran', 'p.nama_periode'])
            ->map(fn ($p) => [
                'id' => $p->id_periode_update,
                'tahun' => $p->nama_tahun_ajaran ?? $p->nama_periode,
                'buka' => Carbon::parse($p->tanggal_mulai)->locale('id')->translatedFormat('d M Y'),
                'tutup' => Carbon::parse($p->tanggal_selesai)->locale('id')->translatedFormat('d M Y'),
                'status' => $p->status === 'aktif' ? 'Dibuka' : 'Ditutup',
            ])
            ->all();

        return view('tu.periode', [
            'periode' => $periode ?: DummyData::periodeUpdate(),
            'tahunAjaran' => $this->daftarTahunAjaran(),
        ]);
    }
    public function kelas()       { return view('tu.kelas', ['kelas' => DummyData::kelas()]); }
    public function kelasDetail(int $id)
    {
        $row = collect(DummyData::kelas())->firstWhere('id', $id) ?? abort(404);

        return view('tu.kelas-detail', ['row' => $row, 'siswa' => DummyData::siswaKelas()]);
    }
    public function penempatan()
    {
        $belum = DB::table('siswa as s')
            ->leftJoin('keanggotaan_kelas as kk', 'kk.id_siswa', '=', 's.id_siswa')
            ->whereNull('kk.id_keanggotaan')
            ->orderBy('s.nama_lengkap')
            ->get(['s.id_siswa', 's.nama_lengkap', 's.nisn'])
            ->map(fn ($s) => [
                'id' => $s->id_siswa,
                'nama' => $s->nama_lengkap,
                'nisn' => $s->nisn ?? '-',
            ])
            ->all();

        return view('tu.penempatan', [
            'belum' => $belum ?: DummyData::belumBerkelas(),
            'kelas' => $this->daftarKelasRingkas(),
        ]);
    }
    public function kenaikan()
    {
        return view('tu.kenaikan', [
            'siswa' => $this->daftarSiswaRingkas(),
            'kelas' => $this->daftarKelasRingkas(),
        ]);
    }

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

    // ---- MUTASI SISWA ----
    public function mutasiTu()     { return $this->halamanMutasi('tu'); }
    public function mutasiKepsek() { return $this->halamanMutasi('kepsek'); }

    private function halamanMutasi(string $role)
    {
        $rows = DB::table('mutasi_siswa as m')
            ->join('siswa as s', 's.id_siswa', '=', 'm.id_siswa')
            ->leftJoin('sekolah as sk', 'sk.id_sekolah', '=', 'm.id_sekolah_tujuan')
            ->leftJoin('pengguna as pj', 'pj.id_pengguna', '=', 'm.id_pengaju')
            ->leftJoin('pengguna as pt', 'pt.id_pengguna', '=', 'm.id_penyetuju')
            ->orderByDesc('m.tgl_pengajuan')
            ->get([
                'm.id_mutasi', 'm.jenis', 'm.alasan', 'm.status', 'm.tgl_pengajuan', 'm.tgl_keputusan',
                's.nama_lengkap', 's.nisn', 'sk.nama_sekolah', 'pj.username as pengaju', 'pt.username as penyetuju',
            ])
            ->map(fn ($r) => [
                'id' => $r->id_mutasi,
                'nama' => $r->nama_lengkap,
                'nisn' => $r->nisn ?? '-',
                'jenis' => match ($r->jenis) {
                    'pindah_masuk' => 'Pindah masuk',
                    'pindah_keluar' => 'Pindah keluar',
                    'keluar' => 'Keluar',
                    default => $r->jenis,
                },
                'alasan' => $r->alasan ?: '-',
                'status' => $r->status,
                'tgl' => $r->tgl_pengajuan
                    ? Carbon::parse($r->tgl_pengajuan)->locale('id')->translatedFormat('d M Y')
                    : '-',
                'keputusan' => $r->tgl_keputusan
                    ? Carbon::parse($r->tgl_keputusan)->locale('id')->translatedFormat('d M Y')
                    : '-',
                'pengaju' => $r->pengaju ?? '-',
                'penyetuju' => $r->penyetuju ?? '-',
                'sekolah' => $r->nama_sekolah ?? '-',
            ])
            ->all();

        $sekolah = DB::table('sekolah')
            ->orderBy('nama_sekolah')
            ->get(['id_sekolah', 'nama_sekolah'])
            ->map(fn ($s) => ['id' => $s->id_sekolah, 'nama' => $s->nama_sekolah])
            ->all();

        $prefix = $role === 'kepsek' ? 'kepsek' : 'tu';

        return view('mutasi.index', [
            'mutasi' => $rows,
            'siswa' => $this->daftarSiswaRingkas(),
            'sekolah' => $sekolah,
            'judul' => $role === 'kepsek' ? 'Persetujuan Mutasi Siswa' : 'Mutasi Siswa',
            'sub' => $role === 'kepsek'
                ? 'Kepala sekolah dan Tata Usaha berwenang memutuskan permohonan mutasi siswa.'
                : 'Ajukan mutasi siswa, lalu pantau keputusan Kepala Sekolah atau Tata Usaha.',
            'bisaAjukan' => $role === 'tu',
            'simpanRoute' => 'tu.mutasi.simpan',
            'putuskanRoute' => $prefix . '.mutasi.putuskan',
            'kembaliRoute' => $prefix . '.mutasi',
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

    /* ---------------- data tampilan dari database ---------------- */

    /** Data siswa milik akun yang sedang masuk (null bila belum terhubung). */
    private function siswaSesi(): ?Siswa
    {
        if (!session('id_pengguna')) {
            return null;
        }

        return Siswa::where('id_pengguna', session('id_pengguna'))->first();
    }

    /** Data siswa untuk halaman profil/konfirmasi, dengan data contoh bila kosong. */
    private function siswaTampilan(): array
    {
        $s = $this->siswaSesi();

        if (!$s) {
            return DummyData::siswa();
        }

        $alamat = DB::table('alamat_siswa')->where('id_siswa', $s->id_siswa)->first();
        $ayah = DB::table('orang_tua_wali')
            ->where('id_siswa', $s->id_siswa)
            ->whereRaw("lower(hubungan) = 'ayah'")
            ->value('nama_lengkap');
        $ibu = DB::table('orang_tua_wali')
            ->where('id_siswa', $s->id_siswa)
            ->whereRaw("lower(hubungan) = 'ibu'")
            ->value('nama_lengkap');
        $kelas = DB::table('keanggotaan_kelas as kk')
            ->join('kelas as k', 'k.id_kelas', '=', 'kk.id_kelas')
            ->where('kk.id_siswa', $s->id_siswa)
            ->orderByDesc('kk.id_keanggotaan')
            ->value('k.nama_kelas');

        return [
            'nama_lengkap' => $s->nama_lengkap,
            'nisn' => $s->nisn ?? '-',
            'nik' => $s->nik ?? '-',
            'no_kk' => '-',
            'jenis_kelamin' => match ($s->jenis_kelamin) {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => '-',
            },
            'tempat_lahir' => $s->tempat_lahir ?? '-',
            'tanggal_lahir' => $s->tanggal_lahir
                ? Carbon::parse($s->tanggal_lahir)->locale('id')->translatedFormat('d F Y')
                : '-',
            'agama' => DB::table('agama')->where('id_agama', $s->id_agama)->value('nama_agama') ?? '-',
            'no_hp' => $s->no_telepon ?? '-',
            'sekolah_asal' => $s->id_sekolah_asal
                ? (DB::table('sekolah')->where('id_sekolah', $s->id_sekolah_asal)->value('nama_sekolah') ?? '-')
                : '-',
            'kelas' => $kelas ?? '-',
            'status' => DB::table('status_siswa')->where('id_status_siswa', $s->id_status_siswa)->value('nama_status') ?? '-',
            'alamat' => $alamat->alamat ?? '-',
            'ayah' => $ayah ?? '-',
            'ibu' => $ibu ?? '-',
        ];
    }

    /** Periode update yang sedang berjalan; data contoh dipakai bila tabel masih kosong. */
    private function periodeBerjalan(): array
    {
        $periode = DB::table('periode_update')
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_periode_update')
            ->first();

        if (!$periode) {
            return DummyData::periodeAktif() + ['id' => null];
        }

        $tahun = DB::table('tahun_ajaran')
            ->where('id_tahun_ajaran', $periode->id_tahun_ajaran)
            ->value('nama_tahun_ajaran');

        return [
            'dibuka' => $periode->status === 'aktif',
            'id' => $periode->id_periode_update,
            'tahun' => $tahun ?? $periode->nama_periode,
            'tutup' => Carbon::parse($periode->tanggal_selesai)->locale('id')->translatedFormat('d M Y'),
        ];
    }

    private function daftarTahunAjaran(): array
    {
        $rows = DB::table('tahun_ajaran')
            ->orderByDesc('tanggal_mulai')
            ->get(['id_tahun_ajaran', 'nama_tahun_ajaran']);

        if ($rows->isEmpty()) {
            return [
                ['id' => null, 'nama' => '2026/2027'],
                ['id' => null, 'nama' => '2027/2028'],
            ];
        }

        return $rows->map(fn ($r) => [
            'id' => $r->id_tahun_ajaran,
            'nama' => $r->nama_tahun_ajaran,
        ])->all();
    }

    private function daftarPengajuanSiswa(?int $idSiswa): array
    {
        if (!DB::table('pengajuan_perubahan')->exists()) {
            return DummyData::pengajuanSiswa();
        }

        $query = DB::table('pengajuan_perubahan as pp')
            ->join('detail_pengajuan as dp', 'dp.id_pengajuan', '=', 'pp.id_pengajuan')
            ->leftJoin('siswa as s', 's.id_siswa', '=', 'pp.id_siswa')
            ->select(
                'pp.id_pengajuan',
                'pp.status_pengajuan',
                'pp.tgl_pengajuan',
                'dp.nama_field',
                'dp.nilai_lama',
                'dp.nilai_baru',
                's.nama_lengkap'
            )
            ->orderByDesc('pp.tgl_pengajuan');

        if ($idSiswa) {
            $query->where('pp.id_siswa', $idSiswa);
        }

        return $query->get()->map(fn ($p) => $this->barisPengajuan($p))->all();
    }

    private function daftarPengajuan(): array
    {
        if (!DB::table('pengajuan_perubahan')->exists()) {
            return DummyData::pengajuanSiswa();
        }

        return DB::table('pengajuan_perubahan as pp')
            ->join('detail_pengajuan as dp', 'dp.id_pengajuan', '=', 'pp.id_pengajuan')
            ->leftJoin('siswa as s', 's.id_siswa', '=', 'pp.id_siswa')
            ->select(
                'pp.id_pengajuan',
                'pp.status_pengajuan',
                'pp.tgl_pengajuan',
                'dp.nama_field',
                'dp.nilai_lama',
                'dp.nilai_baru',
                's.nama_lengkap'
            )
            ->orderByDesc('pp.tgl_pengajuan')
            ->get()
            ->map(fn ($p) => $this->barisPengajuan($p))
            ->all();
    }

    private function barisPengajuan(object $p): array
    {
        $teks = function ($nilai): string {
            if ($nilai === null) {
                return '-';
            }

            if (is_string($nilai)) {
                $decoded = json_decode($nilai, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $nilai = $decoded;
                }
            }

            if (is_array($nilai)) {
                return json_encode($nilai, JSON_UNESCAPED_UNICODE) ?: '-';
            }

            return (string) $nilai;
        };

        return [
            'id' => $p->id_pengajuan,
            'nama' => $p->nama_lengkap ?? 'Siswa',
            'field' => $p->nama_field,
            'lama' => $teks($p->nilai_lama),
            'baru' => $teks($p->nilai_baru),
            'tgl' => $p->tgl_pengajuan
                ? Carbon::parse($p->tgl_pengajuan)->locale('id')->translatedFormat('d M Y')
                : '-',
            'status' => $this->statusTampilan($p->status_pengajuan),
        ];
    }

    private function daftarAntrean(): array
    {
        $rows = DB::table('pendaftaran_ppdb')
            ->orderByDesc('diajukan_pada')
            ->get(['id_pendaftaran', 'nama_calon_siswa', 'nisn', 'diajukan_pada', 'status_pendaftaran']);

        if ($rows->isEmpty()) {
            return DummyData::antrean();
        }

        return $rows->map(fn ($r) => [
            'id' => $r->id_pendaftaran,
            'nama' => $r->nama_calon_siswa,
            'nisn' => $r->nisn ?? '-',
            'jenis' => 'PPDB baru',
            'tgl' => Carbon::parse($r->diajukan_pada)->locale('id')->translatedFormat('d M Y'),
            'status' => $this->statusTampilan($r->status_pendaftaran),
        ])->all();
    }

    private function daftarSiswaRingkas(): array
    {
        $rows = DB::table('siswa')->orderBy('nama_lengkap')->get(['id_siswa', 'nama_lengkap', 'nisn']);

        if ($rows->isEmpty()) {
            return DummyData::siswaKelas();
        }

        return $rows->map(fn ($s) => [
            'id' => $s->id_siswa,
            'nama' => $s->nama_lengkap,
            'nisn' => $s->nisn ?? '-',
        ])->all();
    }

    private function daftarKelasRingkas(): array
    {
        $rows = DB::table('kelas')
            ->where('is_aktif', true)
            ->orderBy('nama_kelas')
            ->get(['id_kelas', 'nama_kelas']);

        if ($rows->isEmpty()) {
            return DummyData::kelas();
        }

        return $rows->map(fn ($k) => [
            'id' => $k->id_kelas,
            'nama' => $k->nama_kelas,
        ])->all();
    }

    /** Data guru milik akun yang sedang masuk. */
    private function guruSesi(): ?object
    {
        if (!session('id_pengguna')) {
            return null;
        }

        return DB::table('guru')->where('id_pengguna', session('id_pengguna'))->first();
    }

    /** Semester aktif; bila belum ada, semester terbaru dipakai untuk tampilan. */
    private function semesterAktif(): ?object
    {
        return DB::table('semester')
            ->where('is_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_semester')
            ->first()
            ?? DB::table('semester')
                ->orderByDesc('tanggal_mulai')
                ->orderByDesc('id_semester')
                ->first();
    }

    /** Kelas yang diwalikan guru pada semester tertentu. */
    private function kelasWali(int $idGuru, int $idSemester): array
    {
        return DB::table('penugasan_wali_kelas as pw')
            ->join('kelas as k', 'k.id_kelas', '=', 'pw.id_kelas')
            ->where('pw.id_guru', $idGuru)
            ->where('pw.id_semester', $idSemester)
            ->orderBy('k.nama_kelas')
            ->get(['k.id_kelas', 'k.nama_kelas'])
            ->map(fn ($k) => ['id' => $k->id_kelas, 'nama' => $k->nama_kelas])
            ->all();
    }

    /** Tanggal dari query string; kembali ke hari ini bila tidak valid. */
    private function tanggalValid($nilai): string
    {
        try {
            return Carbon::parse($nilai ?: now()->toDateString())->toDateString();
        } catch (\Throwable $e) {
            return now()->toDateString();
        }
    }

    /** Samakan status database dengan label/badge yang dipakai tampilan. */
    private function statusTampilan(?string $status): string
    {
        return match ($status) {
            'perlu_perbaikan' => 'dikembalikan',
            'diterima' => 'disahkan',
            'diverifikasi' => 'disetujui',
            default => $status ?: 'menunggu',
        };
    }

    private function langkahStatusPendaftaran(object $row): array
    {
        $status = $row->status_pendaftaran;
        $diperiksa = $status !== 'menunggu';
        $catatan = $row->catatan_verifikasi;

        $verifikasi = match ($status) {
            'perlu_perbaikan' => [
                'judul' => 'Berkas perlu diperbaiki',
                'ket' => $catatan ?: 'Perbaiki berkas lalu unggah ulang.',
                'state' => 'perhatian',
            ],
            'ditolak' => [
                'judul' => 'Pendaftaran ditolak',
                'ket' => $catatan ?: 'Silakan hubungi Tata Usaha.',
                'state' => 'perhatian',
            ],
            'diverifikasi', 'diterima' => [
                'judul' => 'Berkas diterima',
                'ket' => $catatan ?: 'Berkas sudah sesuai.',
                'state' => 'selesai',
            ],
            default => [
                'judul' => 'Pemeriksaan berkas',
                'ket' => 'Menunggu pemeriksaan Tata Usaha.',
                'state' => 'belum',
            ],
        };

        return [
            [
                'judul' => 'Formulir terkirim',
                'ket' => Carbon::parse($row->diajukan_pada)->locale('id')->translatedFormat('d M Y'),
                'state' => 'selesai',
            ],
            [
                'judul' => 'Diperiksa Tata Usaha',
                'ket' => $diperiksa ? 'Sudah diperiksa' : 'Menunggu',
                'state' => $diperiksa ? 'selesai' : 'belum',
            ],
            $verifikasi,
            [
                'judul' => 'Pengesahan',
                'ket' => $status === 'diterima'
                    ? 'Diterima sebagai siswa.'
                    : ($status === 'ditolak' ? 'Tidak dilanjutkan.' : 'Menunggu'),
                'state' => $status === 'diterima' ? 'selesai' : 'belum',
            ],
            [
                'judul' => 'Akun siswa dibuat',
                'ket' => $status === 'diterima'
                    ? 'Akun dibuat otomatis memakai NISN.'
                    : 'Menunggu',
                'state' => $status === 'diterima' ? 'selesai' : 'belum',
            ],
        ];
    }
}