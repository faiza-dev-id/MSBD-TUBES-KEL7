<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\LogAudit;
use App\Models\LogLogin;
use App\Models\MutasiSiswa;
use App\Models\Notifikasi;
use App\Models\Pengguna;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\Staf;
use App\Services\BerkasPreviewService;
use App\Services\KenaikanKelasService;
use App\Services\PengajuanPerubahanService;
use App\Services\PengaturanService;
use App\Services\PpdbService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    /** Cache label field pengajuan per request (nama_kolom => label). */
    private static array $labelFieldCache = [];

    /** Arahkan ke beranda sesuai role. */
    public function beranda()
    {
        return match (session('role')) {
            'siswa' => redirect()->route('siswa.dashboard'),
            'wali_kelas' => redirect()->route('guru.kelas'),
            'tu' => redirect()->route('tu.dashboard'),
            'kepsek' => redirect()->route('kepsek.dashboard'),
            default => redirect()->route('login'),
        };
    }

    /** Daftar notifikasi milik pengguna yang sedang login. */
    public function notifikasi()
    {
        $idPengguna = session('id_pengguna');

        abort_unless($idPengguna, 404);

        $daftar = Notifikasi::where('id_pengguna', $idPengguna)
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('id_notifikasi')
            ->paginate(15);

        $belumDibaca = Notifikasi::where('id_pengguna', $idPengguna)
            ->where('dibaca', false)
            ->count();

        return view('notifikasi', [
            'daftar' => $daftar,
            'belumDibaca' => $belumDibaca,
        ]);
    }

    /** Beranda publik (hero slider + statistik singkat). */
    public function home()
    {
        $slides = [
            [
                'awal' => 'Gerbang PPDB',
                'miring' => '& Layanan Data Siswa',
                'akhir' => '',
                'deskripsi' => 'Portal informasi resmi sekolah: pendaftaran peserta didik baru, cek status seleksi, dan layanan informasi akademik resmi SMA Methodist 2 Medan.',
                'gambar' => $this->gambarPublik('images/sekolah.jpeg'),
                'tombol' => [
                    ['label' => 'Daftar PPDB', 'url' => route('ppdb')],
                    ['label' => 'Cek status pendaftaran', 'url' => route('ppdb.status')],
                ],
            ],
            [
                'awal' => 'Penerimaan Peserta Didik',
                'miring' => 'Baru',
                'akhir' => ' sedang dibuka',
                'deskripsi' => 'Isi formulir secara online, unggah berkas, lalu pantau hasil verifikasi dari Tata Usaha kapan saja.',
                'gambar' => $this->gambarPublik('images/sekolah.jpeg'),
                'tombol' => [
                    ['label' => 'Mulai mendaftar', 'url' => route('ppdb')],
                    ['label' => 'Masuk ke SIAKAD', 'url' => route('login')],
                ],
            ],
        ];

        $statistik = array_values(array_filter([
            ['ikon' => 'gedung', 'nilai' => '',           'label' => 'Gedung 3 Lantai'],
            ['ikon' => 'buku',   'nilai' => '',           'label' => 'Kurikulum Merdeka'],
            ['ikon' => 'piala',  'nilai' => '',           'label' => 'Prestasi Tinggi'],
            ($n = $this->jumlahSiswaAktif()) > 0
                ? ['ikon' => 'siswa', 'nilai' => number_format($n, 0, ',', '.'), 'label' => 'Siswa Aktif']
                : null,
        ]));

        return view('home', [
            'slides' => $slides,
            'statistik' => $statistik,
            'akreditasi' => config('sekolah.akreditasi'),
            'npsn' => config('sekolah.npsn'),
        ]);
    }

    /** Jumlah siswa berstatus aktif; 0 bila tabel kosong atau query gagal. */
    private function jumlahSiswaAktif(): int
    {
        try {
            $aktif = DB::table('siswa as s')
                ->join('status_siswa as st', 'st.id_status_siswa', '=', 's.id_status_siswa')
                ->whereRaw("lower(st.nama_status) = 'aktif'")
                ->count();

            return $aktif > 0 ? $aktif : DB::table('siswa')->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Path gambar di folder public bila filenya ada, selain itu null (dipakai gradasi). */
    private function gambarPublik(string $path): ?string
    {
        return is_file(public_path($path)) ? $path : null;
    }

    // public halaman login
    public function masuk(): View
    {
        return view('auth.login');
    }

    // ---- PUBLIK (calon siswa) ----
    public function ppdb(Request $request, PpdbService $ppdb)
    {
        $hasil = $ppdb->form($request->query('draf'));
        $form = $this->gabungOldPpdb($hasil['data'], $request);
        $kelasTujuan = $ppdb->kelasTujuan();

        if ($hasil['perbaikan'] && filled($hasil['data']['kelas_tujuan'] ?? null)) {
            $form['kelas_tujuan'] = $hasil['data']['kelas_tujuan'];
            $kelasTujuan[] = $hasil['data']['kelas_tujuan'];
            $kelasTujuan = array_values(array_unique($kelasTujuan));
        }

        return view('siswa.ppdb', [
            'form' => $form,
            'token_draf' => $hasil['token'],
            'berkas' => $hasil['berkas'],
            'modePerbaikan' => $hasil['perbaikan'],
            'kelasTujuan' => $kelasTujuan,
            'agamaOptions' => $ppdb->agamaOptions(),
            'maksBerkasMb' => (int) (app(PengaturanService::class)->nilai('ukuran_berkas_mb') ?: 2),
        ]);
    }

    private function gabungOldPpdb(array $data, Request $request): array
    {
        if (! $request->hasSession()) {
            return $data;
        }

        $lama = $request->old();

        if (! is_array($lama) || $lama === []) {
            return $data;
        }

        foreach ($data as $kunci => $nilai) {
            if (! is_array($nilai) && array_key_exists($kunci, $lama) && ! is_array($lama[$kunci])) {
                $data[$kunci] = $lama[$kunci];
            }
        }

        foreach (['provinsi' => 'prov', 'kecamatan' => 'kec', 'kelurahan' => 'kel'] as $tujuan => $asal) {
            if (array_key_exists($asal, $lama) && ! is_array($lama[$asal])) {
                $data[$tujuan] = $lama[$asal];
            }
        }

        $petaOrtu = [
            'nama' => 'ortu_nama_',
            'nik' => 'ortu_nik_',
            'tanggal_lahir' => 'ortu_tanggal_lahir_',
            'tempat_lahir' => 'ortu_tempat_lahir_',
            'pendidikan' => 'ortu_pendidikan_',
            'pekerjaan' => 'ortu_kerja_',
            'penghasilan' => 'ortu_penghasilan_',
            'kebutuhan' => 'ortu_kebutuhan_',
            'hp' => 'ortu_hp_',
            'alamat' => 'ortu_alamat_',
        ];

        foreach ([0, 1, 2] as $i) {
            foreach ($petaOrtu as $tujuan => $awal) {
                $kunci = $awal.$i;
                if (array_key_exists($kunci, $lama) && ! is_array($lama[$kunci])) {
                    $data['ortu'][$i][$tujuan] = $lama[$kunci];
                }
            }
        }

        if (isset($lama['prestasi_nama']) && is_array($lama['prestasi_nama'])) {
            $data['prestasi'] = [];
            foreach ($lama['prestasi_nama'] as $i => $nama) {
                $data['prestasi'][] = [
                    'jenis' => $lama['prestasi_jenis'][$i] ?? '',
                    'tingkat' => $lama['prestasi_tingkat'][$i] ?? '',
                    'nama' => $nama,
                    'tahun' => $lama['prestasi_tahun'][$i] ?? '',
                    'penyelenggara' => $lama['prestasi_penyelenggara'][$i] ?? '',
                ];
            }
        } elseif (($lama['prestasi_dikelola'] ?? null) === '1') {
            $data['prestasi'] = [];
        }

        if (isset($lama['beasiswa_keterangan']) && is_array($lama['beasiswa_keterangan'])) {
            $data['beasiswa'] = [];
            foreach ($lama['beasiswa_keterangan'] as $i => $keterangan) {
                $data['beasiswa'][] = [
                    'jenis' => $lama['beasiswa_jenis'][$i] ?? '',
                    'keterangan' => $keterangan,
                    'tahun_mulai' => $lama['beasiswa_tahun_mulai'][$i] ?? '',
                    'tahun_selesai' => $lama['beasiswa_tahun_selesai'][$i] ?? '',
                ];
            }
        } elseif (($lama['beasiswa_dikelola'] ?? null) === '1') {
            $data['beasiswa'] = [];
        }

        return $data;
    }

    public function status(Request $request)
    {
        $langkah = [];
        $hasil = null;
        $berkas = collect();
        $bolehPerbaiki = false;

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
                $bolehPerbaiki = $request->filled('cek_nisn')
                    && $request->filled('cek_kode')
                    && trim((string) $request->query('cek_nisn')) === (string) $baris->nisn
                    && trim((string) $request->query('cek_kode')) === (string) $baris->nomor_pendaftaran
                    && ! empty($baris->token_draf)
                    && in_array($baris->status_pendaftaran, ['perlu_perbaikan', 'ditolak'], true);
                $berkas = DB::table('dokumen_siswa')
                    ->where('id_pendaftaran', $baris->id_pendaftaran)
                    ->orderBy('id_dokumen')
                    ->get();
                $langkah = $this->langkahStatusPendaftaran($baris);
                $request->flash();
            } else {
                session()->now('error', 'Pendaftaran tidak ditemukan. Periksa kembali NISN dan kode pendaftaran.');
            }
        }

        return view('siswa.status', [
            'langkah' => $langkah,
            'hasil' => $hasil,
            'berkas' => $berkas,
            'bolehPerbaiki' => $bolehPerbaiki,
        ]);
    }

    // ---- SEMUA ROLE ----
    public function sandi()
    {
        return view('akun.sandi');
    }

    // ---- SISWA ----
    public function siswaDashboard()
    {
        $siswa = $this->siswaSesi();

        $kelas = null;
        $status = '-';

        if ($siswa) {
            $semester = $this->semesterAktif();

            $kelas = DB::table('keanggotaan_kelas as kk')
                ->join('kelas as k', 'k.id_kelas', '=', 'kk.id_kelas')
                ->where('kk.id_siswa', $siswa->id_siswa)
                ->where('kk.status', 'aktif')
                ->when($semester, fn ($q) => $q->where('kk.id_semester', $semester->id_semester))
                ->orderByDesc('kk.id_keanggotaan')
                ->value('k.nama_kelas');

            $status = DB::table('status_siswa')
                ->where('id_status_siswa', $siswa->id_status_siswa)
                ->value('nama_status') ?? '-';
        }

        return view('siswa.dashboard', [
            'siswa' => [
                'nama_lengkap' => $siswa->nama_lengkap ?? session('nama', 'Siswa'),
                'kelas' => $kelas ?? '-',
                'status' => $status,
            ],
            'pengajuan' => $this->pengajuanMilikSiswa($siswa?->id_siswa),
            'periode' => $this->periodeUpdateBerjalan(),
            'fotoSekolah' => $this->gambarPublik('images/sekolah.jpeg'),
        ]);
    }

    public function profil(PpdbService $ppdb): View
    {
        $siswa = $this->siswaSesi();
        $idPendaftaran = $siswa ? $this->idPendaftaranSiswa($siswa) : null;

        $dokumen = $siswa
            ? $this->dokumenSiswaQuery()
                ->where('id_siswa', $siswa->id_siswa)
                ->orderByDesc('diunggah_pada')
                ->get(['id_dokumen', 'jenis_dokumen', 'nama_file_asli', 'path_file', 'status_verifikasi', 'diunggah_pada'])
            : collect();

        return view('siswa.profil', [
            's' => $this->siswaTampilan(),
            'ppdb' => $idPendaftaran ? $ppdb->dataPendaftaran((int) $idPendaftaran) : null,
            'agamaOptions' => $ppdb->agamaOptions(),
            'dokumen' => $dokumen,
            'terhubung' => (bool) $siswa,
        ]);
    }

    public function dokumenPreviewSiswa(
        Request $request,
        int $idDokumen,
        BerkasPreviewService $berkas
    ): Response {
        $siswa = $this->siswaSesi();
        abort_unless($siswa, 403);

        $dokumen = $this->dokumenSiswaQuery()
            ->where('id_dokumen', $idDokumen)
            ->first(['id_siswa', 'id_pendaftaran', 'path_file', 'nama_file_asli', 'jenis_dokumen']);

        abort_unless($dokumen, 404);

        $idPendaftaran = $this->idPendaftaranSiswa($siswa);
        $milikSiswa = $dokumen->id_siswa !== null
            && (int) $dokumen->id_siswa === (int) $siswa->id_siswa;
        $milikPendaftaran = $idPendaftaran !== null
            && $dokumen->id_pendaftaran !== null
            && (int) $dokumen->id_pendaftaran === $idPendaftaran;

        abort_unless($milikSiswa || $milikPendaftaran, 403);

        $nama = $berkas->namaAman(
            $dokumen->nama_file_asli ?: basename((string) $dokumen->path_file)
        );

        if (empty($dokumen->path_file)) {
            return $berkas->tidakTersedia($nama);
        }

        return $berkas->response($request, (string) $dokumen->path_file, $nama);
    }

    public function pengajuan()
    {
        $siswa = $this->siswaSesi();

        $fieldDapatDiubah = DB::table('ref_field_data')
            ->where('boleh_diubah_siswa', true)
            ->whereIn('nama_tabel', ['siswa', 'alamat_siswa', 'orang_tua_wali'])
            ->where(function ($query) {
                $query->where('nama_tabel', '!=', 'siswa')
                    ->orWhere('nama_kolom', '!=', 'jenis_kelamin');
            })
            ->orderBy('label_field')
            ->get(['id_field', 'nama_tabel', 'nama_kolom', 'label_field', 'perlu_bukti']);

        return view('siswa.pengajuan', [
            's' => $this->siswaTampilan(),
            'pengajuan' => $this->daftarPengajuanSiswa($siswa?->id_siswa),
            'periode' => $this->periodeBerjalan(),
            'fieldDapatDiubah' => $fieldDapatDiubah,
            'terhubung' => (bool) $siswa,
        ]);
    }

    public function konfirmasi()
    {
        return view('siswa.konfirmasi', [
            's' => $this->siswaTampilan(),
            'periode' => $this->periodeBerjalan(),
            'terhubung' => (bool) $this->siswaSesi(),
        ]);
    }

    // ---- GURU ----
    public function jadwalGuru()
    {
        $guru = $this->guruSesi();
        $semester = $this->semesterAktif();
        $jadwalHarian = [];
        $hariAktif = self::daftarHariJadwal();

        if ($guru && $semester) {
            $rows = DB::table('jadwal_pelajaran as j')
                ->join('mata_pelajaran as m', 'm.id_mata_pelajaran', '=', 'j.id_mata_pelajaran')
                ->join('kelas as k', 'k.id_kelas', '=', 'j.id_kelas')
                ->join('guru as g', 'g.id_guru', '=', 'j.id_guru')
                ->where('j.id_semester', $semester->id_semester)
                ->where('j.id_guru', $guru->id_guru)
                ->whereIn('j.hari', self::daftarNomorHariJadwal())
                ->orderBy('j.hari')
                ->orderBy('j.jam_mulai')
                ->get([
                    'j.hari',
                    'j.jam_mulai',
                    'j.jam_selesai',
                    'j.ruang',
                    'm.nama_mapel',
                    'g.nama_guru',
                    'k.nama_kelas',
                ]);

            $hari = self::daftarHariJadwal();

            foreach ($rows as $row) {
                $nomorHari = (int) $row->hari;
                if (! isset($hari[$nomorHari])) {
                    continue;
                }

                $hariAktif[$nomorHari] = mb_strtoupper($hari[$nomorHari]);
                $jadwalHarian[$nomorHari][] = [
                    'mulai' => substr((string) $row->jam_mulai, 0, 5),
                    'selesai' => substr((string) $row->jam_selesai, 0, 5),
                    'kelas' => $row->nama_kelas,
                    'mapel' => $row->nama_mapel,
                    'guru' => $row->nama_guru,
                    'ruang' => $row->ruang ?: '-',
                ];
            }
        }

        ksort($hariAktif);

        return view('guru.jadwal', compact('jadwalHarian', 'hariAktif'));
    }

    public function guruKelas()
    {
        $guru = $this->guruSesi();
        $semester = $this->semesterAktif();
        $kelasWali = ($guru && $semester) ? $this->kelasWali((int) $guru->id_guru, (int) $semester->id_semester) : [];
        $idKelas = (int) request()->query('kelas', $kelasWali[0]['id'] ?? 0);
        $kelas = collect($kelasWali)->firstWhere('id', $idKelas) ?? ($kelasWali[0] ?? null);
        $siswa = [];

        if ($kelas && $semester) {
            $siswa = DB::table('keanggotaan_kelas as kk')
                ->join('siswa as s', 's.id_siswa', '=', 'kk.id_siswa')
                ->where('kk.id_kelas', $kelas['id'])
                ->where('kk.id_semester', $semester->id_semester)
                ->where('kk.status', 'aktif')
                ->orderBy('s.nama_lengkap')
                ->paginate(10)
                ->through(fn ($s) => ['id' => $s->id_siswa, 'nama' => $s->nama_lengkap, 'nisn' => $s->nisn ?? '-'])
                ->withQueryString();
        }

        return view('guru.kelas', compact('kelasWali', 'kelas', 'siswa'));
    }

    /** Pengajuan perubahan data siswa di kelas yang diwalikan. */
    public function guruPengajuan(Request $request)
    {
        $guru = $this->guruSesi();
        $semester = DB::table('semester')
            ->where('is_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_semester')
            ->first();
        $kelasWali = ($guru && $semester) ? $this->kelasWali((int) $guru->id_guru, (int) $semester->id_semester) : [];
        $idKelas = (int) $request->query('kelas', $kelasWali[0]['id'] ?? 0);
        $kelas = collect($kelasWali)->firstWhere('id', $idKelas) ?? ($kelasWali[0] ?? null);
        $pengajuan = [];

        if ($kelas && $semester) {
            $idSiswaKelas = DB::table('keanggotaan_kelas')
                ->where('id_kelas', $kelas['id'])
                ->where('id_semester', $semester->id_semester)
                ->where('status', 'aktif')
                ->pluck('id_siswa');

            if ($idSiswaKelas->isNotEmpty()) {
                $pengajuan = DB::table('pengajuan_perubahan as pp')
                    ->join('siswa as s', 's.id_siswa', '=', 'pp.id_siswa')
                    ->join('detail_pengajuan as d', 'd.id_pengajuan', '=', 'pp.id_pengajuan')
                    ->whereIn('pp.id_siswa', $idSiswaKelas)
                    ->orderByDesc('pp.tgl_pengajuan')
                    ->get([
                        'pp.id_pengajuan', 'pp.tgl_pengajuan', 'pp.status_pengajuan', 'pp.catatan_pemeriksa',
                        's.nama_lengkap', 's.nisn', 'd.id_detail_pengajuan', 'd.nama_field',
                        'd.nilai_lama', 'd.nilai_baru', 'd.bukti_path',
                    ])
                    ->groupBy('id_pengajuan')
                    ->map(function ($details) {
                        $pengajuan = $details->first();

                        return [
                            'id' => $pengajuan->id_pengajuan,
                            'tgl' => $pengajuan->tgl_pengajuan
                                ? Carbon::parse($pengajuan->tgl_pengajuan)->locale('id')->translatedFormat('d M Y H:i')
                                : '-',
                            'siswa' => $pengajuan->nama_lengkap,
                            'nisn' => $pengajuan->nisn ?? '-',
                            'status' => $pengajuan->status_pengajuan,
                            'catatan' => $pengajuan->catatan_pemeriksa,
                            'detail' => $details->map(fn ($detail) => [
                                'id' => $detail->id_detail_pengajuan,
                                'field' => $detail->nama_field,
                                'lama' => $this->decodeNilai($detail->nilai_lama),
                                'baru' => $this->decodeNilai($detail->nilai_baru),
                                'bukti' => $detail->bukti_path,
                            ])->all(),
                        ];
                    })
                    ->all();
            }
        }

        return view('guru.pengajuan', compact('kelasWali', 'kelas', 'pengajuan', 'semester'));
    }

    public function guruPengajuanBukti(
        Request $request,
        int $id,
        int $idDetail,
        PengajuanPerubahanService $pengajuanService,
        BerkasPreviewService $berkas
    ): Response {
        $detail = DB::table('detail_pengajuan as d')
            ->join('pengajuan_perubahan as pp', 'pp.id_pengajuan', '=', 'd.id_pengajuan')
            ->where('d.id_pengajuan', $id)
            ->where('d.id_detail_pengajuan', $idDetail)
            ->first(['d.bukti_path', 'pp.id_siswa']);

        abort_unless(
            $detail
                && $detail->bukti_path
                && $pengajuanService->waliKelasMenanganiSiswa(
                    (int) $request->session()->get('id_pengguna'),
                    (int) $detail->id_siswa
                ),
            404
        );

        $nama = $berkas->namaAman(basename((string) $detail->bukti_path));

        return $berkas->response($request, (string) $detail->bukti_path, $nama);
    }

    private function decodeNilai(?string $nilai): mixed
    {
        if ($nilai === null) {
            return null;
        }

        $hasil = json_decode($nilai, true);

        return json_last_error() === JSON_ERROR_NONE ? $hasil : $nilai;
    }

    /** Kelengkapan data (konfirmasi/perubahan) siswa di kelas yang diwalikan. */
    public function guruKelengkapan(Request $request)
    {
        $guru = $this->guruSesi();
        $semester = $this->semesterAktif();
        $kelasWali = ($guru && $semester) ? $this->kelasWali((int) $guru->id_guru, (int) $semester->id_semester) : [];
        $idKelas = (int) $request->query('kelas', $kelasWali[0]['id'] ?? 0);
        $kelas = collect($kelasWali)->firstWhere('id', $idKelas) ?? ($kelasWali[0] ?? null);
        $periode = $this->periodeAktifSekarang();
        $siswa = [];

        if ($kelas && $semester) {
            $baris = DB::table('keanggotaan_kelas as kk')
                ->join('siswa as s', 's.id_siswa', '=', 'kk.id_siswa')
                ->leftJoin('konfirmasi_data as cd', function ($join) use ($periode) {
                    $join->on('cd.id_siswa', '=', 'kk.id_siswa');
                    if ($periode) {
                        $join->where('cd.id_periode_update', '=', $periode->id_periode_update);
                    }
                })
                ->where('kk.id_kelas', $kelas['id'])
                ->where('kk.id_semester', $semester->id_semester)
                ->where('kk.status', 'aktif')
                ->groupBy('s.id_siswa', 's.nama_lengkap', 's.nisn')
                ->select(
                    's.id_siswa',
                    's.nama_lengkap',
                    's.nisn',
                    DB::raw('MAX(cd.status) AS status_konfirmasi'),
                    DB::raw('MAX(cd.dikonfirmasi_pada) AS tgl_konfirmasi')
                )
                ->orderBy('s.nama_lengkap')
                ->get();

            $pengajuanTerbaru = DB::table('pengajuan_perubahan')
                ->whereIn('id_siswa', $baris->pluck('id_siswa'))
                ->when($periode, fn ($query) => $query->where('id_periode_update', $periode->id_periode_update))
                ->orderByDesc('tgl_pengajuan')
                ->orderByDesc('id_pengajuan')
                ->get(['id_siswa', 'status_pengajuan', 'tgl_pengajuan'])
                ->unique('id_siswa')
                ->keyBy('id_siswa');

            $siswa = $baris->map(function ($s) use ($pengajuanTerbaru) {
                $pengajuan = $pengajuanTerbaru->get($s->id_siswa);

                if ($pengajuan) {
                    $status = match ($pengajuan->status_pengajuan) {
                        'menunggu' => 'menunggu',
                        'disetujui' => 'disetujui',
                        'ditolak' => 'ditolak',
                        default => 'ubah',
                    };
                    $tgl = $pengajuan->tgl_pengajuan;
                } elseif ($s->status_konfirmasi === 'perlu_perubahan') {
                    $status = 'ubah';
                    $tgl = $s->tgl_konfirmasi;
                } elseif ($s->status_konfirmasi === 'dikonfirmasi') {
                    $status = 'konfirmasi';
                    $tgl = $s->tgl_konfirmasi;
                } else {
                    $status = 'belum';
                    $tgl = null;
                }

                return [
                    'nama' => $s->nama_lengkap,
                    'nisn' => $s->nisn ?? '-',
                    'status' => $status,
                    'tgl' => $tgl ? Carbon::parse($tgl)->locale('id')->translatedFormat('d M Y') : '-',
                ];
            })->all();
        }

        return view('guru.kelengkapan', compact('kelasWali', 'kelas', 'siswa', 'semester', 'periode'));
    }

    public function catatan()
    {
        $idGuru = Guru::where('id_pengguna', session('id_pengguna'))->value('id_guru');
        $semester = DB::table('semester')
            ->where('is_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_semester')
            ->first(['id_semester']);
        $kelasWali = $idGuru && $semester
            ? $this->kelasWali((int) $idGuru, (int) $semester->id_semester)
            : [];
        $idKelasWali = collect($kelasWali)->pluck('id')->all();
        $siswa = [];

        if ($semester && $idKelasWali !== []) {
            $siswa = DB::table('keanggotaan_kelas as kk')
                ->join('siswa as s', 's.id_siswa', '=', 'kk.id_siswa')
                ->whereIn('kk.id_kelas', $idKelasWali)
                ->where('kk.id_semester', $semester->id_semester)
                ->where('kk.status', 'aktif')
                ->orderBy('s.nama_lengkap')
                ->get(['s.id_siswa', 's.nama_lengkap', 's.nisn'])
                ->map(fn ($s) => [
                    'id' => (int) $s->id_siswa,
                    'nama' => $s->nama_lengkap,
                    'nisn' => $s->nisn ?? '-',
                ])
                ->all();
        }

        $catatan = DB::table('catatan_wali_kelas as c')
            ->join('siswa as s', 's.id_siswa', '=', 'c.id_siswa')
            ->when($idGuru, fn ($q) => $q->where('c.id_guru', $idGuru))
            ->orderByDesc('c.tgl_dibuat')
            ->paginate(10)
            ->through(fn ($c) => [
                'siswa' => $c->nama_lengkap,
                'isi' => $c->isi_catatan,
                'tgl' => Carbon::parse($c->tgl_dibuat)->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i').' WIB',
            ])
            ->withQueryString();

        return view('guru.catatan', [
            'siswa' => $siswa,
            'catatan' => $catatan,
        ]);
    }

    /** Presensi harian kelas yang diwalikan. */
    public function guruAbsensi(Request $request)
    {
        $guru = $this->guruSesi();
        $semester = $this->semesterAktif();
        $kelasWali = ($guru && $semester) ? $this->kelasWali((int) $guru->id_guru, (int) $semester->id_semester) : [];
        $kelas = $kelasWali[0] ?? null;
        $tanggal = $this->tanggalValid($request->query('tanggal'));
        $siswa = [];
        $presensi = [];

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

            $sesi = DB::table('sesi_absensi')
                ->where('id_kelas', $kelas['id'])
                ->where('id_semester', $semester->id_semester)
                ->where('tanggal', $tanggal)
                ->first(['id_sesi']);

            if ($sesi) {
                $detail = DB::table('detail_absensi as da')
                    ->join('status_kehadiran as sh', 'sh.id_status_hadir', '=', 'da.id_status_hadir')
                    ->where('da.id_sesi', $sesi->id_sesi)
                    ->get(['da.id_siswa', 'sh.kode', 'da.keterangan']);

                foreach ($detail as $d) {
                    $presensi[$d->id_siswa] = [
                        'kode' => $this->kodeKehadiranForm($d->kode),
                        'keterangan' => $d->keterangan ?? '',
                    ];
                }
            }
        }

        return view('guru.absensi', compact('kelasWali', 'kelas', 'semester', 'tanggal', 'siswa', 'presensi'));
    }

    /** Rapor seluruh siswa di kelas yang diwalikan (nilai diisi sekaligus per mata pelajaran). */
    public function guruRapor(Request $request)
    {
        $guru = $this->guruSesi();
        $semester = $this->semesterAktif();
        $kelasWali = ($guru && $semester) ? $this->kelasWali((int) $guru->id_guru, (int) $semester->id_semester) : [];
        $kelas = $kelasWali[0] ?? null;
        $siswa = [];
        $mapel = [];
        $idMapel = 0;
        $mapelTerpilih = null;
        $komponenRapor = $this->komponenRapor();
        $bobotKomponen = $this->bobotKomponenRapor($komponenRapor);
        $nilaiMentah = [];
        $nilaiAkhir = [];
        $nilaiTampil = [];
        $catatanSiswa = [];
        $statusSiswa = [];
        $idRaporPerSiswa = [];
        $idRaporSiapDikunci = [];
        $rekapKelas = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];

        if ($kelas && $semester) {
            $siswa = DB::table('keanggotaan_kelas as kk')
                ->join('siswa as s', 's.id_siswa', '=', 'kk.id_siswa')
                ->where('kk.id_kelas', $kelas['id'])
                ->where('kk.id_semester', $semester->id_semester)
                ->where('kk.status', 'aktif')
                ->orderBy('s.nama_lengkap', 'asc')
                ->get(['kk.id_keanggotaan', 's.id_siswa', 's.nama_lengkap', 's.nisn'])
                ->map(fn ($s) => [
                    'id' => $s->id_siswa,
                    'id_keanggotaan' => $s->id_keanggotaan,
                    'nama' => $s->nama_lengkap,
                    'nisn' => $s->nisn ?? '-',
                ])
                ->all();

            $mapel = DB::table('jadwal_pelajaran as j')
                ->join('mata_pelajaran as m', 'm.id_mata_pelajaran', '=', 'j.id_mata_pelajaran')
                ->where('j.id_kelas', $kelas['id'])
                ->where('j.id_semester', $semester->id_semester)
                ->whereIn('j.hari', self::daftarNomorHariJadwal())
                ->distinct()
                ->orderBy('m.nama_mapel')
                ->get(['m.id_mata_pelajaran', 'm.nama_mapel'])
                ->map(fn ($m) => ['id' => $m->id_mata_pelajaran, 'nama' => $m->nama_mapel])
                ->all();

            if (! $mapel) {
                $mapel = DB::table('mata_pelajaran')
                    ->where('is_aktif', true)
                    ->orderBy('nama_mapel')
                    ->get(['id_mata_pelajaran', 'nama_mapel'])
                    ->map(fn ($m) => ['id' => $m->id_mata_pelajaran, 'nama' => $m->nama_mapel])
                    ->all();
            }

            $idMapel = (int) $request->query('mapel', $mapel[0]['id'] ?? 0);
            $mapelTerpilih = collect($mapel)->firstWhere('id', $idMapel) ?? ($mapel[0] ?? null);

            if ($mapelTerpilih) {
                $idMapel = (int) $mapelTerpilih['id'];
            }

            foreach ($siswa as $s) {
                $nilaiMentah[$s['id']] = ['tugas' => null, 'keterampilan' => null, 'ujian' => null];
                $nilaiAkhir[$s['id']] = null;
                $nilaiTampil[$s['id']] = null;
                $catatanSiswa[$s['id']] = null;
                $statusSiswa[$s['id']] = 'draft';
            }

            $idAnggota = array_map(fn ($s) => (int) $s['id_keanggotaan'], $siswa);
            $anggotaKeSiswa = [];

            foreach ($siswa as $s) {
                $anggotaKeSiswa[(int) $s['id_keanggotaan']] = (int) $s['id'];
            }

            if ($idAnggota) {
                $rowsRapor = DB::table('rapor')
                    ->whereIn('id_anggota', $idAnggota)
                    ->where('id_semester', $semester->id_semester)
                    ->get(['id_rapor', 'id_anggota', 'catatan_wali', 'status_rapor']);

                foreach ($rowsRapor as $row) {
                    $idSiswaRapor = $anggotaKeSiswa[(int) $row->id_anggota] ?? null;

                    if (! $idSiswaRapor) {
                        continue;
                    }

                    $idRaporPerSiswa[$idSiswaRapor] = (int) $row->id_rapor;
                    $catatanSiswa[$idSiswaRapor] = $row->catatan_wali;
                    $statusSiswa[$idSiswaRapor] = $row->status_rapor ?: 'draft';
                }

                if ($mapelTerpilih && $idRaporPerSiswa) {
                    $detailRows = DB::table('detail_rapor')
                        ->whereIn('id_rapor', array_values($idRaporPerSiswa))
                        ->where('id_mapel', $mapelTerpilih['id'])
                        ->get(['id_rapor', 'nilai_akhir']);
                    $detailPerRapor = $detailRows->pluck('nilai_akhir', 'id_rapor')->all();

                    foreach ($idRaporPerSiswa as $idSiswaRapor => $idRapor) {
                        if (isset($detailPerRapor[$idRapor])) {
                            $nilaiAkhir[$idSiswaRapor] = (float) $detailPerRapor[$idRapor];
                        }
                    }
                }
            }

            $komponenAda = array_values(array_filter($komponenRapor, fn ($id) => $id !== null));

            if ($mapelTerpilih && $idAnggota && $komponenAda) {
                $komponenKeJenis = [];

                foreach ($komponenRapor as $jenis => $idKomponen) {
                    if ($idKomponen !== null) {
                        $komponenKeJenis[(int) $idKomponen] = $jenis;
                    }
                }

                $rawRows = DB::table('nilai_siswa')
                    ->whereIn('id_anggota', $idAnggota)
                    ->where('id_mapel', $mapelTerpilih['id'])
                    ->where('id_semester', $semester->id_semester)
                    ->whereIn('id_komponen', $komponenAda)
                    ->get(['id_anggota', 'id_komponen', 'nilai']);

                foreach ($rawRows as $raw) {
                    $idSiswaRaw = $anggotaKeSiswa[(int) $raw->id_anggota] ?? null;
                    $jenis = $komponenKeJenis[(int) $raw->id_komponen] ?? null;

                    if ($idSiswaRaw && $jenis) {
                        $nilaiMentah[$idSiswaRaw][$jenis] = (float) $raw->nilai;
                    }
                }
            }

            foreach ($siswa as $s) {
                $raw = $nilaiMentah[$s['id']];
                $adaMentah = false;

                foreach ($raw as $angka) {
                    if ($angka !== null && $angka !== '') {
                        $adaMentah = true;
                        break;
                    }
                }

                $nilaiTampil[$s['id']] = $adaMentah
                    ? $this->nilaiAkhirKomponen($raw, $bobotKomponen)
                    : $nilaiAkhir[$s['id']];
            }

            if ($siswa) {
                $rekapRowsKelas = DB::table('detail_absensi as da')
                    ->join('sesi_absensi as sa', 'sa.id_sesi', '=', 'da.id_sesi')
                    ->join('status_kehadiran as sh', 'sh.id_status_hadir', '=', 'da.id_status_hadir')
                    ->whereIn('da.id_siswa', array_map(fn ($s) => (int) $s['id'], $siswa))
                    ->where('sa.id_kelas', $kelas['id'])
                    ->where('sa.id_semester', $semester->id_semester)
                    ->select('sh.kode', DB::raw('COUNT(*) AS jumlah'))
                    ->groupBy('sh.kode')
                    ->pluck('jumlah', 'kode')
                    ->all();

                foreach ($rekapRowsKelas as $kode => $jumlah) {
                    $kode = $this->kodeKehadiran($kode);

                    if (isset($rekapKelas[$kode])) {
                        $rekapKelas[$kode] += (int) $jumlah;
                    }
                }
            }

            $idRaporDraft = [];

            foreach ($idRaporPerSiswa as $idSiswaRapor => $idRapor) {
                if (($statusSiswa[$idSiswaRapor] ?? 'draft') !== 'final') {
                    $idRaporDraft[] = (int) $idRapor;
                }
            }

            if ($idRaporDraft) {
                $idRaporSiapDikunci = DB::table('detail_rapor')
                    ->whereIn('id_rapor', $idRaporDraft)
                    ->where('id_mapel', $idMapel)
                    ->distinct()
                    ->orderBy('id_rapor')
                    ->pluck('id_rapor')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
        }

        return view('guru.rapor', compact(
            'kelasWali',
            'kelas',
            'semester',
            'siswa',
            'mapel',
            'idMapel',
            'mapelTerpilih',
            'komponenRapor',
            'bobotKomponen',
            'nilaiMentah',
            'nilaiAkhir',
            'nilaiTampil',
            'catatanSiswa',
            'statusSiswa',
            'idRaporPerSiswa',
            'idRaporSiapDikunci',
            'rekapKelas'
        ));
    }

    // ---- SISWA: akademik ----

    public static function daftarSlotJadwal(): array
    {
        return [
            1 => ['label' => '( 1 )', 'waktu' => '07:00 - 07:40', 'mulai' => '07:00', 'selesai' => '07:40'],
            2 => ['label' => '( 2 )', 'waktu' => '07:40 - 08:20', 'mulai' => '07:40', 'selesai' => '08:20'],
            3 => ['label' => '( 3 )', 'waktu' => '08:20 - 09:00', 'mulai' => '08:20', 'selesai' => '09:00'],
            4 => ['label' => '( 4 )', 'waktu' => '09:20 - 10:00', 'mulai' => '09:20', 'selesai' => '10:00'],
            5 => ['label' => '( 5 )', 'waktu' => '10:00 - 10:40', 'mulai' => '10:00', 'selesai' => '10:40'],
            6 => ['label' => '( 6 )', 'waktu' => '10:40 - 11:20', 'mulai' => '10:40', 'selesai' => '11:20'],
            7 => ['label' => '( 7 )', 'waktu' => '11:30 - 12:10', 'mulai' => '11:30', 'selesai' => '12:10'],
            8 => ['label' => '( 8 )', 'waktu' => '12:10 - 12:50', 'mulai' => '12:10', 'selesai' => '12:50'],
        ];
    }

    public static function daftarHariJadwal(): array
    {
        return [
            1 => 'SENIN',
            2 => 'SELASA',
            3 => 'RABU',
            4 => 'KAMIS',
            5 => 'JUMAT',
        ];
    }

    /** Nomor hari sekolah aktif (Senin–Jumat) untuk filter query dan validasi. */
    public static function daftarNomorHariJadwal(): array
    {
        return array_keys(self::daftarHariJadwal());
    }

    public function jadwalSiswa()
    {
        $siswa = $this->siswaSesi();
        $semester = $this->semesterAktif();
        $kelas = null;
        $jadwal = [];
        $jadwalHarian = [];
        $hariAktif = self::daftarHariJadwal();
        $daftarHari = self::daftarHariJadwal();
        $daftarSlot = self::daftarSlotJadwal();

        $matriks = [];
        foreach (array_keys($daftarHari) as $h) {
            foreach (array_keys($daftarSlot) as $s) {
                $matriks[$h][$s] = null;
            }
        }

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
                $hari = self::daftarHariJadwal();

                $rows = DB::table('jadwal_pelajaran as j')
                    ->join('mata_pelajaran as m', 'm.id_mata_pelajaran', '=', 'j.id_mata_pelajaran')
                    ->join('guru as g', 'g.id_guru', '=', 'j.id_guru')
                    ->where('j.id_kelas', $anggota->id_kelas)
                    ->where('j.id_semester', $semester->id_semester)
                    ->whereIn('j.hari', self::daftarNomorHariJadwal())
                    ->orderBy('j.hari')
                    ->orderBy('j.jam_mulai')
                    ->get(['j.hari', 'j.jam_mulai', 'j.jam_selesai', 'j.ruang', 'm.nama_mapel', 'g.nama_guru']);

                foreach ($rows as $r) {
                    $nomorHari = (int) $r->hari;
                    if (! isset($hari[$nomorHari])) {
                        continue;
                    }

                    $label = $hari[$nomorHari];
                    $mulai = substr((string) $r->jam_mulai, 0, 5);
                    $selesai = substr((string) $r->jam_selesai, 0, 5);

                    $item = [
                        'mapel' => $r->nama_mapel,
                        'guru' => $r->nama_guru,
                        'mulai' => $mulai,
                        'selesai' => $selesai,
                        'ruang' => $r->ruang ?: '-',
                    ];
                    $jadwal[$label][] = $item;
                    $jadwalHarian[$nomorHari][] = $item;

                    if (! isset($hariAktif[$nomorHari])) {
                        $hariAktif[$nomorHari] = mb_strtoupper($label);
                    }

                    $h = $nomorHari;
                    if (isset($daftarHari[$h])) {
                        $slotDitemukan = null;
                        foreach ($daftarSlot as $s => $info) {
                            if ($info['mulai'] === $mulai) {
                                $slotDitemukan = $s;
                                break;
                            }
                        }

                        if ($slotDitemukan === null) {
                            foreach ($daftarSlot as $s => $info) {
                                if ($matriks[$h][$s] === null) {
                                    $slotDitemukan = $s;
                                    break;
                                }
                            }
                        }

                        if ($slotDitemukan !== null) {
                            $matriks[$h][$slotDitemukan] = $item;
                        }
                    }
                }
            }
        }

        ksort($hariAktif);

        return view('siswa.jadwal', compact(
            'jadwal',
            'jadwalHarian',
            'hariAktif',
            'kelas',
            'semester',
            'daftarHari',
            'daftarSlot',
            'matriks'
        ));
    }

    public function absensiSiswa()
    {
        $siswa = $this->siswaSesi();
        $rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];
        $riwayat = [];
        $persentaseKehadiran = null;

        if ($siswa) {
            if (DB::getDriverName() === 'pgsql') {
                $ringkasan = DB::table('vw_rekap_absensi_siswa')
                    ->where('id_siswa', $siswa->id_siswa)
                    ->selectRaw('COALESCE(SUM(hadir), 0) AS hadir, COALESCE(SUM(sakit), 0) AS sakit, COALESCE(SUM(izin), 0) AS izin, COALESCE(SUM(alpa), 0) AS alpha')
                    ->first();

                $rekap = [
                    'hadir' => (int) $ringkasan->hadir,
                    'izin' => (int) $ringkasan->izin,
                    'sakit' => (int) $ringkasan->sakit,
                    'alpha' => (int) $ringkasan->alpha,
                ];

                $persentaseKehadiran = (float) DB::selectOne(
                    'SELECT public.fn_persen_kehadiran(?) AS persentase',
                    [$siswa->id_siswa]
                )->persentase;
            }

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
                $kode = $this->kodeKehadiran($r->kode);

                if (DB::getDriverName() !== 'pgsql' && isset($rekap[$kode])) {
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

        return view('siswa.absensi', compact('rekap', 'riwayat', 'persentaseKehadiran'));
    }

    public function raporSiswa(?int $id = null)
    {
        $siswa = $this->siswaSesi();
        abort_unless($siswa, 404);

        $raporList = DB::table('rapor as r')
            ->join('keanggotaan_kelas as kk', 'kk.id_keanggotaan', '=', 'r.id_anggota')
            ->join('semester as sm', 'sm.id_semester', '=', 'r.id_semester')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'sm.id_tahun_ajaran')
            ->leftJoin('kelas as k', 'k.id_kelas', '=', 'kk.id_kelas')
            ->where('kk.id_siswa', $siswa->id_siswa)
            ->where('r.status_rapor', 'final')
            ->orderByDesc('sm.tanggal_mulai')
            ->orderByDesc('r.tgl_dibuat')
            ->get([
                'r.id_rapor',
                'r.id_anggota',
                'r.id_semester',
                'r.rata_rata',
                'r.jumlah_sakit',
                'r.jumlah_izin',
                'r.jumlah_alpa',
                'r.catatan_wali',
                'sm.nama_semester',
                'ta.nama_tahun_ajaran',
                'k.nama_kelas',
            ]);

        $rapor = $id
            ? $raporList->firstWhere('id_rapor', $id)
            : $raporList->first();
        abort_if($id && ! $rapor, 404);

        $rataRata = null;
        $predikatRataRata = null;
        $detail = [];

        if ($rapor) {
            $rataRata = DB::table('vw_nilai_rata_siswa')
                ->where('id_anggota', $rapor->id_anggota)
                ->where('id_semester', $rapor->id_semester)
                ->avg('nilai_rata_rata');

            if ($rataRata === null && $rapor->rata_rata !== null) {
                $rataRata = (float) $rapor->rata_rata;
            }

            if ($rataRata !== null) {
                $rataRata = round((float) $rataRata, 2);
                $predikatRataRata = DB::selectOne(
                    'SELECT public.fn_predikat_nilai(?) AS predikat',
                    [$rataRata]
                )->predikat;
            }

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

        return view('siswa.rapor', compact('raporList', 'rapor', 'rataRata', 'predikatRataRata', 'detail'));
    }

    // ---- TU ----
    public function tuDashboard(Request $request)
    {
        $statistikPpdb = DB::getDriverName() === 'pgsql'
            ? DB::table('vw_statistik_ppdb')->pluck('jumlah', 'status_pendaftaran')
            : collect();

        $statistik = [
            'menunggu' => DB::getDriverName() === 'pgsql'
                ? (int) $statistikPpdb->get('menunggu', 0)
                : DB::table('pendaftaran_ppdb')->where('status_pendaftaran', 'menunggu')->count(),
            'perlu_perbaikan' => DB::getDriverName() === 'pgsql'
                ? (int) $statistikPpdb->get('perlu_perbaikan', 0)
                : DB::table('pendaftaran_ppdb')->where('status_pendaftaran', 'perlu_perbaikan')->count(),
            'disahkan' => DB::getDriverName() === 'pgsql'
                ? (int) $statistikPpdb->get('diterima', 0)
                : DB::table('pendaftaran_ppdb')->where('status_pendaftaran', 'diterima')->count('id_pendaftaran'),
            'pengajuan' => DB::table('pengajuan_perubahan')
                ->where('status_pengajuan', 'menunggu')
                ->count('id_pengajuan'),
        ];

        $periode = $this->periodeAktifSekarang();
        $filterKelas = $this->rekapKelengkapanKelas($request, $periode, 'kelas_tu_page', 'kelas-tu');

        return view('tu.dashboard', [
            'antrean' => $this->daftarAntrean(5),
            'kelas' => $filterKelas['kelas'],
            'filterKelas' => $filterKelas,
            'statistik' => $statistik,
            'periode' => $periode,
        ]);
    }

    public function verifikasi(int $id, PpdbService $ppdb, BerkasPreviewService $berkas)
    {
        $pendaftaran = DB::table('pendaftaran_ppdb')->where('id_pendaftaran', $id)->first();

        if (! $pendaftaran) {
            abort(404);
        }

        $row = [
            'id' => $pendaftaran->id_pendaftaran,
            'nama' => $pendaftaran->nama_calon_siswa,
            'nisn' => $pendaftaran->nisn ?? '-',
            'jenis' => 'PPDB baru',
            'tgl' => Carbon::parse($pendaftaran->diajukan_pada)->locale('id')->translatedFormat('d M Y'),
            'status' => $this->statusTampilan($pendaftaran->status_pendaftaran),
        ];

        $daftarBerkas = [];

        if ($pendaftaran) {
            $daftarBerkas = DB::table('dokumen_siswa')
                ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
                ->orderBy('diunggah_pada')
                ->get(['id_dokumen', 'jenis_dokumen', 'nama_file_asli', 'path_file', 'status_verifikasi', 'diunggah_pada'])
                ->map(function ($d) use ($berkas) {
                    $url = route('tu.dokumen.preview', ['idDokumen' => $d->id_dokumen]);

                    return [
                        'id' => $d->id_dokumen,
                        'jenis' => $d->jenis_dokumen,
                        'nama' => $d->nama_file_asli ?: basename((string) $d->path_file),
                        'url' => $url,
                        'unduhUrl' => $url.'?download=1',
                        'tipe' => $berkas->tipe((string) $d->path_file),
                        'status' => $d->status_verifikasi,
                        'tgl' => Carbon::parse($d->diunggah_pada)->locale('id')->translatedFormat('d M Y'),
                    ];
                })
                ->all();
        }

        $alasan = DB::table('ref_alasan_pengembalian')
            ->orderBy('nama_alasan')
            ->pluck('nama_alasan')
            ->all();

        return view('tu.verifikasi', [
            'row' => $row,
            'berkas' => $daftarBerkas,
            'alasan' => $alasan,
            'detail' => $ppdb->dataPendaftaran((int) $pendaftaran->id_pendaftaran),
            'agamaOptions' => $ppdb->agamaOptions(),
        ]);
    }

    public function antrean(Request $request)
    {
        $cari = $request->query('cari', '');
        $cari = is_string($cari) ? trim($cari) : '';

        $status = $request->query('status', 'semua');
        $status = is_string($status) && in_array($status, ['semua', 'menunggu', 'disetujui', 'dikembalikan', 'disahkan'], true)
            ? $status
            : 'semua';

        $antrean = $this->queryAntrean($cari, $status)
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($r) => $this->barisAntrean($r));

        return view('tu.antrean', [
            'antrean' => $antrean,
            'cari' => $cari,
            'status' => $status,
            'hitung' => $this->hitungAntrean($cari),
        ]);
    }

    public function kelulusan()
    {
        return view('tu.kelulusan', ['pendaftar' => []]);
    }

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
            ->where(function ($q) {
                $q->whereNull('status_verifikasi')
                    ->orWhere('status_verifikasi', '<>', 'ditolak');
            })
            ->orderByDesc('diunggah_pada')
            ->get()
            ->map(function ($d) {
                $path = $d->path_file;
                $ext = strtoupper(pathinfo($path, PATHINFO_EXTENSION) ?: '-');
                $ukuran = '-';

                try {
                    $disk = null;
                    foreach (['private', 'public'] as $candidate) {
                        if ($path && Storage::disk($candidate)->exists($path)) {
                            $disk = $candidate;
                            break;
                        }
                    }

                    if ($disk) {
                        $ukuran = number_format(Storage::disk($disk)->size($path) / 1024, 0).' KB';
                    }
                } catch (\Throwable $e) {
                    $ukuran = '-';
                }

                $catatan = (string) ($d->catatan ?? '');
                $fisik = str_contains($catatan, 'fisik sudah diterima');
                $nomor = trim(str_replace('· fisik sudah diterima', '', $catatan));
                $nomor = trim($nomor, " \t\n\r\0\x0B·");

                return [
                    'id_dokumen' => $d->id_dokumen,
                    'jenis' => $d->jenis_dokumen,
                    'nama' => $d->nama_file_asli ?: basename((string) $path),
                    'nomor' => $nomor !== '' ? $nomor : '-',
                    'format' => $ext,
                    'tipe' => strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)) === 'pdf' ? 'pdf' : 'gambar',
                    'ukuran' => $ukuran,
                    'fisik' => $fisik,
                    'tgl' => Carbon::parse($d->diunggah_pada)->locale('id')->translatedFormat('d M Y'),
                ];
            })
            ->all();

        $jenis = DB::table('jenis_dokumen')
            ->whereNotIn('nama_dokumen', PpdbService::CHECKLIST_TIDAK_DIPERIKSA)
            ->orderBy('nama_dokumen')
            ->pluck('nama_dokumen')
            ->all();

        return view('tu.siswa-dokumen', [
            'id' => $id,
            'nama' => $nama,
            'dokumen' => $dokumen,
            'jenis' => $jenis,
        ]);
    }

    public function dokumenPreview(
        Request $request,
        int $idDokumen,
        BerkasPreviewService $berkas
    ): Response {
        $dokumen = DB::table('dokumen_siswa')->where('id_dokumen', $idDokumen)->first();

        abort_unless($dokumen, 404);

        $nama = $berkas->namaAman(
            $dokumen->nama_file_asli ?: basename((string) $dokumen->path_file)
        );

        if (empty($dokumen->path_file)) {
            return $berkas->tidakTersedia($nama);
        }

        return $berkas->response($request, (string) $dokumen->path_file, $nama);
    }

    public function resetSandi()
    {
        return view('tu.reset-sandi', ['pengguna' => []]);
    }

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

        abort_if(! $siswa, 404);

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
            ->select('p.id_periode_update', 'p.tanggal_mulai', 'p.tanggal_selesai', 'p.status', 't.nama_tahun_ajaran', 'p.nama_periode')
            ->when(
                DB::getDriverName() === 'pgsql',
                fn ($query) => $query->addSelect(DB::raw('public.fn_persen_konfirmasi_periode(p.id_periode_update) AS persentase_konfirmasi'))
            )
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id_periode_update,
                'tahun' => $p->nama_tahun_ajaran ?? $p->nama_periode,
                'buka' => Carbon::parse($p->tanggal_mulai)->locale('id')->translatedFormat('d M Y'),
                'tutup' => Carbon::parse($p->tanggal_selesai)->locale('id')->translatedFormat('d M Y'),
                'status' => $p->status === 'aktif' ? 'Dibuka' : 'Ditutup',
                'persentase_konfirmasi' => isset($p->persentase_konfirmasi) ? (float) $p->persentase_konfirmasi : null,
            ])
            ->all();

        return view('tu.periode', [
            'periode' => $periode,
            'tahunAjaran' => $this->daftarTahunAjaran(),
        ]);
    }

    public function kelas()
    {
        return view('tu.kelas', ['kelas' => []]);
    }

    public function kelasDetail(int $id)
    {
        abort(404);
    }

    public function kenaikan(Request $request, KenaikanKelasService $kenaikan)
    {
        $filter = $request->validate([
            'tingkat' => ['nullable', 'in:X,XI,XII'],
            'program' => ['nullable', 'in:MIA,IIS'],
        ]);
        $tahunAjaran = $kenaikan->tahunAjaran();
        $idTahunAsal = (int) $request->query('tahun_ajaran', 0);

        if (! $tahunAjaran->firstWhere('id_tahun_ajaran', $idTahunAsal)) {
            $idTahunAsal = (int) ($kenaikan->tahunAjaranAktifId() ?? 0);
        }

        $rencana = $idTahunAsal > 0 ? $kenaikan->pratinjau($idTahunAsal) : null;
        if ($rencana) {
            $rencana['baris'] = array_values(array_filter($rencana['baris'], fn (array $baris) => (! ($filter['tingkat'] ?? null) || $baris['kelas_asal']['tingkat'] === $filter['tingkat'])
                && (! ($filter['program'] ?? null) || $baris['kelas_asal']['kode_jurusan'] === $filter['program'])
            ));
            $rencana['jumlah_siswa'] = array_sum(array_map(fn (array $baris) => count($baris['siswa']), $rencana['baris']));
        }

        return view('tu.kenaikan', [
            'tahunAjaran' => $tahunAjaran,
            'idTahunAsal' => $idTahunAsal,
            'rencana' => $rencana,
            'tingkatTerpilih' => $filter['tingkat'] ?? '',
            'programTerpilih' => $filter['program'] ?? '',
        ]);
    }

    // ---- DATA MASTER (TU) ----
    public function referensi(string $tab = 'agama')
    {
        abort(404);
    }

    public function checklistRef()
    {
        return view('admin.checklist', ['checklist' => [], 'alasan' => []]);
    }

    public function aturan()
    {
        return view('admin.aturan-field', ['field' => []]);
    }

    // ---- AKADEMIK (TU) ----
    public function konfigurasi(string $tab = 'tahun-ajaran')
    {
        abort(404);
    }

    public function jadwal(Request $request)
    {
        $semesterAktif = $this->semesterAktif();

        $semesterList = DB::table('semester as s')
            ->join('tahun_ajaran as t', 't.id_tahun_ajaran', '=', 's.id_tahun_ajaran')
            ->orderByDesc('t.tanggal_mulai')
            ->orderByDesc('s.tanggal_mulai')
            ->get(['s.id_semester', 's.nama_semester', 's.id_tahun_ajaran', 't.nama_tahun_ajaran'])
            ->map(fn ($s) => [
                'id' => (int) $s->id_semester,
                'nama' => $s->nama_semester,
                'tahun' => $s->nama_tahun_ajaran,
                'id_tahun_ajaran' => $s->id_tahun_ajaran !== null ? (int) $s->id_tahun_ajaran : null,
            ])
            ->all();

        $idSemester = (int) $request->query('semester', $semesterAktif->id_semester ?? ($semesterList[0]['id'] ?? 0));
        $semesterTerpilih = collect($semesterList)->firstWhere('id', $idSemester) ?? ($semesterList[0] ?? null);

        if ($semesterTerpilih) {
            $idSemester = (int) $semesterTerpilih['id'];
        }

        $idTahunAjaran = $semesterTerpilih['id_tahun_ajaran'] ?? null;

        $kelas = DB::table('kelas as k')
            ->leftJoin('tingkat as t', 't.id_tingkat', '=', 'k.id_tingkat')
            ->leftJoin('penugasan_wali_kelas as pw', function ($join) use ($idSemester) {
                $join->on('pw.id_kelas', '=', 'k.id_kelas')
                    ->where('pw.id_semester', '=', $idSemester);
            })
            ->leftJoin('guru as g', 'g.id_guru', '=', 'pw.id_guru')
            ->where('k.is_aktif', true)
            ->when($idTahunAjaran, function ($query) use ($idTahunAjaran) {
                $query->where(function ($q) use ($idTahunAjaran) {
                    $q->where('k.id_tahun_ajaran', $idTahunAjaran)
                        ->orWhereNull('k.id_tahun_ajaran');
                });
            })
            ->orderByRaw('COALESCE(t.urutan, 99)')
            ->orderBy('k.nama_kelas')
            ->get(['k.id_kelas', 'k.nama_kelas', 't.nama_tingkat', 'g.nama_guru', 't.urutan as urutan_tingkat'])
            ->map(fn ($k) => [
                'id' => (int) $k->id_kelas,
                'nama' => $k->nama_kelas,
                'tingkat' => $k->nama_tingkat ?? '-',
                'wali' => $k->nama_guru ?? '(belum ditetapkan)',
                'urutan_tingkat' => $k->urutan_tingkat !== null ? (int) $k->urutan_tingkat : 99,
                'jumlah' => 0,
            ])
            ->all();

        // Urutkan kelas sesuai urutan natural (1-20, dsb.)
        usort($kelas, function ($a, $b) {
            $urutanA = $a['urutan_tingkat'] ?? 99;
            $urutanB = $b['urutan_tingkat'] ?? 99;

            if ($urutanA !== $urutanB) {
                return $urutanA <=> $urutanB;
            }

            $cmp = strnatcasecmp($a['nama'] ?? '', $b['nama'] ?? '');

            return $cmp !== 0 ? $cmp : (($a['id'] ?? 0) <=> ($b['id'] ?? 0));
        });

        $idKelas = (int) $request->query('kelas', $kelas[0]['id'] ?? 0);
        $kelasTerpilih = collect($kelas)->firstWhere('id', $idKelas) ?? ($kelas[0] ?? null);

        if ($kelasTerpilih) {
            $idKelas = (int) $kelasTerpilih['id'];
        }

        $guru = DB::table('guru')
            ->where('is_aktif', true)
            ->orderBy('nama_guru')
            ->get(['id_guru', 'nama_guru'])
            ->map(fn ($g) => ['id' => (int) $g->id_guru, 'nama' => $g->nama_guru])
            ->all();

        $mapel = DB::table('mata_pelajaran')
            ->where('is_aktif', true)
            ->orderBy('nama_mapel')
            ->get(['id_mata_pelajaran', 'nama_mapel'])
            ->map(fn ($m) => ['id' => (int) $m->id_mata_pelajaran, 'nama' => $m->nama_mapel])
            ->all();

        $jadwalTersimpan = [];

        if ($idKelas && $idSemester) {
            $jadwalTersimpan = DB::table('jadwal_pelajaran as j')
                ->leftJoin('mata_pelajaran as m', 'm.id_mata_pelajaran', '=', 'j.id_mata_pelajaran')
                ->leftJoin('guru as g', 'g.id_guru', '=', 'j.id_guru')
                ->where('j.id_kelas', $idKelas)
                ->where('j.id_semester', $idSemester)
                ->whereIn('j.hari', self::daftarNomorHariJadwal())
                ->orderBy('j.hari')
                ->orderBy('j.jam_mulai')
                ->get([
                    'j.id_jadwal',
                    'j.hari',
                    'j.jam_mulai',
                    'j.jam_selesai',
                    'j.id_mata_pelajaran',
                    'j.id_guru',
                    'j.ruang',
                    'm.nama_mapel',
                    'g.nama_guru',
                ])
                ->map(fn ($j) => [
                    'id_jadwal' => (int) $j->id_jadwal,
                    'hari' => (int) $j->hari,
                    'jam_mulai' => substr((string) $j->jam_mulai, 0, 5),
                    'jam_selesai' => substr((string) $j->jam_selesai, 0, 5),
                    'id_mata_pelajaran' => (int) $j->id_mata_pelajaran,
                    'id_guru' => (int) $j->id_guru,
                    'ruang' => $j->ruang ?? '',
                    'nama_mapel' => $j->nama_mapel,
                    'nama_guru' => $j->nama_guru,
                ])
                ->all();
        }

        $daftarHari = self::daftarHariJadwal();
        $daftarSlot = self::daftarSlotJadwal();

        $matriks = [];
        foreach (array_keys($daftarHari) as $h) {
            foreach (array_keys($daftarSlot) as $s) {
                $matriks[$h][$s] = null;
            }
        }

        foreach ($jadwalTersimpan as $j) {
            $h = (int) $j['hari'];
            if (! isset($daftarHari[$h])) {
                continue;
            }

            $slotDitemukan = null;
            foreach ($daftarSlot as $s => $info) {
                if ($info['mulai'] === $j['jam_mulai']) {
                    $slotDitemukan = $s;
                    break;
                }
            }

            if ($slotDitemukan === null) {
                foreach ($daftarSlot as $s => $info) {
                    if ($matriks[$h][$s] === null) {
                        $slotDitemukan = $s;
                        break;
                    }
                }
            }

            if ($slotDitemukan !== null) {
                $matriks[$h][$slotDitemukan] = $j;
            }
        }

        return view('akademik.jadwal', [
            'kelas' => $kelas,
            'mapel' => $mapel,
            'guru' => $guru,
            'semester' => $semesterList,
            'semesterTerpilih' => $semesterTerpilih,
            'kelasTerpilih' => $kelasTerpilih,
            'jadwalTersimpan' => $jadwalTersimpan,
            'daftarHari' => $daftarHari,
            'daftarSlot' => $daftarSlot,
            'matriks' => $matriks,
        ]);
    }

    // ---- MUTASI SISWA ----
    public function mutasiTu()
    {
        return $this->halamanMutasi('tu');
    }

    public function mutasiKepsek()
    {
        return $this->halamanMutasi('kepsek');
    }

    private function halamanMutasi(string $role)
    {
        $rows = DB::table('mutasi_siswa as m')
            ->leftJoin('siswa as s', 's.id_siswa', '=', 'm.id_siswa')
            ->leftJoin('sekolah as st', 'st.id_sekolah', '=', 'm.id_sekolah_tujuan')
            ->leftJoin('sekolah as sa', 'sa.id_sekolah', '=', 'm.id_sekolah_asal')
            ->leftJoin('pengguna as pj', 'pj.id_pengguna', '=', 'm.id_pengaju')
            ->leftJoin('pengguna as pt', 'pt.id_pengguna', '=', 'm.id_penyetuju')
            ->orderByDesc('m.tgl_pengajuan')
            ->get([
                'm.id_mutasi', 'm.id_siswa', 'm.id_pengaju', 'm.id_penyetuju', 'm.jenis', 'm.alasan',
                'm.status', 'm.tgl_pengajuan', 'm.tgl_keputusan', 'm.tanggal_efektif',
                'm.catatan_keputusan', 'm.path_lampiran', 'm.nama_lampiran',
                'm.nisn_baru', 'm.nama_siswa_baru', 'm.nama_sekolah_tujuan as sekolah_tujuan_input',
                'st.nama_sekolah as sekolah_tujuan',
                'sa.nama_sekolah as sekolah_asal', 's.nama_lengkap', 's.nisn',
                'pj.username as pengaju', 'pt.username as penyetuju',
            ])
            ->map(fn ($r) => [
                'id' => $r->id_mutasi,
                'id_siswa' => $r->id_siswa,
                'sekolah_tujuan' => ($r->sekolah_tujuan_input ?: $r->sekolah_tujuan) ?? '',
                'jenis_kode' => $r->jenis,
                'nama' => $r->nama_lengkap ?? $r->nama_siswa_baru ?? '-',
                'nisn' => $r->nisn ?? $r->nisn_baru ?? '-',
                'jenis' => match ($r->jenis) {
                    'pindah_masuk' => 'Pindah Masuk',
                    'pindah_keluar' => 'Pindah Keluar',
                    'mengundurkan_diri' => 'Mengundurkan Diri',
                    'dikeluarkan' => 'Dikeluarkan',
                    'putus_sekolah' => 'Putus Sekolah',
                    'meninggal_dunia' => 'Meninggal Dunia',
                    default => $r->jenis,
                },
                'alasan' => $r->alasan ?: '-',
                'status' => $r->status,
                'tanggal_efektif_raw' => $r->tanggal_efektif,
                'tanggal_efektif' => $r->tanggal_efektif
                    ? Carbon::parse($r->tanggal_efektif)->locale('id')->translatedFormat('d M Y')
                    : '-',
                'catatan_keputusan' => $r->catatan_keputusan,
                'tgl' => $r->tgl_pengajuan
                    ? Carbon::parse($r->tgl_pengajuan)->locale('id')->translatedFormat('d M Y')
                    : '-',
                'keputusan' => $r->tgl_keputusan
                    ? Carbon::parse($r->tgl_keputusan)->locale('id')->translatedFormat('d M Y')
                    : '-',
                'pengaju' => $r->pengaju ?? '-',
                'penyetuju' => $r->penyetuju ?? '-',
                'sekolah_label' => match ($r->jenis) {
                    'pindah_masuk' => 'Sekolah Asal',
                    'pindah_keluar' => 'Sekolah Tujuan',
                    default => null,
                },
                'sekolah' => match ($r->jenis) {
                    'pindah_masuk' => $r->sekolah_asal ?? '-',
                    'pindah_keluar' => ($r->sekolah_tujuan_input ?: $r->sekolah_tujuan) ?? '-',
                    default => '-',
                },
                'ada_lampiran' => (bool) $r->path_lampiran,
                'nama_lampiran' => $r->nama_lampiran,
                'bisa_revisi' => $role === 'tu' && $r->status === 'menunggu' && $r->jenis !== 'pindah_masuk',
                'bisa_putuskan' => $role === 'kepsek' && $r->status === 'menunggu'
                    && in_array($r->jenis, ['pindah_keluar', 'mengundurkan_diri', 'dikeluarkan', 'putus_sekolah', 'meninggal_dunia'], true),
            ])
            ->all();

        $sekolah = DB::table('sekolah')
            ->orderBy('nama_sekolah')
            ->get(['id_sekolah', 'nama_sekolah'])
            ->map(fn ($s) => ['id' => $s->id_sekolah, 'nama' => $s->nama_sekolah])
            ->all();

        $siswaAktif = DB::table('siswa as s')
            ->join('status_siswa as st', 'st.id_status_siswa', '=', 's.id_status_siswa')
            ->where('st.kode_status', 'aktif')
            ->orderBy('s.nama_lengkap')
            ->get(['s.id_siswa', 's.nama_lengkap', 's.nisn'])
            ->map(fn ($s) => ['id' => $s->id_siswa, 'nama' => $s->nama_lengkap, 'nisn' => $s->nisn ?? '-'])
            ->all();

        $semesterAktif = DB::table('semester')
            ->where('is_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_semester')
            ->first(['id_tahun_ajaran']);
        $kelasTujuan = DB::table('kelas')
            ->where('is_aktif', true)
            ->when(
                $semesterAktif?->id_tahun_ajaran,
                fn ($query) => $query->where('id_tahun_ajaran', $semesterAktif->id_tahun_ajaran),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('nama_kelas')
            ->get(['id_kelas', 'nama_kelas'])
            ->map(fn ($k) => ['id' => $k->id_kelas, 'nama' => $k->nama_kelas])
            ->all();

        $prefix = $role === 'kepsek' ? 'kepsek' : 'tu';

        return view('mutasi.index', [
            'mutasi' => $rows,
            'siswa' => $siswaAktif,
            'sekolah' => $sekolah,
            'kelasTujuan' => $kelasTujuan,
            'roleMutasi' => $role,
            'judul' => $role === 'kepsek' ? 'Persetujuan Mutasi Siswa' : 'Mutasi Siswa',
            'sub' => $role === 'kepsek'
                ? 'Tinjau dan putuskan pengajuan Pindah Keluar atau status keluar siswa.'
                : 'Ajukan Pindah Masuk secara administratif, dan pantau status pengajuan mutasi siswa.',
            'bisaAjukan' => $role === 'tu',
            'bisaPutuskan' => $role === 'kepsek',
            'simpanRoute' => 'tu.mutasi.simpan',
            'putuskanRoute' => 'kepsek.mutasi.putuskan',
            'revisiRoute' => 'tu.mutasi.revisi',
            'batalkanRoute' => 'tu.mutasi.batalkan',
            'lampiranRoute' => $prefix.'.mutasi.lampiran',
            'kembaliRoute' => $prefix.'.mutasi',
        ]);
    }

    // ---- KEPALA SEKOLAH ----
    public function kepsekDashboard(Request $request)
    {
        $periode = $this->periodeAktifSekarang();

        $statistik = [
            'siswa_aktif' => DB::table('siswa as s')
                ->join('status_siswa as st', 'st.id_status_siswa', '=', 's.id_status_siswa')
                ->where('st.kode_status', 'aktif')
                ->count(),
            'total_guru' => DB::table('guru')->count(),
            'total_pendaftar' => DB::table('pendaftaran_ppdb')->count('id_pendaftaran'),
            'disahkan' => DB::table('pendaftaran_ppdb')
                ->where('status_pendaftaran', 'diterima')
                ->count('id_pendaftaran'),
            'kelengkapan' => null,
            'rata_verifikasi' => null,
        ];

        if ($periode) {
            $rekap = DB::table('keanggotaan_kelas as kk')
                ->where('kk.status', 'aktif')
                ->leftJoin('konfirmasi_data as cd', function ($join) use ($periode) {
                    $join->on('cd.id_siswa', '=', 'kk.id_siswa')
                        ->where('cd.id_periode_update', '=', $periode->id_periode_update);
                })
                ->leftJoin('pengajuan_perubahan as pp', function ($join) use ($periode) {
                    $join->on('pp.id_siswa', '=', 'kk.id_siswa')
                        ->where('pp.id_periode_update', '=', $periode->id_periode_update);
                })
                ->select(
                    DB::raw('COUNT(DISTINCT kk.id_siswa) AS total'),
                    DB::raw("COUNT(DISTINCT kk.id_siswa) FILTER (WHERE cd.status = 'dikonfirmasi') AS konfirmasi"),
                    DB::raw("COUNT(DISTINCT kk.id_siswa) FILTER (WHERE pp.status_pengajuan = 'menunggu') AS pengajuan")
                )
                ->first();

            if ($rekap && (int) $rekap->total > 0) {
                $statistik['kelengkapan'] = (int) round(
                    (((int) $rekap->konfirmasi + (int) $rekap->pengajuan) / (int) $rekap->total) * 100
                );
            }
        }

        $durasi = DB::table('pendaftaran_ppdb')
            ->whereNotNull('diperiksa_pada')
            ->whereNotNull('diajukan_pada')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (diperiksa_pada - diajukan_pada)) / 86400) AS rata_hari')
            ->first();

        if ($durasi && $durasi->rata_hari !== null) {
            $statistik['rata_verifikasi'] = (int) round((float) $durasi->rata_hari);
        }

        $filterKelas = $this->rekapKelengkapanKelas($request, $periode, 'kelas_kepsek_page', 'kelas');

        $mutasiMenunggu = MutasiSiswa::query()
            ->with('siswa:id_siswa,nama_lengkap')
            ->where('status', 'menunggu')
            ->orderByDesc('tgl_pengajuan')
            ->limit(5)
            ->get()
            ->map(fn (MutasiSiswa $m) => [
                'id' => $m->id_mutasi,
                'nama' => $m->siswa?->nama_lengkap ?? '-',
                'jenis' => match ($m->jenis) {
                    'pindah_masuk' => 'Pindah masuk',
                    'pindah_keluar' => 'Pindah keluar',
                    'keluar' => 'Keluar',
                    default => $m->jenis,
                },
                'tgl' => $m->tgl_pengajuan
                    ? Carbon::parse($m->tgl_pengajuan)->locale('id')->translatedFormat('d M Y')
                    : '-',
            ]);

        $totalMutasiMenunggu = MutasiSiswa::where('status', 'menunggu')->count();

        return view('kepsek.dashboard', [
            'kelas' => $filterKelas['kelas'],
            'filterKelas' => $filterKelas,
            'statistik' => $statistik,
            'mutasiMenunggu' => $mutasiMenunggu,
            'totalMutasiMenunggu' => $totalMutasiMenunggu,
            'tahunAktif' => DB::table('tahun_ajaran')
                ->where('is_aktif', true)
                ->orderByDesc('tanggal_mulai')
                ->value('nama_tahun_ajaran'),
        ]);
    }

    public function pengguna(Request $request)
    {
        $cari = trim($request->query('cari', ''));

        $pengguna = Pengguna::query()
            ->select(['id_pengguna', 'id_role', 'username', 'terakhir_login', 'is_aktif'])
            ->with([
                'role:id_role,nama_role',
                'guru:id_guru,id_pengguna,nama_guru',
                'staf:id_staf,id_pengguna,nama_staf',
            ])
            ->whereHas('role', fn ($query) => $query->whereIn('nama_role', ['tu', 'wali_kelas']))
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
                    'role' => ($u->role->nama_role ?? null) === 'wali_kelas'
                        ? 'Wali Kelas'
                        : 'Tata Usaha',
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
            'id_role' => ['required', 'integer', Rule::in(Role::whereIn('nama_role', ['tu', 'wali_kelas'])->pluck('id_role')->all())],
            'nip' => ['nullable', 'string', 'max:40'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $role = Role::findOrFail($data['id_role']);

        if (! in_array($role->nama_role, ['tu', 'wali_kelas'], true)) {
            return back()
                ->withInput()
                ->withErrors([
                    'id_role' => 'Akun yang dapat ditambahkan hanya Wali Kelas atau Tata Usaha.',
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

            if ($role->nama_role === 'wali_kelas') {
                Guru::create([
                    'id_pengguna' => $pengguna->id_pengguna,
                    'nip' => $data['nip'] ?: null,
                    'nama_guru' => $data['nama'],
                    'no_telepon' => $data['no_hp'] ?: null,
                    'is_aktif' => true,
                ]);
            } elseif ($role->nama_role === 'tu') {
                Staf::create([
                    'id_pengguna' => $pengguna->id_pengguna,
                    'nama_staf' => $data['nama'],
                    'jabatan' => 'Tata Usaha',
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
        $roles = Role::whereIn('nama_role', ['wali_kelas', 'tu'])->orderBy('id_role')->get();

        return view('admin.pengguna-tambah', compact('roles'));
    }

    public function penggunaEdit($id)
    {
        $user = Pengguna::query()
            ->select(['id_pengguna', 'id_role', 'username'])
            ->with([
                'role:id_role,nama_role',
                'guru:id_guru,id_pengguna,nama_guru,nip,no_telepon',
                'staf:id_staf,id_pengguna,nama_staf,no_telepon',
            ])
            ->findOrFail($id);

        $roles = Role::whereIn('nama_role', ['wali_kelas', 'tu'])->orderBy('id_role')->get();

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
                Rule::unique('pengguna', 'username')
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

            if ($role->nama_role === 'wali_kelas') {
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

        $user->is_aktif = ! $user->is_aktif;
        $user->diubah_pada = now();
        $user->save();

        $pesan = $user->is_aktif
            ? 'Akun berhasil diaktifkan.'
            : 'Akun berhasil dinonaktifkan.';

        return redirect()
            ->route('admin.pengguna')
            ->with('success', $pesan);
    }

    public function staf()
    {
        return view('admin.staf', ['guru' => [], 'staf' => []]);
    }

    public function pengaturan()
    {
        return view('admin.pengaturan', ['set' => []]);
    }

    public function logLogin()
    {
        $log = LogLogin::query()
            ->orderByDesc('waktu')
            ->limit(100)
            ->get()
            ->map(function ($l) {
                return [
                    'waktu' => $l->waktu
                        ? Carbon::parse($l->waktu)
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
        $log = LogAudit::query()
            ->orderByDesc('waktu')
            ->limit(100)
            ->get();

        return view('admin.audit', compact('log'));
    }

    /* ---------------- data tampilan dari database ---------------- */

    /** Data siswa milik akun yang sedang masuk (null bila belum terhubung). */
    private function siswaSesi(): ?Siswa
    {
        if (! session('id_pengguna')) {
            return null;
        }

        return Siswa::where('id_pengguna', session('id_pengguna'))->first();
    }

    /** Data siswa untuk halaman profil/konfirmasi, dengan data contoh bila kosong. */
    private function siswaTampilan(): array
    {
        $s = $this->siswaSesi();

        if (! $s) {
            return array_fill_keys([
                'nama_lengkap', 'nis', 'nisn', 'nik', 'no_kk', 'jenis_kelamin',
                'tempat_lahir', 'tanggal_lahir', 'agama', 'no_hp', 'email',
                'sekolah_asal', 'kelas', 'status', 'alamat', 'alamat_rt',
                'alamat_rw', 'alamat_kelurahan', 'alamat_kecamatan', 'alamat_kota',
                'alamat_provinsi', 'alamat_kode_pos', 'ayah', 'ayah_no_telepon',
                'ayah_email', 'ayah_alamat', 'ibu', 'ibu_no_telepon', 'ibu_email',
                'ibu_alamat', 'wali', 'wali_no_telepon', 'wali_email', 'wali_alamat',
            ], '-');
        }

        $idPendaftaran = $this->idPendaftaranSiswa($s);
        $pendaftaran = $idPendaftaran
            ? DB::table('pendaftaran_ppdb')
                ->where('id_pendaftaran', $idPendaftaran)
                ->first(['no_kk', 'sekolah_asal'])
            : null;
        $alamat = DB::table('alamat_siswa')
            ->where('id_siswa', $s->id_siswa)
            ->orderBy('id_alamat')
            ->first();
        $orangTua = DB::table('orang_tua_wali')
            ->where('id_siswa', $s->id_siswa)
            ->orderBy('id_orang_tua_wali')
            ->get(['id_orang_tua_wali', 'id_siswa', 'hubungan', 'nama_lengkap', 'no_telepon', 'email', 'alamat'])
            ->unique(fn ($orang) => mb_strtolower($orang->hubungan))
            ->keyBy(fn ($orang) => mb_strtolower($orang->hubungan));
        $ayah = $orangTua->get('ayah');
        $ibu = $orangTua->get('ibu');
        $wali = $orangTua->get('wali');
        $kelas = DB::table('keanggotaan_kelas as kk')
            ->join('kelas as k', 'k.id_kelas', '=', 'kk.id_kelas')
            ->where('kk.id_siswa', $s->id_siswa)
            ->orderByDesc('kk.id_keanggotaan')
            ->value('k.nama_kelas');
        $sekolahAsal = $pendaftaran->sekolah_asal ?? null;

        if (! $sekolahAsal && $s->id_sekolah_asal) {
            $sekolahAsal = DB::table('sekolah')
                ->where('id_sekolah', $s->id_sekolah_asal)
                ->value('nama_sekolah');
        }

        return [
            'nama_lengkap' => $s->nama_lengkap,
            'nis' => $s->nis ?? '-',
            'nisn' => $s->nisn ?? '-',
            'nik' => $s->nik ?? '-',
            'no_kk' => $pendaftaran->no_kk ?? '-',
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
            'email' => $s->email ?? '-',
            'sekolah_asal' => $sekolahAsal ?: '-',
            'kelas' => $kelas ?? '-',
            'status' => DB::table('status_siswa')->where('id_status_siswa', $s->id_status_siswa)->value('nama_status') ?? '-',
            'alamat' => $alamat->alamat ?? '-',
            'alamat_rt' => $alamat->rt ?? '-',
            'alamat_rw' => $alamat->rw ?? '-',
            'alamat_kelurahan' => $alamat->kelurahan ?? '-',
            'alamat_kecamatan' => $alamat->kecamatan ?? '-',
            'alamat_kota' => $alamat->kota ?? '-',
            'alamat_provinsi' => $alamat->provinsi ?? '-',
            'alamat_kode_pos' => $alamat->kode_pos ?? '-',
            'ayah' => $ayah->nama_lengkap ?? '-',
            'ayah_no_telepon' => $ayah->no_telepon ?? '-',
            'ayah_email' => $ayah->email ?? '-',
            'ayah_alamat' => $ayah->alamat ?? '-',
            'ibu' => $ibu->nama_lengkap ?? '-',
            'ibu_no_telepon' => $ibu->no_telepon ?? '-',
            'ibu_email' => $ibu->email ?? '-',
            'ibu_alamat' => $ibu->alamat ?? '-',
            'wali' => $wali->nama_lengkap ?? '-',
            'wali_no_telepon' => $wali->no_telepon ?? '-',
            'wali_email' => $wali->email ?? '-',
            'wali_alamat' => $wali->alamat ?? '-',
        ];
    }

    /**
     * role_siswa tidak memiliki SELECT pada dokumen_siswa, sehingga metadata
     * dokumen dibaca lewat koneksi aplikasi. Kepemilikan tetap divalidasi di PHP.
     */
    private function dokumenSiswaQuery(): Builder
    {
        return DB::connection('pgsql_login')->table('dokumen_siswa');
    }

    private function idPendaftaranSiswa(Siswa $siswa): ?int
    {
        $idPendaftaran = $this->dokumenSiswaQuery()
            ->where('id_siswa', $siswa->id_siswa)
            ->whereNotNull('id_pendaftaran')
            ->orderByDesc('id_pendaftaran')
            ->value('id_pendaftaran');

        if (! $idPendaftaran && $siswa->nisn) {
            $idPendaftaran = DB::table('pendaftaran_ppdb')
                ->where('nisn', $siswa->nisn)
                ->orderByDesc('id_pendaftaran')
                ->value('id_pendaftaran');
        }

        return $idPendaftaran ? (int) $idPendaftaran : null;
    }

    /** Periode update yang sedang berjalan; data contoh dipakai bila tabel masih kosong. */
    private function periodeBerjalan(): array
    {
        $periode = DB::table('periode_update')
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_periode_update')
            ->first();

        if (! $periode) {
            return ['dibuka' => false, 'id' => null, 'tahun' => '-', 'tutup' => '-'];
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

    /** Periode update yang aktif menurut status dan rentang tanggalnya (null bila tidak ada). */
    private function periodeAktifSekarang(): ?object
    {
        $hariIni = now()->toDateString();

        return DB::table('periode_update')
            ->where('status', 'aktif')
            ->whereDate('tanggal_mulai', '<=', $hariIni)
            ->whereDate('tanggal_selesai', '>=', $hariIni)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_periode_update')
            ->first();
    }

    /** Periode update berjalan untuk tampilan siswa (tanpa data contoh). */
    private function periodeUpdateBerjalan(): array
    {
        $periode = $this->periodeAktifSekarang();

        if (! $periode) {
            return ['dibuka' => false, 'id' => null, 'tahun' => null, 'tutup' => '-'];
        }

        $tahun = DB::table('tahun_ajaran')
            ->where('id_tahun_ajaran', $periode->id_tahun_ajaran)
            ->value('nama_tahun_ajaran');

        return [
            'dibuka' => true,
            'id' => $periode->id_periode_update,
            'tahun' => $tahun ?? $periode->nama_periode,
            'tutup' => Carbon::parse($periode->tanggal_selesai)->locale('id')->translatedFormat('d M Y'),
        ];
    }

    /** Pengajuan perubahan milik satu siswa (kosong bila tidak ada). */
    private function pengajuanMilikSiswa(?int $idSiswa): array
    {
        if (! $idSiswa) {
            return [];
        }

        return DB::table('pengajuan_perubahan as pp')
            ->join('detail_pengajuan as dp', 'dp.id_pengajuan', '=', 'pp.id_pengajuan')
            ->leftJoin('siswa as s', 's.id_siswa', '=', 'pp.id_siswa')
            ->where('pp.id_siswa', $idSiswa)
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

    /**
     * Rekap kelengkapan data per kelas aktif pada periode update berjalan (tanpa data contoh).
     *
     * @return array{kelas: LengthAwarePaginator, tingkat: string, jurusan: string, opsi_tingkat: array<int, string>, opsi_jurusan: array<int, string>, total: int}
     */
    private function rekapKelengkapanKelas(Request $request, ?object $periode = null, string $pageName = 'kelas_page', string $fragment = 'kelas'): array
    {
        $periode ??= $this->periodeAktifSekarang();
        $rekap = collect();

        if ($periode) {
            $rekap = DB::table('keanggotaan_kelas as kk')
                ->where('kk.status', 'aktif')
                ->leftJoin('konfirmasi_data as cd', function ($join) use ($periode) {
                    $join->on('cd.id_siswa', '=', 'kk.id_siswa')
                        ->where('cd.id_periode_update', '=', $periode->id_periode_update);
                })
                ->leftJoin('pengajuan_perubahan as pp', function ($join) use ($periode) {
                    $join->on('pp.id_siswa', '=', 'kk.id_siswa')
                        ->where('pp.id_periode_update', '=', $periode->id_periode_update);
                })
                ->groupBy('kk.id_kelas')
                ->select(
                    'kk.id_kelas',
                    DB::raw('COUNT(DISTINCT kk.id_siswa) AS total_siswa'),
                    DB::raw("COUNT(DISTINCT kk.id_siswa) FILTER (WHERE cd.status = 'dikonfirmasi') AS sudah_konfirmasi"),
                    DB::raw("COUNT(DISTINCT kk.id_siswa) FILTER (WHERE pp.status_pengajuan = 'menunggu') AS mengajukan_perubahan")
                )
                ->get()
                ->keyBy('id_kelas');
        }

        $semuaKelas = Kelas::urutAlami(
            Kelas::query()
                ->where('is_aktif', true)
                ->get(['id_kelas', 'nama_kelas'])
        );

        $opsiTingkat = ['X', 'XI', 'XII'];
        $opsiJurusan = $semuaKelas
            ->map(fn (Kelas $kelas) => $kelas->jurusanLabel())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $tingkat = $request->query('tingkat');
        $tingkat = is_string($tingkat) && in_array($tingkat, $opsiTingkat, true) ? $tingkat : 'X';

        $jurusanDefault = in_array('MIA', $opsiJurusan, true) ? 'MIA' : '';
        $jurusan = $request->query('jurusan');
        $jurusan = is_string($jurusan) && ($jurusan === '' || in_array($jurusan, $opsiJurusan, true))
            ? $jurusan
            : $jurusanDefault;

        $kelas = $semuaKelas
            ->filter(fn (Kelas $k) => $k->tingkatLabel() === $tingkat
                && ($jurusan === '' || $k->jurusanLabel() === $jurusan))
            ->map(function (Kelas $k) use ($rekap, $periode) {
                $baris = $periode ? $rekap->get($k->id_kelas) : null;
                $total = (int) ($baris->total_siswa ?? 0);
                $konfirmasi = (int) ($baris->sudah_konfirmasi ?? 0);
                $pengajuan = (int) ($baris->mengajukan_perubahan ?? 0);

                return [
                    'nama_kelas' => $k->nama_kelas,
                    'total_siswa' => $total,
                    'sudah_konfirmasi' => $konfirmasi,
                    'mengajukan_perubahan' => $pengajuan,
                    'belum_respons' => max($total - $konfirmasi - $pengajuan, 0),
                ];
            })
            ->values();

        $total = $kelas->count();
        $perHalaman = 10;
        $halamanTerakhir = max((int) ceil($total / $perHalaman), 1);
        $halaman = min(max((int) $request->query($pageName, 1), 1), $halamanTerakhir);

        $paginator = new LengthAwarePaginator(
            $kelas->forPage($halaman, $perHalaman)->values(),
            $total,
            $perHalaman,
            $halaman,
            ['path' => $request->url(), 'pageName' => $pageName]
        );
        $paginator->withQueryString()->fragment($fragment);

        return [
            'kelas' => $paginator,
            'tingkat' => $tingkat,
            'jurusan' => $jurusan,
            'opsi_tingkat' => $opsiTingkat,
            'opsi_jurusan' => $opsiJurusan,
            'total' => $total,
        ];
    }

    private function daftarTahunAjaran(): array
    {
        $rows = DB::table('tahun_ajaran')
            ->orderByDesc('tanggal_mulai')
            ->get(['id_tahun_ajaran', 'nama_tahun_ajaran']);

        return $rows->map(fn ($r) => [
            'id' => $r->id_tahun_ajaran,
            'nama' => $r->nama_tahun_ajaran,
        ])->all();
    }

    private function daftarPengajuanSiswa(?int $idSiswa): array
    {
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

        return $query->get()
            ->reject(fn ($p) => in_array(strtolower((string) $p->nama_field), ['jenis_kelamin', 'jenis kelamin'], true))
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
            'field' => $this->labelFieldPengajuan($p->nama_field),
            'lama' => $teks($p->nilai_lama),
            'baru' => $teks($p->nilai_baru),
            'tgl' => $p->tgl_pengajuan
                ? Carbon::parse($p->tgl_pengajuan)->locale('id')->translatedFormat('d M Y')
                : '-',
            'status' => $this->statusTampilan($p->status_pengajuan),
        ];
    }

    /** Label tampilan untuk nama_field yang sudah memakai nama kolom database. */
    private function labelFieldPengajuan(?string $nama): string
    {
        $nama = (string) $nama;

        if ($nama === '') {
            return '-';
        }

        if (! array_key_exists($nama, self::$labelFieldCache)) {
            $label = DB::table('ref_field_data')
                ->where('nama_kolom', $nama)
                ->value('label_field');

            self::$labelFieldCache[$nama] = $label ?: $nama;
        }

        return self::$labelFieldCache[$nama];
    }

    private function daftarAntrean(int $limit = 0): array
    {
        $query = $this->queryAntrean(sertakanDisahkan: false);

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query
            ->get(['id_pendaftaran', 'nama_calon_siswa', 'nisn', 'diajukan_pada', 'status_pendaftaran'])
            ->map(fn ($r) => $this->barisAntrean($r))
            ->all();
    }

    private function queryAntrean(string $cari = '', string $status = 'semua', bool $sertakanDisahkan = true): Builder
    {
        $statusAktif = ['menunggu', 'perlu_perbaikan', 'diverifikasi'];
        $statusDatabase = [
            'menunggu' => 'menunggu',
            'disetujui' => 'diverifikasi',
            'dikembalikan' => 'perlu_perbaikan',
            'disahkan' => 'diterima',
        ];

        return DB::table('pendaftaran_ppdb')
            ->whereIn(
                'status_pendaftaran',
                $sertakanDisahkan ? [...$statusAktif, 'diterima'] : $statusAktif
            )
            ->when(
                $status !== 'semua' && isset($statusDatabase[$status]),
                fn ($query) => $query->where('status_pendaftaran', $statusDatabase[$status])
            )
            ->when($cari !== '', function ($query) use ($cari) {
                $kataKunci = '%'.mb_strtolower($cari).'%';

                $query->where(function ($query) use ($kataKunci) {
                    $query->whereRaw('LOWER(nama_calon_siswa) LIKE ?', [$kataKunci])
                        ->orWhereRaw('LOWER(nisn) LIKE ?', [$kataKunci]);
                });
            })
            ->orderByDesc('diajukan_pada')
            ->orderByDesc('id_pendaftaran');
    }

    /** @return array<string, int> */
    private function hitungAntrean(string $cari = ''): array
    {
        $jumlahPerStatus = $this->queryAntrean($cari)
            ->reorder()
            ->select('status_pendaftaran', DB::raw('COUNT(*) AS jumlah'))
            ->groupBy('status_pendaftaran')
            ->pluck('jumlah', 'status_pendaftaran');

        $hitung = ['semua' => (int) $jumlahPerStatus->sum()];

        foreach ($jumlahPerStatus as $statusDatabase => $jumlah) {
            $status = $this->statusTampilan((string) $statusDatabase);
            $hitung[$status] = ($hitung[$status] ?? 0) + (int) $jumlah;
        }

        return $hitung;
    }

    private function barisAntrean(object $r): array
    {
        return [
            'id' => $r->id_pendaftaran,
            'nama' => $r->nama_calon_siswa,
            'nisn' => $r->nisn ?? '-',
            'jenis' => 'PPDB baru',
            'tgl' => Carbon::parse($r->diajukan_pada)->locale('id')->translatedFormat('d M Y'),
            'status' => $this->statusTampilan($r->status_pendaftaran),
        ];
    }

    private function daftarKelasRingkas(): array
    {
        $semester = DB::table('semester')
            ->where('is_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_semester')
            ->first(['id_tahun_ajaran']);

        $rows = DB::table('kelas')
            ->where('is_aktif', true)
            ->when($semester?->id_tahun_ajaran, fn ($query) => $query->where(function ($kelasQuery) use ($semester) {
                $kelasQuery->whereNull('id_tahun_ajaran')
                    ->orWhere('id_tahun_ajaran', $semester->id_tahun_ajaran);
            }))
            ->orderBy('nama_kelas')
            ->get(['id_kelas', 'nama_kelas']);

        return $rows->map(fn ($k) => [
            'id' => $k->id_kelas,
            'nama' => $k->nama_kelas,
        ])->all();
    }

    /** Data guru milik akun yang sedang masuk. */
    private function guruSesi(): ?object
    {
        if (! session('id_pengguna')) {
            return null;
        }

        return DB::table('guru')
            ->where('id_pengguna', session('id_pengguna'))
            ->first(['id_guru']);
    }

    /** Semester aktif; bila belum ada, semester terbaru dipakai untuk tampilan. */
    private function semesterAktif(): ?object
    {
        $idSemesterAktif = DB::getDriverName() === 'pgsql'
            ? DB::selectOne('SELECT public.fn_semester_aktif() AS id_semester')?->id_semester
            : DB::table('semester')
                ->where('is_aktif', true)
                ->orderByDesc('tanggal_mulai')
                ->orderByDesc('id_semester')
                ->value('id_semester');

        return ($idSemesterAktif
            ? DB::table('semester')->where('id_semester', $idSemesterAktif)->first()
            : null)
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

    /**
     * Petakan tiga komponen nilai yang dipakai form rapor ke komponen
     * penilaian yang tersedia tanpa mengubah konfigurasi akademik.
     */
    private function komponenRapor(): array
    {
        $rows = DB::table('komponen_penilaian')
            ->orderBy('id_komponen')
            ->get(['id_komponen', 'nama_komponen'])
            ->map(fn ($row) => [
                'id' => (int) $row->id_komponen,
                'nama' => mb_strtolower((string) $row->nama_komponen),
            ])
            ->all();

        $pilih = function (array $kataKunci, array $dipakai) use ($rows): ?int {
            foreach ($kataKunci as $kunci) {
                foreach ($rows as $row) {
                    if (! in_array($row['id'], $dipakai, true) && str_contains($row['nama'], $kunci)) {
                        return $row['id'];
                    }
                }
            }

            foreach ($rows as $row) {
                if (! in_array($row['id'], $dipakai, true)) {
                    return $row['id'];
                }
            }

            return null;
        };

        $tugas = $pilih(['tugas'], []);
        $keterampilan = $pilih(
            ['keterampilan', 'praktik', 'praktikum', 'proyek', 'quiz', 'kuis'],
            array_values(array_filter([$tugas], fn ($id) => $id !== null))
        );
        $ujian = $pilih(
            ['ujian', 'uts', 'pts', 'uas', 'pas'],
            array_values(array_filter([$tugas, $keterampilan], fn ($id) => $id !== null))
        );

        return [
            'tugas' => $tugas,
            'keterampilan' => $keterampilan,
            'ujian' => $ujian,
        ];
    }

    /** Bobot tiap komponen yang dipetakan ke form rapor. */
    private function bobotKomponenRapor(array $komponen): array
    {
        $ids = array_values(array_filter($komponen, fn ($id) => $id !== null));
        $bobot = $ids
            ? DB::table('komponen_penilaian')
                ->whereIn('id_komponen', $ids)
                ->pluck('bobot_persen', 'id_komponen')
                ->all()
            : [];

        $hasil = [];

        foreach ($komponen as $jenis => $idKomponen) {
            $hasil[$jenis] = $idKomponen !== null ? (float) ($bobot[$idKomponen] ?? 0) : 0.0;
        }

        return $hasil;
    }

    /** Rata-rata berbobot dari komponen yang sudah diisi. */
    private function nilaiAkhirKomponen(array $nilai, array $bobot): ?float
    {
        $jumlah = 0.0;
        $totalBobot = 0.0;

        foreach ($nilai as $jenis => $angka) {
            if ($angka === null || $angka === '') {
                continue;
            }

            $bobotKomponen = (float) ($bobot[$jenis] ?? 0);

            if ($bobotKomponen <= 0) {
                continue;
            }

            $jumlah += (float) $angka * $bobotKomponen;
            $totalBobot += $bobotKomponen;
        }

        return $totalBobot > 0 ? round($jumlah / $totalBobot, 2) : null;
    }

    /** Normalisasi kode kehadiran ke kunci rekap huruf kecil. */
    private function kodeKehadiran($kode): string
    {
        $kode = strtolower(trim((string) $kode));

        return match ($kode) {
            'h', 'hadir' => 'hadir',
            'i', 'izin' => 'izin',
            's', 'sakit' => 'sakit',
            'a', 'alpa', 'alpha' => 'alpha',
            default => $kode,
        };
    }

    /** Kode huruf yang dipakai pilihan form absensi. */
    private function kodeKehadiranForm($kode): string
    {
        return match ($this->kodeKehadiran($kode)) {
            'hadir' => 'H',
            'izin' => 'I',
            'sakit' => 'S',
            'alpha' => 'A',
            default => 'H',
        };
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
                'judul' => 'Pengajuan perlu diperbaiki',
                'ket' => $catatan ?: 'Perbaiki data atau berkas melalui formulir, lalu kirim ulang.',
                'state' => 'perhatian',
            ],
            'ditolak' => [
                'judul' => 'Pengajuan perlu diperbaiki',
                'ket' => $catatan ?: 'Perbaiki data atau berkas melalui formulir, lalu kirim ulang.',
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
                    : (in_array($status, ['ditolak', 'perlu_perbaikan'], true) ? 'Menunggu perbaikan pengajuan.' : 'Menunggu'),
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
