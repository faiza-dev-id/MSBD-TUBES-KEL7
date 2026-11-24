<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_teacher_class_roster_paginates_ten_students_with_absolute_numbers(): void
    {
        [$idGuru, $session] = $this->createWaliKelas();
        $idSemester = $this->createSemester();
        $idKelas = $this->createClass('X MIA 1');
        $this->assignClass($idGuru, $idKelas, $idSemester);

        for ($number = 1; $number <= 12; $number++) {
            $idSiswa = $this->createStudent(sprintf('Siswa %02d', $number));
            $this->assignStudent($idSiswa, $idKelas, $idSemester);
        }

        $halamanPertama = $this->withSession($session)
            ->get(route('guru.kelas'))
            ->assertOk()
            ->assertSee('12 siswa')
            ->assertSee('Siswa 10')
            ->assertDontSee('Siswa 11')
            ->assertSee('<td class="p-2.5 text-mute">10</td>', false);

        $halamanKedua = $this->withSession($session)
            ->get(route('guru.kelas', ['page' => 2]))
            ->assertOk()
            ->assertSee('Siswa 11')
            ->assertSee('Siswa 12')
            ->assertSee('<td class="p-2.5 text-mute">11</td>', false);

        $this->assertNotSame($halamanPertama->getContent(), $halamanKedua->getContent());
    }

    public function test_homeroom_home_redirects_to_the_class_roster(): void
    {
        [, $session] = $this->createWaliKelas();

        $this->withSession($session)
            ->get(route('beranda'))
            ->assertRedirect(route('guru.kelas'));
    }

    public function test_teacher_note_history_paginates_after_ten_records(): void
    {
        [$idGuru, $session] = $this->createWaliKelas();
        $this->createSemester();

        for ($number = 1; $number <= 11; $number++) {
            $idSiswa = $this->createStudent(sprintf('Siswa %02d', $number));
            DB::table('catatan_wali_kelas')->insert([
                'id_siswa' => $idSiswa,
                'id_guru' => $idGuru,
                'isi_catatan' => sprintf('Catatan %02d', $number),
                'tgl_dibuat' => sprintf('2026-10-%02d 08:00:00', 12 - $number),
            ]);
        }

        $this->withSession($session)
            ->get(route('guru.catatan'))
            ->assertOk()
            ->assertSee('Catatan 10')
            ->assertDontSee('Catatan 11')
            ->assertSee('Menampilkan 1-10 dari 11 catatan');

        $this->withSession($session)
            ->get(route('guru.catatan', ['page' => 2]))
            ->assertOk()
            ->assertSee('Catatan 11')
            ->assertSee('Menampilkan 11-11 dari 11 catatan');
    }

    public function test_attendance_page_uses_the_first_assigned_class_as_read_only_text(): void
    {
        [$idGuru, $session] = $this->createWaliKelas();
        $idSemester = $this->createSemester();
        $idKelasPertama = $this->createClass('X MIA 1');
        $idKelasKedua = $this->createClass('X MIA 2');
        $this->assignClass($idGuru, $idKelasPertama, $idSemester);
        $this->assignClass($idGuru, $idKelasKedua, $idSemester);

        $this->withSession($session)
            ->get(route('guru.absensi', ['kelas' => $idKelasKedua]))
            ->assertOk()
            ->assertSee('<input class="input" id="kelas" type="text" value="X MIA 1" readonly>', false)
            ->assertDontSee('<select class="input" id="kelas"', false);

    }

    public function test_homeroom_schedule_is_not_exposed_in_the_route_or_navigation(): void
    {
        [, $session] = $this->createWaliKelas();

        $this->withSession($session)
            ->get('/guru/jadwal')
            ->assertNotFound();

        $this->withSession($session)
            ->get(route('guru.kelas'))
            ->assertOk()
            ->assertDontSee('Jadwal Mengajar');
    }

    public function test_principal_navigation_hides_ppdb_references_and_route_is_not_found(): void
    {
        $idRole = $this->createRole('kepsek');
        $idPengguna = $this->createAccount('kepsek-test', $idRole);
        $session = $this->sessionFor($idPengguna, 'kepsek-test', 'kepsek');

        $this->withSession($session)
            ->get(route('akun.sandi'))
            ->assertOk()
            ->assertDontSee('Data Referensi PPDB');

        $this->withSession($session)
            ->get('/kepsek/referensi')
            ->assertNotFound();
    }

    public function test_student_schedule_defaults_to_daily_mode_and_keeps_the_full_roster(): void
    {
        $idRole = $this->createRole('siswa');
        $idPengguna = $this->createAccount('siswa-jadwal', $idRole);
        $idSemester = $this->createSemester();
        $idKelas = $this->createClass('XI IIS 1');
        $idSiswa = $this->createStudent('Siswa Jadwal', $idPengguna);
        $this->assignStudent($idSiswa, $idKelas, $idSemester);
        [$idGuru] = $this->createWaliKelas();
        $idMapel = (int) DB::table('mata_pelajaran')->insertGetId([
            'nama_mapel' => 'Biologi',
            'is_aktif' => true,
        ], 'id_mata_pelajaran');

        DB::table('jadwal_pelajaran')->insert([
            'id_kelas' => $idKelas,
            'id_semester' => $idSemester,
            'id_mata_pelajaran' => $idMapel,
            'id_guru' => $idGuru,
            'hari' => 3,
            'jam_mulai' => '09:20:00',
            'jam_selesai' => '10:00:00',
            'ruang' => 'XI IIS 1',
        ]);

        $session = $this->sessionFor($idPengguna, 'siswa-jadwal', 'siswa');

        $this->withSession($session)
            ->get(route('siswa.jadwal'))
            ->assertOk()
            ->assertSee('data-jadwal-mode-switch', false)
            ->assertSee('data-jadwal-mode-button="harian"', false)
            ->assertSee('data-jadwal-mode-button="roster"', false)
            ->assertSee('data-jadwal-mode-panel="harian"', false)
            ->assertSee('data-jadwal-mode-panel="roster" hidden', false)
            ->assertSee('Roster lengkap')
            ->assertSee('Biologi')
            ->assertSee('Guru Pengajar')
            ->assertSee('Wali Test')
            ->assertSee('<table', false)
            ->assertDontSee('data-hari-tab="7"', false);
    }

    public function test_student_navigation_exposes_rapor_route_but_unlinked_accounts_are_rejected(): void
    {
        $idRole = $this->createRole('siswa');
        $idPengguna = $this->createAccount('siswa-test', $idRole);
        $session = $this->sessionFor($idPengguna, 'siswa-test', 'siswa');

        $this->withSession($session)
            ->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('/siswa/rapor');

        $this->withSession($session)
            ->get(route('siswa.rapor'))
            ->assertNotFound();
    }

    public function test_student_profile_renders_database_backed_data_without_a_linked_ppdb_record(): void
    {
        $idRole = $this->createRole('siswa');
        $idPengguna = $this->createAccount('siswa-profil', $idRole);
        $idSiswa = $this->createStudent('Siswa Profil Test', $idPengguna);
        DB::table('agama')->insert(['id_agama' => 1, 'nama_agama' => 'Kristen']);
        DB::table('status_siswa')->insert(['id_status_siswa' => 1, 'nama_status' => 'Aktif']);
        DB::table('siswa')->where('id_siswa', $idSiswa)->update([
            'id_agama' => 1,
            'id_status_siswa' => 1,
        ]);

        $this->withSession($this->sessionFor($idPengguna, 'siswa-profil', 'siswa'))
            ->get(route('siswa.profil'))
            ->assertOk()
            ->assertSee('Siswa Profil Test')
            ->assertSee('Kristen')
            ->assertSee('Data formulir PPDB belum terhubung ke akun ini.');
    }

    public function test_student_profile_renders_linked_ppdb_data_with_an_active_religion(): void
    {
        $idRole = $this->createRole('siswa');
        $idPengguna = $this->createAccount('siswa-profil-ppdb', $idRole);
        $idSiswa = $this->createStudent('Siswa Profil PPDB', $idPengguna);
        DB::table('agama')->insert([
            'id_agama' => 1,
            'nama_agama' => 'Islam',
            'is_aktif' => true,
        ]);
        DB::table('siswa')->where('id_siswa', $idSiswa)->update(['id_agama' => 1]);
        DB::table('pendaftaran_ppdb')->insert([
            'id_pendaftaran' => 1,
            'nisn' => DB::table('siswa')->where('id_siswa', $idSiswa)->value('nisn'),
            'nama_calon_siswa' => 'Nama Arsip PPDB',
            'id_agama' => 1,
            'diajukan_pada' => now(),
        ]);
        DB::table('pekerjaan')->insert([
            'id_pekerjaan' => 7,
            'nama_pekerjaan' => 'PNS/ASN',
        ]);
        DB::table('ppdb_orang_tua')->insert([
            'id_pendaftaran' => 1,
            'hubungan' => 'ayah',
            'id_pekerjaan' => 7,
        ]);

        $this->withSession($this->sessionFor($idPengguna, 'siswa-profil-ppdb', 'siswa'))
            ->get(route('siswa.profil'))
            ->assertOk()
            ->assertSee('Nama Arsip PPDB')
            ->assertSee('PNS/TNI/Polri');
    }

    public function test_student_confirmation_displays_kk_and_school_from_the_linked_ppdb_registration(): void
    {
        $idRole = $this->createRole('siswa');
        $idPengguna = $this->createAccount('siswa-konfirmasi-ppdb', $idRole);
        $idSiswa = $this->createStudent('Siswa Konfirmasi', $idPengguna);
        $idPendaftaran = (int) DB::table('pendaftaran_ppdb')->insertGetId([
            'nisn' => DB::table('siswa')->where('id_siswa', $idSiswa)->value('nisn'),
            'nama_calon_siswa' => 'Siswa Konfirmasi',
            'no_kk' => '3273010101010001',
            'sekolah_asal' => 'SMP Negeri 1 Bandung',
        ], 'id_pendaftaran');
        DB::table('dokumen_siswa')->insert([
            'id_siswa' => $idSiswa,
            'id_pendaftaran' => $idPendaftaran,
            'jenis_dokumen' => 'kk',
        ]);
        DB::table('periode_update')->insert([
            'nama_periode' => 'Periode 2026',
            'status' => 'ditutup',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
        ]);

        $this->withSession($this->sessionFor($idPengguna, 'siswa-konfirmasi-ppdb', 'siswa'))
            ->get(route('siswa.konfirmasi'))
            ->assertOk()
            ->assertSee('3273010101010001')
            ->assertSee('SMP Negeri 1 Bandung');
    }

    public function test_homeroom_navigation_exposes_rapor_route(): void
    {
        [, $session] = $this->createWaliKelas();

        $this->withSession($session)
            ->get(route('guru.kelas'))
            ->assertOk()
            ->assertSee('/guru/rapor')
            ->assertSee('Rapor Siswa');
    }

    public function test_student_profile_displays_parent_data_labels_instead_of_codes(): void
    {
        $student = array_fill_keys([
            'nama_lengkap', 'nis', 'nisn', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
            'agama', 'no_hp', 'email', 'sekolah_asal', 'kelas', 'status', 'alamat', 'alamat_rt',
            'alamat_rw', 'alamat_kelurahan', 'alamat_kecamatan', 'alamat_kota', 'alamat_provinsi',
            'alamat_kode_pos', 'ayah', 'ayah_no_telepon', 'ayah_email', 'ayah_alamat', 'ibu',
            'ibu_no_telepon', 'ibu_email', 'ibu_alamat', 'wali', 'wali_no_telepon', 'wali_email',
            'wali_alamat',
        ], '-');
        $student['ayah_email'] = 'ayah@example.test';
        $student['ibu_email'] = 'ibu@example.test';
        $student['wali_email'] = 'wali@example.test';
        $ppdb = [
            'agama' => '01',
            'negara' => 'Negara yang disembunyikan',
            'keluar_karena' => 'Keluar yang disembunyikan',
            'ortu' => [
                [
                    'nama' => 'Ayah',
                    'pendidikan' => '09',
                    'pekerjaan' => '05',
                    'penghasilan' => '3',
                    'kebutuhan' => '02',
                ],
            ],
        ];
        view()->share('errors', new ViewErrorBag);

        $html = view('siswa.profil', [
            's' => $student,
            'ppdb' => $ppdb,
            'agamaOptions' => ['01' => 'Islam'],
            'dokumen' => collect(),
            'terhubung' => true,
        ])->render();

        $this->assertStringContainsString('D4/S1', $html);
        $this->assertStringContainsString('PNS/TNI/Polri', $html);
        $this->assertStringContainsString('1 juta - 1.999.999', $html);
        $this->assertStringContainsString('Tunarungu', $html);
        $this->assertStringContainsString('Islam', $html);
        $this->assertStringContainsString('wali@example.test', $html);
        $this->assertStringNotContainsString('ayah@example.test', $html);
        $this->assertStringNotContainsString('ibu@example.test', $html);
        $this->assertStringNotContainsString('Negara yang disembunyikan', $html);
        $this->assertStringNotContainsString('Data siswa keluar', $html);
        $this->assertStringNotContainsString('>09</dd>', $html);
        $this->assertStringNotContainsString('>05</dd>', $html);
    }

    public function test_tu_student_detail_displays_labels_and_hides_parent_email_and_country(): void
    {
        view()->share('errors', new ViewErrorBag);

        $html = view('tu.siswa-detail', [
            'siswa' => (object) [
                'id_siswa' => 1,
                'nama_lengkap' => 'Siswa Uji',
                'nis' => 'NIS-1',
                'nisn' => '1234567890',
                'nik' => null,
                'jenis_kelamin' => 'P',
                'tempat_lahir' => null,
                'tanggal_lahir' => null,
                'email' => null,
                'no_telepon' => null,
            ],
            'alamat' => null,
            'orangTua' => collect([
                (object) ['hubungan' => 'ayah', 'nama_lengkap' => 'Ayah Uji', 'no_telepon' => null, 'email' => 'ayah@example.test', 'alamat' => null],
            ]),
            'ppdb' => (object) [
                'nama_calon_siswa' => 'Siswa Uji',
                'hobi' => 'A',
                'cita_cita' => 'B',
                'negara' => 'Negara rahasia',
            ],
            'ppdbAlamat' => null,
            'ppdbPeriodik' => (object) ['tempat_tinggal' => '3', 'moda_transportasi' => '01'],
            'ppdbOrangTua' => collect(),
            'ppdbPrestasi' => collect(),
            'ppdbBeasiswa' => collect(),
            'ppdbKeluar' => (object) ['alasan_keluar' => 'Data keluar tetap terlihat oleh TU'],
            'dokumen' => collect(),
        ])->render();

        $this->assertStringContainsString('Olahraga', $html);
        $this->assertStringContainsString('TNI/POLRI', $html);
        $this->assertStringContainsString('Jalan kaki', $html);
        $this->assertStringContainsString('Data keluar tetap terlihat oleh TU', $html);
        $this->assertStringNotContainsString('Negara rahasia', $html);
        $this->assertStringNotContainsString('ayah@example.test', $html);
    }

    public function test_tu_ppdb_verification_displays_reference_labels_without_country(): void
    {
        view()->share('errors', new ViewErrorBag);

        $html = view('tu.verifikasi', [
            'row' => ['nama' => 'Siswa Uji', 'nisn' => '1234567890', 'tgl' => '01 Jan 2026', 'id' => 1],
            'detail' => [
                'nomor_dokumen_1' => '',
                'nomor_dokumen_2' => '',
                'agama' => '01',
                'hobi' => 'A',
                'cita_cita' => 'B',
                'berkebutuhan_khusus' => '01',
                'negara' => 'Negara rahasia',
                'ortu' => [
                    ['pendidikan' => '09', 'pekerjaan' => '05', 'penghasilan' => '3', 'kebutuhan' => '02'],
                ],
                'prestasi' => [['nama' => 'Olimpiade', 'jenis' => '1', 'tingkat' => '2', 'tahun' => '2025', 'penyelenggara' => 'Sekolah']],
                'beasiswa' => [['keterangan' => 'Beasiswa uji', 'jenis' => '01', 'tahun_mulai' => '2025', 'tahun_selesai' => '2026']],
            ],
            'agamaOptions' => ['01' => 'Islam'],
            'berkas' => [],
            'alasan' => [],
        ])->render();

        $this->assertStringContainsString('Agama', $html);
        $this->assertStringContainsString('Islam', $html);
        $this->assertStringContainsString('Olahraga', $html);
        $this->assertStringContainsString('Tunanetra', $html);
        $this->assertStringContainsString('D4/S1', $html);
        $this->assertStringContainsString('PNS/TNI/Polri', $html);
        $this->assertStringContainsString('Sains', $html);
        $this->assertStringContainsString('Anak berprestasi', $html);
        $this->assertStringNotContainsString('Negara rahasia', $html);
    }

    private function createSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                'detail_rapor',
                'rapor',
                'ppdb_keluar',
                'ppdb_beasiswa',
                'ppdb_prestasi',
                'ppdb_orang_tua',
                'ppdb_periodik',
                'ppdb_alamat',
                'dokumen_siswa',
                'pendaftaran_ppdb',
                'periode_update',
                'orang_tua_wali',
                'alamat_siswa',
                'agama',
                'status_siswa',
                'sekolah',
                'detail_absensi',
                'sesi_absensi',
                'jadwal_pelajaran',
                'catatan_wali_kelas',
                'keanggotaan_kelas',
                'penugasan_wali_kelas',
                'skala_predikat',
                'komponen_penilaian',
                'mata_pelajaran',
                'kelas',
                'semester',
                'tahun_ajaran',
                'pekerjaan',
                'siswa',
                'guru',
                'pengguna',
                'role',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        Schema::create('role', function (Blueprint $table) {
            $table->id('id_role');
            $table->string('nama_role');
            $table->string('kode_role')->nullable();
        });

        Schema::create('pengguna', function (Blueprint $table) {
            $table->id('id_pengguna');
            $table->unsignedBigInteger('id_role');
            $table->string('username');
            $table->string('password_hash');
            $table->boolean('is_aktif')->default(true);
            $table->timestamp('terakhir_login')->nullable();
        });

        Schema::create('guru', function (Blueprint $table) {
            $table->id('id_guru');
            $table->unsignedBigInteger('id_pengguna');
            $table->string('nama_guru')->nullable();
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->id('id_siswa');
            $table->unsignedBigInteger('id_pengguna')->nullable();
            $table->string('nama_lengkap');
            $table->string('nis')->nullable();
            $table->string('nisn')->nullable();
            $table->string('nik')->nullable();
            $table->string('jenis_kelamin', 2)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('email')->nullable();
            $table->string('no_telepon')->nullable();
            $table->unsignedBigInteger('id_agama')->nullable();
            $table->unsignedBigInteger('id_status_siswa')->nullable();
            $table->unsignedBigInteger('id_sekolah_asal')->nullable();
        });

        Schema::create('agama', function (Blueprint $table) {
            $table->id('id_agama');
            $table->string('nama_agama');
            $table->boolean('is_aktif')->default(true);
        });

        Schema::create('status_siswa', function (Blueprint $table) {
            $table->id('id_status_siswa');
            $table->string('nama_status');
        });

        Schema::create('sekolah', function (Blueprint $table) {
            $table->id('id_sekolah');
            $table->string('nama_sekolah');
        });

        Schema::create('alamat_siswa', function (Blueprint $table) {
            $table->id('id_alamat');
            $table->unsignedBigInteger('id_siswa');
            $table->text('alamat')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos')->nullable();
        });

        Schema::create('orang_tua_wali', function (Blueprint $table) {
            $table->id('id_orang_tua_wali');
            $table->unsignedBigInteger('id_siswa');
            $table->string('hubungan');
            $table->string('nama_lengkap')->nullable();
            $table->string('no_telepon')->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
        });

        Schema::create('pendaftaran_ppdb', function (Blueprint $table) {
            $table->id('id_pendaftaran');
            $table->string('nisn')->nullable();
            $table->string('nama_calon_siswa')->nullable();
            $table->string('no_kk')->nullable();
            $table->string('sekolah_asal')->nullable();
            $table->unsignedBigInteger('id_agama')->nullable();
            $table->timestamp('diajukan_pada')->nullable();
            $table->boolean('persetujuan_ortu')->default(false);
            $table->boolean('pernyataan')->default(false);
            $table->unsignedSmallInteger('langkah_terakhir')->default(0);
        });

        Schema::create('periode_update', function (Blueprint $table) {
            $table->id('id_periode_update');
            $table->unsignedBigInteger('id_tahun_ajaran')->nullable();
            $table->string('nama_periode');
            $table->string('status');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
        });

        Schema::create('ppdb_alamat', function (Blueprint $table) {
            $table->id('id_alamat_ppdb');
            $table->unsignedBigInteger('id_pendaftaran');
        });

        Schema::create('ppdb_periodik', function (Blueprint $table) {
            $table->id('id_periodik');
            $table->unsignedBigInteger('id_pendaftaran');
        });

        Schema::create('ppdb_orang_tua', function (Blueprint $table) {
            $table->id('id_orang_tua');
            $table->unsignedBigInteger('id_pendaftaran');
            $table->string('hubungan')->nullable();
            $table->unsignedBigInteger('id_pekerjaan')->nullable();
        });

        Schema::create('ppdb_prestasi', function (Blueprint $table) {
            $table->id('id_prestasi');
            $table->unsignedBigInteger('id_pendaftaran');
        });

        Schema::create('ppdb_beasiswa', function (Blueprint $table) {
            $table->id('id_beasiswa');
            $table->unsignedBigInteger('id_pendaftaran');
        });

        Schema::create('ppdb_keluar', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pendaftaran')->primary();
        });

        Schema::create('dokumen_siswa', function (Blueprint $table) {
            $table->id('id_dokumen');
            $table->unsignedBigInteger('id_siswa')->nullable();
            $table->unsignedBigInteger('id_pendaftaran')->nullable();
            $table->string('jenis_dokumen');
            $table->string('nama_file_asli')->nullable();
            $table->string('status_verifikasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('diunggah_pada')->nullable();
        });

        Schema::create('semester', function (Blueprint $table) {
            $table->id('id_semester');
            $table->string('nama_semester');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_aktif')->default(false);
        });

        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->id('id_tahun_ajaran');
            $table->string('nama_tahun_ajaran');
            $table->boolean('is_aktif')->default(false);
        });

        Schema::create('pekerjaan', function (Blueprint $table) {
            $table->id('id_pekerjaan');
            $table->string('nama_pekerjaan');
            $table->boolean('is_aktif')->default(true);
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id('id_kelas');
            $table->string('nama_kelas');
        });

        Schema::create('penugasan_wali_kelas', function (Blueprint $table) {
            $table->id('id_penugasan_wali_kelas');
            $table->unsignedBigInteger('id_kelas');
            $table->unsignedBigInteger('id_guru');
            $table->unsignedBigInteger('id_semester');
        });

        Schema::create('keanggotaan_kelas', function (Blueprint $table) {
            $table->id('id_keanggotaan');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_kelas');
            $table->unsignedBigInteger('id_semester');
            $table->string('status');
        });

        Schema::create('catatan_wali_kelas', function (Blueprint $table) {
            $table->id('id_catatan');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_guru');
            $table->text('isi_catatan');
            $table->timestamp('tgl_dibuat');
        });

        Schema::create('mata_pelajaran', function (Blueprint $table) {
            $table->id('id_mata_pelajaran');
            $table->string('nama_mapel');
            $table->boolean('is_aktif')->default(true);
        });

        Schema::create('jadwal_pelajaran', function (Blueprint $table) {
            $table->id('id_jadwal');
            $table->unsignedBigInteger('id_kelas');
            $table->unsignedBigInteger('id_semester');
            $table->unsignedBigInteger('id_mata_pelajaran');
            $table->unsignedBigInteger('id_guru')->nullable();
            $table->unsignedSmallInteger('hari')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('ruang')->nullable();
        });

        Schema::create('komponen_penilaian', function (Blueprint $table) {
            $table->id('id_komponen');
            $table->string('nama_komponen');
            $table->decimal('bobot_persen', 5, 2)->default(0);
        });

        Schema::create('rapor', function (Blueprint $table) {
            $table->id('id_rapor');
            $table->unsignedBigInteger('id_anggota');
            $table->unsignedBigInteger('id_semester');
            $table->string('status_rapor')->nullable();
            $table->decimal('rata_rata', 5, 2)->nullable();
            $table->integer('jumlah_sakit')->default(0);
            $table->integer('jumlah_izin')->default(0);
            $table->integer('jumlah_alpa')->default(0);
            $table->text('catatan_wali')->nullable();
        });

        Schema::create('detail_rapor', function (Blueprint $table) {
            $table->id('id_detail_rapor');
            $table->unsignedBigInteger('id_rapor');
            $table->unsignedBigInteger('id_mapel');
            $table->unsignedBigInteger('id_predikat')->nullable();
            $table->decimal('nilai_akhir', 5, 2);
        });

        Schema::create('skala_predikat', function (Blueprint $table) {
            $table->id('id_predikat');
            $table->string('huruf');
        });

        Schema::create('sesi_absensi', function (Blueprint $table) {
            $table->id('id_sesi');
            $table->unsignedBigInteger('id_jadwal');
            $table->unsignedBigInteger('id_kelas')->nullable();
            $table->unsignedBigInteger('id_semester')->nullable();
            $table->date('tanggal');
        });

        Schema::create('detail_absensi', function (Blueprint $table) {
            $table->id('id_detail_absensi');
            $table->unsignedBigInteger('id_sesi');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_status_hadir');
        });

        Schema::create('status_kehadiran', function (Blueprint $table) {
            $table->id('id_status_hadir');
            $table->string('kode');
            $table->string('nama_status');
        });
    }

    private function createRole(string $name): int
    {
        return (int) DB::table('role')->insertGetId([
            'nama_role' => $name,
            'kode_role' => $name,
        ], 'id_role');
    }

    private function createAccount(string $username, int $idRole): int
    {
        return (int) DB::table('pengguna')->insertGetId([
            'id_role' => $idRole,
            'username' => $username,
            'password_hash' => 'hash',
            'is_aktif' => true,
        ], 'id_pengguna');
    }

    private function createWaliKelas(): array
    {
        $idRole = $this->createRole('wali_kelas');
        $idPengguna = $this->createAccount('wali-test', $idRole);
        $idGuru = (int) DB::table('guru')->insertGetId([
            'id_pengguna' => $idPengguna,
            'nama_guru' => 'Wali Test',
        ], 'id_guru');

        return [
            $idGuru,
            $this->sessionFor($idPengguna, 'wali-test', 'wali_kelas'),
        ];
    }

    private function createSemester(): int
    {
        return (int) DB::table('semester')->insertGetId([
            'nama_semester' => 'Ganjil 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'is_aktif' => true,
        ], 'id_semester');
    }

    private function createClass(string $name): int
    {
        return (int) DB::table('kelas')->insertGetId(['nama_kelas' => $name], 'id_kelas');
    }

    private function assignClass(int $idGuru, int $idKelas, int $idSemester): void
    {
        DB::table('penugasan_wali_kelas')->insert([
            'id_guru' => $idGuru,
            'id_kelas' => $idKelas,
            'id_semester' => $idSemester,
        ]);
    }

    private function createStudent(string $name, ?int $idPengguna = null): int
    {
        return (int) DB::table('siswa')->insertGetId([
            'id_pengguna' => $idPengguna,
            'nama_lengkap' => $name,
            'nisn' => str_pad((string) DB::table('siswa')->count() + 1, 10, '0', STR_PAD_LEFT),
        ], 'id_siswa');
    }

    private function assignStudent(int $idSiswa, int $idKelas, int $idSemester): int
    {
        return (int) DB::table('keanggotaan_kelas')->insertGetId([
            'id_siswa' => $idSiswa,
            'id_kelas' => $idKelas,
            'id_semester' => $idSemester,
            'status' => 'aktif',
        ], 'id_keanggotaan');
    }

    private function sessionFor(int $idPengguna, string $username, string $role): array
    {
        return [
            'id_pengguna' => $idPengguna,
            'username' => $username,
            'role' => $role,
            'nama' => $username,
        ];
    }
}
