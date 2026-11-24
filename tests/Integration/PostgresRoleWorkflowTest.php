<?php

namespace Tests\Integration;

use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PostgresRoleWorkflowTest extends TestCase
{
    public function test_all_roles_can_use_their_menus_and_core_workflows(): void
    {
        if (getenv('RUN_LIVE_PGSQL_WORKFLOW_TEST') !== '1') {
            $this->markTestSkipped('Set RUN_LIVE_PGSQL_WORKFLOW_TEST=1 to opt in to the live PostgreSQL workflow test.');
        }

        $databaseName = getenv('LIVE_PGSQL_DATABASE');

        if (! $databaseName) {
            $this->markTestSkipped('Set LIVE_PGSQL_DATABASE to the intended database name.');
        }

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.database' => $databaseName,
        ]);
        DB::setDefaultConnection('pgsql');
        DB::purge('pgsql');

        $connection = DB::connection('pgsql');
        $identity = $connection->selectOne('SELECT current_user AS username, current_database() AS database_name');

        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('postgres', $identity->username);
        $this->assertSame($databaseName, $identity->database_name);

        $fixture = [];
        $connection->beginTransaction();

        try {
            $fixture = $this->createFixtures($connection);

            $this->assertAllMenuPages($fixture);
            $this->runPasswordChangeWorkflows($connection, $fixture);
            $this->runStudentAndTeacherWorkflows($connection, $fixture);
            $this->runTuAndAcademicWorkflows($connection, $fixture);
            $this->runNotificationWorkflows($connection, $fixture);
            $this->runPrincipalWorkflows($connection, $fixture);
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
        }

        if ($fixture !== []) {
            $this->assertFixturesWereRolledBack($connection, $fixture);
        }
    }

    private function createFixtures(Connection $connection): array
    {
        if ($connection->table('komponen_penilaian')->count() < 3
            || $connection->table('skala_predikat')->doesntExist()) {
            throw new RuntimeException('Live workflow test requires configured assessment components and predicate ranges.');
        }

        $semester = $connection->table('semester')->where('is_aktif', true)->first();
        $statusSiswaId = $connection->table('status_siswa')
            ->where('kode_status', 'aktif')
            ->value('id_status_siswa');
        $roleIds = $connection->table('role')->pluck('id_role', 'nama_role')->all();

        if (! $semester || ! $statusSiswaId) {
            throw new RuntimeException('Live workflow test requires an active semester and active student status.');
        }

        foreach (['siswa', 'wali_kelas', 'tu', 'kepsek'] as $role) {
            if (! isset($roleIds[$role])) {
                throw new RuntimeException("Live workflow test requires the {$role} role.");
            }
        }

        $kelas = $connection->table('kelas as k')
            ->leftJoin('penugasan_wali_kelas as pw', function ($join) use ($semester) {
                $join->on('pw.id_kelas', '=', 'k.id_kelas')
                    ->where('pw.id_semester', '=', $semester->id_semester);
            })
            ->leftJoin('jadwal_pelajaran as j', function ($join) use ($semester) {
                $join->on('j.id_kelas', '=', 'k.id_kelas')
                    ->where('j.id_semester', '=', $semester->id_semester);
            })
            ->whereNull('pw.id_penugasan_wali_kelas')
            ->whereNull('j.id_jadwal')
            ->where('k.is_aktif', true)
            ->where('k.id_tahun_ajaran', $semester->id_tahun_ajaran)
            ->orderBy('k.id_kelas')
            ->first(['k.id_kelas']);

        if (! $kelas) {
            throw new RuntimeException('Live workflow test could not find an unused active class.');
        }

        $latestYear = $connection->table('tahun_ajaran')
            ->orderByDesc('tanggal_selesai')
            ->first(['tanggal_selesai']);

        if (! $latestYear?->tanggal_selesai) {
            throw new RuntimeException('Live workflow test could not find the latest school-year end date.');
        }

        $suffix = strtoupper(Str::random(10));
        $password = 'TmpWorkflow123!';
        $nisn = (string) random_int(1000000000, 9999999999);
        $usernameByRole = [];
        $userIdByRole = [];
        $sessions = [];

        foreach ([
            'siswa' => 'Siswa Uji',
            'wali_kelas' => 'Guru Uji',
            'tu' => 'Petugas Uji',
            'kepsek' => 'Kepala Uji',
        ] as $role => $nama) {
            $username = 'it-'.$role.'-'.$suffix;
            $idPengguna = $connection->table('pengguna')->insertGetId([
                'id_role' => $roleIds[$role],
                'username' => $username,
                'password_hash' => Hash::make($password),
                'is_aktif' => true,
            ], 'id_pengguna');

            $usernameByRole[$role] = $username;
            $userIdByRole[$role] = (int) $idPengguna;
            $sessions[$role] = [
                'id_pengguna' => (int) $idPengguna,
                'username' => $username,
                'role' => $role,
                'nama' => $nama.' '.$suffix,
            ];
        }

        $idGuru = $connection->table('guru')->insertGetId([
            'id_pengguna' => $userIdByRole['wali_kelas'],
            'nip' => 'IT'.$suffix,
            'nama_guru' => 'Guru Uji '.$suffix,
            'no_telepon' => '08123456789',
            'is_aktif' => true,
        ], 'id_guru');

        foreach (['tu' => 'Tata Usaha', 'kepsek' => 'Kepala Sekolah'] as $role => $jabatan) {
            $connection->table('staf')->insert([
                'id_pengguna' => $userIdByRole[$role],
                'nama_staf' => $jabatan.' Uji '.$suffix,
                'jabatan' => $jabatan,
                'no_telepon' => '08123456789',
                'is_aktif' => true,
            ]);
        }

        $idSiswa = $connection->table('siswa')->insertGetId([
            'id_pengguna' => $userIdByRole['siswa'],
            'id_status_siswa' => $statusSiswaId,
            'nis' => 'IT'.$suffix,
            'nisn' => $nisn,
            'nama_lengkap' => 'Siswa Uji '.$suffix,
            'jenis_kelamin' => 'L',
            'email' => 'siswa-'.$suffix.'@example.test',
            'no_telepon' => '08123456789',
        ], 'id_siswa');

        $idKeanggotaan = $connection->table('keanggotaan_kelas')->insertGetId([
            'id_siswa' => $idSiswa,
            'id_kelas' => $kelas->id_kelas,
            'id_semester' => $semester->id_semester,
            'status' => 'aktif',
        ], 'id_keanggotaan');

        $connection->table('penugasan_wali_kelas')->insert([
            'id_kelas' => $kelas->id_kelas,
            'id_guru' => $idGuru,
            'id_semester' => $semester->id_semester,
        ]);

        $kodeMapel = 'IT'.$suffix;
        $namaMapel = 'Mata Pelajaran Uji '.$suffix;
        $idMapel = $connection->table('mata_pelajaran')->insertGetId([
            'kode_mapel' => $kodeMapel,
            'nama_mapel' => $namaMapel,
            'is_aktif' => true,
        ], 'id_mata_pelajaran');

        $notificationIds = [];

        foreach ($userIdByRole as $role => $idPengguna) {
            $notificationIds[$role] = [];
            foreach ([1, 2] as $n) {
                $notificationIds[$role][] = (int) $connection->table('notifikasi')->insertGetId([
                    'id_pengguna' => $idPengguna,
                    'judul' => 'Notifikasi uji '.$n,
                    'pesan' => 'Pesan sementara '.$suffix,
                    'dibaca' => false,
                    'dibuat_pada' => now(),
                ], 'id_notifikasi');
            }
        }

        $idPendaftaran = $connection->table('pendaftaran_ppdb')->insertGetId([
            'nomor_pendaftaran' => 'IT-PPDB-'.$suffix,
            'nama_calon_siswa' => 'Calon Uji '.$suffix,
            'status_pendaftaran' => 'menunggu',
            'diajukan_pada' => now(),
            'langkah_terakhir' => 1,
        ], 'id_pendaftaran');

        $sourceStart = Carbon::parse($latestYear->tanggal_selesai)->addDay();
        $sourceEnd = $sourceStart->copy()->addYear()->subDay();
        $targetStart = $sourceEnd->copy()->addDay();
        $targetEnd = $targetStart->copy()->addYear()->subDay();
        $sourceYearName = $sourceStart->format('Y').'/'.$sourceEnd->format('Y');
        $targetYearName = $targetStart->format('Y').'/'.$targetEnd->format('Y');

        $sourceYearId = $connection->table('tahun_ajaran')->insertGetId([
            'nama_tahun_ajaran' => $sourceYearName,
            'tanggal_mulai' => $sourceStart->toDateString(),
            'tanggal_selesai' => $sourceEnd->toDateString(),
            'is_aktif' => false,
        ], 'id_tahun_ajaran');
        $targetYearId = $connection->table('tahun_ajaran')->insertGetId([
            'nama_tahun_ajaran' => $targetYearName,
            'tanggal_mulai' => $targetStart->toDateString(),
            'tanggal_selesai' => $targetEnd->toDateString(),
            'is_aktif' => false,
        ], 'id_tahun_ajaran');

        $sourceSemesterId = $connection->table('semester')->insertGetId([
            'id_tahun_ajaran' => $sourceYearId,
            'nama_semester' => 'Ganjil',
            'tanggal_mulai' => $sourceStart->toDateString(),
            'tanggal_selesai' => $sourceStart->copy()->addMonths(5)->toDateString(),
            'is_aktif' => false,
        ], 'id_semester');
        $targetSemesterId = $connection->table('semester')->insertGetId([
            'id_tahun_ajaran' => $targetYearId,
            'nama_semester' => 'Ganjil',
            'tanggal_mulai' => $targetStart->toDateString(),
            'tanggal_selesai' => $targetStart->copy()->addMonths(5)->toDateString(),
            'is_aktif' => false,
        ], 'id_semester');

        $tingkatX = $connection->table('tingkat')->where('urutan', 10)->value('id_tingkat');
        $tingkatXI = $connection->table('tingkat')->where('urutan', 11)->value('id_tingkat');
        $jurusanMia = $connection->table('jurusan')->where('kode_jurusan', 'MIA')->value('id_jurusan');

        if (! $tingkatX || ! $tingkatXI || ! $jurusanMia) {
            throw new RuntimeException('Live workflow test requires X/XI levels and the MIA program.');
        }

        $rombel = random_int(50, 99);
        $namaKelasAsal = 'X MIA '.$rombel;
        $namaKelasTujuan = 'XI MIA '.$rombel;

        while (
            $connection->table('kelas')->where('id_tahun_ajaran', $sourceYearId)->where('nama_kelas', $namaKelasAsal)->exists()
            || $connection->table('kelas')->where('id_tahun_ajaran', $targetYearId)->where('nama_kelas', $namaKelasTujuan)->exists()
        ) {
            $rombel = random_int(50, 99);
            $namaKelasAsal = 'X MIA '.$rombel;
            $namaKelasTujuan = 'XI MIA '.$rombel;
        }

        $idKelasAsal = $connection->table('kelas')->insertGetId([
            'nama_kelas' => $namaKelasAsal,
            'id_tahun_ajaran' => $sourceYearId,
            'id_tingkat' => $tingkatX,
            'id_jurusan' => $jurusanMia,
            'kapasitas' => 32,
            'is_aktif' => true,
        ], 'id_kelas');
        $idKelasTujuan = $connection->table('kelas')->insertGetId([
            'nama_kelas' => $namaKelasTujuan,
            'id_tahun_ajaran' => $targetYearId,
            'id_tingkat' => $tingkatXI,
            'id_jurusan' => $jurusanMia,
            'kapasitas' => 32,
            'is_aktif' => true,
        ], 'id_kelas');
        $idAnggotaAsal = $connection->table('keanggotaan_kelas')->insertGetId([
            'id_siswa' => $idSiswa,
            'id_kelas' => $idKelasAsal,
            'id_semester' => $sourceSemesterId,
            'tanggal_mulai' => $sourceStart->toDateString(),
            'status' => 'aktif',
        ], 'id_keanggotaan');

        $periodStatuses = $connection->table('periode_update')
            ->orderBy('id_periode_update')
            ->get(['id_periode_update', 'status'])
            ->mapWithKeys(fn ($row) => [$row->id_periode_update => $row->status])
            ->all();

        return [
            'suffix' => $suffix,
            'password' => $password,
            'nisn' => $nisn,
            'username_by_role' => $usernameByRole,
            'user_id_by_role' => $userIdByRole,
            'sessions' => $sessions,
            'id_guru' => (int) $idGuru,
            'id_siswa' => (int) $idSiswa,
            'id_keanggotaan' => (int) $idKeanggotaan,
            'id_kelas' => (int) $kelas->id_kelas,
            'id_semester' => (int) $semester->id_semester,
            'id_tahun_ajaran' => (int) $semester->id_tahun_ajaran,
            'id_mapel' => (int) $idMapel,
            'kode_mapel' => $kodeMapel,
            'nama_mapel' => $namaMapel,
            'id_pendaftaran' => (int) $idPendaftaran,
            'source_year_id' => (int) $sourceYearId,
            'target_year_id' => (int) $targetYearId,
            'source_semester_id' => (int) $sourceSemesterId,
            'target_semester_id' => (int) $targetSemesterId,
            'target_start_date' => $targetStart->toDateString(),
            'source_class_id' => (int) $idKelasAsal,
            'target_class_id' => (int) $idKelasTujuan,
            'source_membership_id' => (int) $idAnggotaAsal,
            'notification_ids' => $notificationIds,
            'period_statuses' => $periodStatuses,
        ];
    }

    private function assertAllMenuPages(array $fixture): void
    {
        $menuRoutes = [
            'siswa' => [
                'siswa.dashboard', 'siswa.jadwal', 'siswa.absensi', 'siswa.rapor',
                'siswa.profil', 'siswa.konfirmasi', 'siswa.pengajuan',
            ],
            'wali_kelas' => [
                'guru.kelas', 'guru.kelengkapan', 'guru.pengajuan', 'guru.catatan',
                'guru.absensi', 'guru.rapor',
            ],
            'tu' => [
                'tu.dashboard', 'tu.antrean', 'tu.siswa', 'tu.periode', 'tu.reset',
                'tu.mutasi', 'admin.referensi', 'tu.kelas', 'tu.kenaikan',
                'akademik.konfigurasi', 'akademik.jadwal',
            ],
            'kepsek' => [
                'kepsek.dashboard', 'kepsek.mutasi', 'admin.pengguna', 'admin.staf',
                'admin.log', 'admin.audit', 'admin.pengaturan',
            ],
        ];

        foreach ($menuRoutes as $role => $routeNames) {
            foreach ($routeNames as $routeName) {
                $response = $this->callAs(
                    $fixture['sessions'][$role],
                    'GET',
                    $routeName,
                    $this->routeParameters($routeName, $fixture)
                );

                $this->assertSame(
                    200,
                    $response->getStatusCode(),
                    "{$role} menu route {$routeName} returned {$response->getStatusCode()}."
                );
            }

            $this->callAs($fixture['sessions'][$role], 'GET', 'notifikasi')
                ->assertOk()
                ->assertSee('Notifikasi (2 belum dibaca)', false);
            $this->callAs($fixture['sessions'][$role], 'GET', 'akun.sandi')
                ->assertOk();
        }

        $this->callAs($fixture['sessions']['tu'], 'GET', 'tu.siswa.detail', [$fixture['id_siswa']])
            ->assertOk();
        $this->callAs($fixture['sessions']['tu'], 'GET', 'tu.verifikasi', [$fixture['id_pendaftaran']])
            ->assertOk();
        $this->callAs($fixture['sessions']['tu'], 'GET', 'tu.kelas.detail', [$fixture['id_kelas']])
            ->assertOk();
        $this->callAs($fixture['sessions']['kepsek'], 'GET', 'admin.pengguna.edit', [$fixture['user_id_by_role']['wali_kelas']])
            ->assertOk();
        $this->callAs($fixture['sessions']['tu'], 'GET', 'akademik.konfigurasi', ['mapel'])
            ->assertOk();
        $this->callAs($fixture['sessions']['tu'], 'GET', 'akademik.jadwal', [
            'semester' => $fixture['id_semester'],
            'kelas' => $fixture['id_kelas'],
        ])->assertOk();
        $this->callAs($fixture['sessions']['wali_kelas'], 'GET', 'guru.rapor', [
            'kelas' => $fixture['id_kelas'],
            'mapel' => $fixture['id_mapel'],
        ])->assertOk();
    }

    private function runStudentAndTeacherWorkflows(Connection $connection, array $fixture): void
    {
        $tu = $fixture['sessions']['tu'];
        $student = $fixture['sessions']['siswa'];
        $teacher = $fixture['sessions']['wali_kelas'];
        $today = now('Asia/Jakarta')->toDateString();

        $this->callAs($tu, 'POST', 'tu.periode.simpan', [], [
            'id_tahun_ajaran' => $fixture['id_tahun_ajaran'],
            'tgl_buka' => $today,
            'tgl_tutup' => now('Asia/Jakarta')->addDays(3)->toDateString(),
        ])
            ->assertRedirect(route('tu.periode'))
            ->assertSessionHas('success', 'Periode update berhasil dibuka.');

        $periodeId = (int) $connection->table('periode_update')
            ->where('id_tahun_ajaran', $fixture['id_tahun_ajaran'])
            ->where('dibuat_oleh', $fixture['user_id_by_role']['tu'])
            ->orderByDesc('id_periode_update')
            ->value('id_periode_update');
        $this->assertGreaterThan(0, $periodeId);

        $this->callAs($tu, 'POST', 'tu.pengingat')
            ->assertRedirect();

        $this->callAs($student, 'POST', 'siswa.konfirmasi.simpan', [], [
            'id_periode' => $periodeId,
            'konfirmasi' => '1',
        ])
            ->assertRedirect(route('siswa.konfirmasi'))
            ->assertSessionHas('success', 'Konfirmasi data berhasil disimpan.');

        $emailBaru = 'siswa-'.$fixture['suffix'].'-baru@example.test';
        $idFieldEmail = (int) $connection->table('ref_field_data')
            ->where('nama_tabel', 'siswa')
            ->where('nama_kolom', 'email')
            ->value('id_field');

        $this->callAs($student, 'POST', 'siswa.pengajuan.simpan', [], [
            'id_field' => $idFieldEmail,
            'nilai_baru' => $emailBaru,
        ])
            ->assertRedirect(route('siswa.pengajuan'))
            ->assertSessionHas('success');

        $idPengajuan = (int) $connection->table('pengajuan_perubahan')
            ->where('id_siswa', $fixture['id_siswa'])
            ->where('id_periode_update', $periodeId)
            ->value('id_pengajuan');
        $this->assertGreaterThan(0, $idPengajuan);

        $this->callAs($teacher, 'POST', 'guru.pengajuan.simpan', [], [
            'id_pengajuan' => $idPengajuan,
            'keputusan' => 'setujui',
            'catatan' => 'Validasi uji sementara.',
        ])
            ->assertRedirect(route('guru.pengajuan'))
            ->assertSessionHas('success', 'Pengajuan disetujui dan data siswa diperbarui.');
        $this->assertSame(
            $emailBaru,
            $connection->table('siswa')->where('id_siswa', $fixture['id_siswa'])->value('email')
        );

        $this->callAs($teacher, 'POST', 'guru.catatan.simpan', [], [
            'id_siswa' => $fixture['id_siswa'],
            'isi_catatan' => 'Catatan wali uji sementara.',
        ])
            ->assertRedirect(route('guru.catatan'))
            ->assertSessionHas('success', 'Catatan wali kelas berhasil disimpan.');
        $this->assertSame(
            1,
            $connection->table('catatan_wali_kelas')->where('id_siswa', $fixture['id_siswa'])->count()
        );

        $this->callAs($teacher, 'POST', 'guru.absensi.simpan', [], [
            'id_kelas' => $fixture['id_kelas'],
            'tanggal' => $today,
            'status' => [$fixture['id_siswa'] => 'H'],
            'keterangan' => [$fixture['id_siswa'] => 'Presensi uji sementara.'],
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Presensi kelas berhasil disimpan.');

        $reportUrl = route('guru.rapor', [
            'kelas' => $fixture['id_kelas'],
            'mapel' => $fixture['id_mapel'],
        ]);

        $this->callAs($teacher, 'POST', 'guru.rapor.simpan', [], [
            'id_kelas' => $fixture['id_kelas'],
            'id_mapel' => $fixture['id_mapel'],
            'nilai_mentah' => [
                $fixture['id_siswa'] => [
                    'tugas' => 80,
                    'keterampilan' => 80,
                    'ujian' => 80,
                ],
            ],
            'catatan_siswa' => [$fixture['id_siswa'] => 'Rapor uji sementara.'],
        ])
            ->assertRedirect($reportUrl)
            ->assertSessionHas('success', 'Nilai rapor berhasil disimpan untuk seluruh siswa.');

        $report = $connection->table('rapor')
            ->where('id_anggota', $fixture['id_keanggotaan'])
            ->where('id_semester', $fixture['id_semester'])
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(80.0, (float) $report->rata_rata);
        $this->assertSame(
            3,
            $connection->table('nilai_siswa')
                ->where('id_anggota', $fixture['id_keanggotaan'])
                ->where('id_mapel', $fixture['id_mapel'])
                ->where('id_semester', $fixture['id_semester'])
                ->count()
        );

        $this->callAs($teacher, 'POST', 'guru.rapor.finalisasi', [], [
            'id_rapor' => $report->id_rapor,
        ])
            ->assertRedirect(route('guru.rapor'))
            ->assertSessionHas('success', 'Rapor siswa berhasil dikunci.');

        $this->assertSame(
            'final',
            $connection->table('rapor')->where('id_rapor', $report->id_rapor)->value('status_rapor')
        );
        $this->callAs($student, 'GET', 'siswa.rapor', [$report->id_rapor])
            ->assertOk()
            ->assertSee($fixture['nama_mapel']);

        $this->callAs($teacher, 'POST', 'guru.rapor.simpan', [], [
            'id_kelas' => $fixture['id_kelas'],
            'id_mapel' => $fixture['id_mapel'],
            'nilai_mentah' => [
                $fixture['id_siswa'] => [
                    'tugas' => 90,
                    'keterampilan' => 90,
                    'ujian' => 90,
                ],
            ],
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('nilai_mentah');

        $this->assertEquals(
            80.0,
            (float) $connection->table('nilai_siswa')
                ->where('id_anggota', $fixture['id_keanggotaan'])
                ->where('id_mapel', $fixture['id_mapel'])
                ->where('id_semester', $fixture['id_semester'])
                ->orderBy('id_komponen')
                ->value('nilai')
        );
    }

    private function runTuAndAcademicWorkflows(Connection $connection, array $fixture): void
    {
        $tu = $fixture['sessions']['tu'];
        $today = now('Asia/Jakarta')->toDateString();

        $this->callAs($tu, 'GET', 'tu.verifikasi', [$fixture['id_pendaftaran']])
            ->assertOk();

        $this->callAs($tu, 'POST', 'tu.verifikasi.simpan', [], [
            'id_pendaftaran' => $fixture['id_pendaftaran'],
            'hasil' => 'kembalikan',
            'id_alasan' => $connection->table('ref_alasan_pengembalian')->orderBy('id_alasan')->value('nama_alasan'),
            'catatan' => 'Berkas uji perlu diperbaiki.',
        ])
            ->assertRedirect(route('tu.verifikasi', $fixture['id_pendaftaran']))
            ->assertSessionHas('success', 'Berkas dikembalikan untuk diperbaiki.');
        $this->assertSame(
            'perlu_perbaikan',
            $connection->table('pendaftaran_ppdb')
                ->where('id_pendaftaran', $fixture['id_pendaftaran'])
                ->value('status_pendaftaran')
        );

        $updatedPassword = $this->callAs($tu, 'POST', 'tu.reset.proses', [$fixture['user_id_by_role']['siswa']]);
        $updatedPassword->assertOk();
        $this->assertNotNull($updatedPassword->headers->get('Cache-Control'));
        $resetSandi = $updatedPassword->viewData('hasil')['sandi'] ?? '';
        $this->assertNotSame('', $resetSandi);
        $this->assertTrue(Hash::check(
            $resetSandi,
            $connection->table('pengguna')
                ->where('id_pengguna', $fixture['user_id_by_role']['siswa'])
                ->value('password_hash')
        ));

        $this->callAs($tu, 'PUT', 'tu.siswa.update', [$fixture['id_siswa']], [
            'nama_lengkap' => 'Siswa Diperbarui '.$fixture['suffix'],
            'nis' => 'IT'.$fixture['suffix'],
            'nisn' => $fixture['nisn'],
            'jenis_kelamin' => 'L',
            'email' => 'siswa-diperbarui-'.$fixture['suffix'].'@example.test',
            'no_telepon' => '08123456789',
        ])
            ->assertRedirect(route('tu.siswa'))
            ->assertSessionHas('success', 'Data siswa berhasil diperbarui.');

        $kodeReferensi = 'Agama Uji '.$fixture['suffix'];
        $this->callAs($tu, 'POST', 'admin.referensi.simpan', ['tab' => 'agama'], [
            'nama_agama' => $kodeReferensi,
            'is_aktif' => '1',
        ])
            ->assertRedirect(route('admin.referensi', 'agama'));
        $idAgama = (int) $connection->table('agama')->where('nama_agama', $kodeReferensi)->value('id_agama');
        $this->assertGreaterThan(0, $idAgama);

        $namaReferensiDiubah = $kodeReferensi.' Diubah';
        $this->callAs($tu, 'PUT', 'admin.referensi.ubah', [
            'tab' => 'agama',
            'id' => $idAgama,
        ], [
            'nama_agama' => $namaReferensiDiubah,
            'is_aktif' => '1',
        ])->assertRedirect(route('admin.referensi', 'agama'));
        $this->callAs($tu, 'DELETE', 'admin.referensi.hapus', [
            'tab' => 'agama',
            'id' => $idAgama,
        ])->assertRedirect(route('admin.referensi', 'agama'));
        $this->assertSame(0, $connection->table('agama')->where('id_agama', $idAgama)->count());

        $kodeMapelBaru = 'CFG-'.$fixture['suffix'];
        $namaMapelBaru = 'Mapel Konfigurasi Uji '.$fixture['suffix'];
        $this->callAs($tu, 'POST', 'akademik.konfigurasi.simpan', ['tab' => 'mapel'], [
            'kode_mapel' => $kodeMapelBaru,
            'nama_mapel' => $namaMapelBaru,
            'is_aktif' => '1',
        ])->assertRedirect(route('akademik.konfigurasi', 'mapel'));
        $idMapelBaru = (int) $connection->table('mata_pelajaran')->where('kode_mapel', $kodeMapelBaru)->value('id_mata_pelajaran');
        $this->assertGreaterThan(0, $idMapelBaru);

        $this->callAs($tu, 'PUT', 'akademik.konfigurasi.ubah', [
            'tab' => 'mapel',
            'id' => $idMapelBaru,
        ], [
            'kode_mapel' => $kodeMapelBaru,
            'nama_mapel' => $namaMapelBaru.' Diperbarui',
            'is_aktif' => '1',
        ])->assertRedirect(route('akademik.konfigurasi', 'mapel'));
        $this->callAs($tu, 'DELETE', 'akademik.konfigurasi.hapus', [
            'tab' => 'mapel',
            'id' => $idMapelBaru,
        ])->assertRedirect(route('akademik.konfigurasi', 'mapel'));

        $this->callAs($tu, 'POST', 'akademik.jadwal.simpan', [], [
            'id_semester' => $fixture['id_semester'],
            'id_kelas' => $fixture['id_kelas'],
            'jadwal' => [[
                'hari' => 1,
                'jam_mulai' => '07:00',
                'jam_selesai' => '07:40',
                'id_mata_pelajaran' => $fixture['id_mapel'],
                'id_guru' => $fixture['id_guru'],
                'nama_guru_mapel' => 'Guru Uji '.$fixture['suffix'],
                'ruang' => 'Ruang Uji',
            ]],
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Jadwal pelajaran berhasil disimpan.');
        $this->assertSame(
            1,
            $connection->table('jadwal_pelajaran')
                ->where('id_kelas', $fixture['id_kelas'])
                ->where('id_semester', $fixture['id_semester'])
                ->count()
        );

        $otherClass = $connection->table('kelas as k')
            ->leftJoin('penugasan_wali_kelas as pw', function ($join) use ($fixture) {
                $join->on('pw.id_kelas', '=', 'k.id_kelas')
                    ->where('pw.id_semester', '=', $fixture['id_semester']);
            })
            ->whereNull('pw.id_penugasan_wali_kelas')
            ->where('k.id_tahun_ajaran', $fixture['id_tahun_ajaran'])
            ->where('k.is_aktif', true)
            ->where('k.id_kelas', '<>', $fixture['id_kelas'])
            ->orderBy('k.id_kelas')
            ->first(['k.id_kelas']);
        $this->assertNotNull($otherClass);

        $this->callAs($tu, 'POST', 'tu.kelas.wali', [], [
            'id_kelas' => $otherClass->id_kelas,
            'id_guru' => $fixture['id_guru'],
            'id_semester' => $fixture['id_semester'],
        ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $idPenugasan = (int) $connection->table('penugasan_wali_kelas')
            ->where('id_kelas', $otherClass->id_kelas)
            ->where('id_semester', $fixture['id_semester'])
            ->value('id_penugasan_wali_kelas');
        $this->assertGreaterThan(0, $idPenugasan);

        $this->callAs($tu, 'DELETE', 'tu.kelas.wali.cabut', ['id' => $idPenugasan])
            ->assertRedirect();
        $this->assertSame(
            0,
            $connection->table('penugasan_wali_kelas')
                ->where('id_penugasan_wali_kelas', $idPenugasan)
                ->count()
        );

        $this->callAs($tu, 'POST', 'tu.kenaikan.simpan', [], [
            'id_tahun_ajaran' => $fixture['source_year_id'],
            'id_siswa' => [$fixture['id_siswa']],
        ])->assertRedirect();
        $this->assertSame(
            'naik_kelas',
            $connection->table('keanggotaan_kelas')
                ->where('id_keanggotaan', $fixture['source_membership_id'])
                ->value('status')
        );
        $this->assertSame(
            1,
            $connection->table('keanggotaan_kelas')
                ->where('id_siswa', $fixture['id_siswa'])
                ->where('id_kelas', $fixture['target_class_id'])
                ->where('id_semester', $fixture['target_semester_id'])
                ->where('status', 'aktif')
                ->count()
        );

        $periodeId = (int) $connection->table('periode_update')
            ->where('dibuat_oleh', $fixture['user_id_by_role']['tu'])
            ->orderByDesc('id_periode_update')
            ->value('id_periode_update');
        $this->callAs($tu, 'POST', 'tu.periode.tutup', ['id' => $periodeId])
            ->assertRedirect(route('tu.periode'))
            ->assertSessionHas('success', 'Periode update berhasil ditutup.');
        $this->assertSame(
            'ditutup',
            $connection->table('periode_update')->where('id_periode_update', $periodeId)->value('status')
        );
    }

    private function runPrincipalWorkflows(Connection $connection, array $fixture): void
    {
        $tu = $fixture['sessions']['tu'];
        $principal = $fixture['sessions']['kepsek'];

        $this->callAs($tu, 'POST', 'tu.mutasi.simpan', [], [
            'jenis' => 'pindah_keluar',
            'id_siswa' => $fixture['id_siswa'],
            'sekolah_tujuan' => 'Sekolah Mutasi Uji '.$fixture['suffix'],
            'tanggal_efektif' => $fixture['target_start_date'],
            'alasan' => 'Uji alur mutasi sementara.',
        ])
            ->assertRedirect(route('tu.mutasi'))
            ->assertSessionHas('success', 'Pengajuan mutasi berhasil dikirim ke Kepala Sekolah.');

        $idMutasi = (int) $connection->table('mutasi_siswa')
            ->where('id_pengaju', $fixture['user_id_by_role']['tu'])
            ->where('id_siswa', $fixture['id_siswa'])
            ->where('status', 'menunggu')
            ->value('id_mutasi');
        $this->assertGreaterThan(0, $idMutasi);

        $this->callAs($principal, 'POST', 'kepsek.mutasi.putuskan', ['id' => $idMutasi], [
            'keputusan' => 'setujui',
            'catatan_keputusan' => null,
        ])
            ->assertRedirect(route('kepsek.mutasi'))
            ->assertSessionHas('success', 'Mutasi disetujui.');
        $this->assertSame(
            'disetujui',
            $connection->table('mutasi_siswa')->where('id_mutasi', $idMutasi)->value('status')
        );

        $this->runMutationTypeMatrix($connection, $fixture, $principal);

        $this->callAs($principal, 'PUT', 'admin.pengaturan.simpan', [], [
            'nama_sekolah' => 'Sekolah Uji '.$fixture['suffix'],
            'max_login_gagal' => 6,
            'durasi_kunci' => 20,
            'ukuran_berkas_mb' => 2,
            'format_berkas' => 'pdf,jpg,png',
            'tahun_ajaran_aktif' => $connection->table('tahun_ajaran')
                ->where('id_tahun_ajaran', $fixture['id_tahun_ajaran'])
                ->value('nama_tahun_ajaran'),
        ])
            ->assertRedirect(route('admin.pengaturan'))
            ->assertSessionHas('success', 'Pengaturan disimpan.');

        $this->callAs($principal, 'PATCH', 'admin.pengguna.status', [
            'id' => $fixture['user_id_by_role']['siswa'],
        ])
            ->assertRedirect(route('admin.pengguna'));
        $this->assertFalse((bool) $connection->table('pengguna')
            ->where('id_pengguna', $fixture['user_id_by_role']['siswa'])
            ->value('is_aktif'));

        $this->callAs($principal, 'PATCH', 'admin.pengguna.status', [
            'id' => $fixture['user_id_by_role']['siswa'],
        ])->assertRedirect(route('admin.pengguna'));
        $this->assertTrue((bool) $connection->table('pengguna')
            ->where('id_pengguna', $fixture['user_id_by_role']['siswa'])
            ->value('is_aktif'));

        $roleWaliId = (int) $connection->table('role')->where('nama_role', 'wali_kelas')->value('id_role');
        $usernameTuBaru = $fixture['username_by_role']['tu'].'-ubah';
        $this->callAs($principal, 'PUT', 'admin.pengguna.update', [
            'id' => $fixture['user_id_by_role']['tu'],
        ], [
            'nama' => 'Petugas Diubah '.$fixture['suffix'],
            'username' => $usernameTuBaru,
            'id_role' => $roleWaliId,
            'nip' => 'ITW'.$fixture['suffix'],
            'no_hp' => '08123456789',
        ])
            ->assertRedirect(route('admin.pengguna'))
            ->assertSessionHas('success', 'Pengguna berhasil diperbarui.');
        $this->assertSame(
            'wali_kelas',
            $connection->table('role as r')
                ->join('pengguna as p', 'p.id_role', '=', 'r.id_role')
                ->where('p.id_pengguna', $fixture['user_id_by_role']['tu'])
                ->value('r.nama_role')
        );
    }

    private function runNotificationWorkflows(Connection $connection, array $fixture): void
    {
        foreach ($fixture['sessions'] as $role => $session) {
            $idNotifikasi = $fixture['notification_ids'][$role][0];
            $this->callAs($session, 'POST', 'notifikasi.baca', ['id' => $idNotifikasi])
                ->assertRedirect()
                ->assertSessionHas('success', 'Notifikasi ditandai sudah dibaca.');
            $this->callAs($session, 'POST', 'notifikasi.baca-semua')
                ->assertRedirect()
                ->assertSessionHas('success');
            $this->assertSame(
                0,
                $connection->table('notifikasi')
                    ->where('id_pengguna', $session['id_pengguna'])
                    ->where('dibaca', false)
                    ->count()
            );
        }
    }

    private function runPasswordChangeWorkflows(Connection $connection, array $fixture): void
    {
        $passwordBaru = 'NewTmpWorkflow456!';

        foreach ($fixture['sessions'] as $role => $session) {
            $this->callAs($session, 'POST', 'akun.sandi.update', [], [
                'sandi_lama' => $fixture['password'],
                'sandi_baru' => $passwordBaru,
                'sandi_ulang' => $passwordBaru,
            ])
                ->assertRedirect(route('akun.sandi'))
                ->assertSessionHas('success', 'Kata sandi berhasil diubah.');

            $this->assertTrue(Hash::check(
                $passwordBaru,
                $connection->table('pengguna')
                    ->where('id_pengguna', $fixture['user_id_by_role'][$role])
                    ->value('password_hash')
            ));
        }
    }

    private function routeParameters(string $routeName, array $fixture): array
    {
        return match ($routeName) {
            'guru.kelas', 'guru.kelengkapan', 'guru.pengajuan', 'guru.absensi' => [
                'kelas' => $fixture['id_kelas'],
            ],
            'guru.rapor' => [
                'kelas' => $fixture['id_kelas'],
                'mapel' => $fixture['id_mapel'],
            ],
            'tu.kelas' => ['semester' => $fixture['id_semester']],
            'tu.kenaikan' => ['tahun_ajaran' => $fixture['source_year_id']],
            'akademik.konfigurasi' => ['tab' => 'mapel'],
            'akademik.jadwal' => [
                'semester' => $fixture['id_semester'],
                'kelas' => $fixture['id_kelas'],
            ],
            default => [],
        };
    }

    private function runMutationTypeMatrix(
        Connection $connection,
        array $fixture,
        array $principal
    ): void {
        $cases = [
            'pindah_keluar' => ['mutasi', 'pindah'],
            'mengundurkan_diri' => ['mengundurkan_diri', 'keluar'],
            'dikeluarkan' => ['dikeluarkan', 'keluar'],
            'putus_sekolah' => ['putus_sekolah', 'keluar'],
            'meninggal_dunia' => ['meninggal_dunia', 'keluar'],
        ];
        $semesterStart = $connection->table('semester')
            ->where('id_semester', $fixture['id_semester'])
            ->value('tanggal_mulai');
        $statusAktif = (int) $connection->table('status_siswa')
            ->where('kode_status', 'aktif')
            ->value('id_status_siswa');
        $nomorKasus = 0;

        foreach ($cases as $jenis => [$kodeStatus, $statusAnggota]) {
            foreach (['setujui', 'tolak'] as $keputusan) {
                $nomorKasus++;
                $nisn = (string) random_int(1000000000, 9999999999);
                $idSiswa = (int) $connection->table('siswa')->insertGetId([
                    'id_status_siswa' => $statusAktif,
                    'nis' => 'IT-MUT-'.$fixture['suffix'].'-'.$nomorKasus,
                    'nisn' => $nisn,
                    'nama_lengkap' => 'Siswa Uji Mutasi '.$fixture['suffix'].' '.$nomorKasus,
                    'jenis_kelamin' => 'L',
                ], 'id_siswa');
                $connection->table('keanggotaan_kelas')->insert([
                    'id_siswa' => $idSiswa,
                    'id_kelas' => $fixture['id_kelas'],
                    'id_semester' => $fixture['id_semester'],
                    'tanggal_mulai' => $semesterStart,
                    'status' => 'aktif',
                ]);
                $idMutasi = (int) $connection->table('mutasi_siswa')->insertGetId([
                    'id_siswa' => $idSiswa,
                    'id_pengaju' => $fixture['user_id_by_role']['tu'],
                    'jenis' => $jenis,
                    'alasan' => 'Uji matriks mutasi '.$fixture['suffix'].' '.$nomorKasus,
                    'status' => 'menunggu',
                    'tgl_pengajuan' => now(),
                    'tanggal_efektif' => $semesterStart,
                ], 'id_mutasi');

                $catatan = $keputusan === 'tolak' ? 'Ditolak dalam pengujian.' : null;
                $this->callAs($principal, 'POST', 'kepsek.mutasi.putuskan', ['id' => $idMutasi], [
                    'keputusan' => $keputusan,
                    'catatan_keputusan' => $catatan,
                ])
                    ->assertRedirect(route('kepsek.mutasi'))
                    ->assertSessionHas('success', $keputusan === 'setujui' ? 'Mutasi disetujui.' : 'Mutasi ditolak.');

                $idStatusAkhir = $keputusan === 'setujui'
                    ? (int) $connection->table('status_siswa')->where('kode_status', $kodeStatus)->value('id_status_siswa')
                    : $statusAktif;
                $this->assertDatabaseHas('siswa', [
                    'id_siswa' => $idSiswa,
                    'id_status_siswa' => $idStatusAkhir,
                ]);
                $this->assertDatabaseHas('keanggotaan_kelas', [
                    'id_siswa' => $idSiswa,
                    'status' => $keputusan === 'setujui' ? $statusAnggota : 'aktif',
                    'tanggal_selesai' => $keputusan === 'setujui' ? $semesterStart : null,
                ]);
                $this->assertDatabaseHas('mutasi_siswa', [
                    'id_mutasi' => $idMutasi,
                    'status' => $keputusan === 'setujui' ? 'disetujui' : 'ditolak',
                    'id_penyetuju' => $fixture['user_id_by_role']['kepsek'],
                    'catatan_keputusan' => $catatan,
                ]);
            }
        }
    }

    private function callAs(
        array $session,
        string $method,
        string $routeName,
        array $parameters = [],
        array $data = []
    ) {
        return $this->withSession($session)->call($method, route($routeName, $parameters), $data);
    }

    private function assertFixturesWereRolledBack(Connection $connection, array $fixture): void
    {
        foreach ($fixture['user_id_by_role'] as $idPengguna) {
            $this->assertSame(0, $connection->table('pengguna')->where('id_pengguna', $idPengguna)->count());
        }

        $this->assertSame(0, $connection->table('siswa')->where('nisn', $fixture['nisn'])->count());
        $this->assertSame(0, $connection->table('guru')->where('id_guru', $fixture['id_guru'])->count());
        $this->assertSame(1, $connection->table('kelas')->where('id_kelas', $fixture['id_kelas'])->count());
        $this->assertSame(0, $connection->table('kelas')->whereIn('id_kelas', [
            $fixture['source_class_id'],
            $fixture['target_class_id'],
        ])->count());
        $this->assertSame(1, $connection->table('semester')->where('id_semester', $fixture['id_semester'])->count());
        $this->assertSame(0, $connection->table('semester')->whereIn('id_semester', [
            $fixture['source_semester_id'],
            $fixture['target_semester_id'],
        ])->count());
        $this->assertSame(0, $connection->table('keanggotaan_kelas')->whereIn('id_keanggotaan', [
            $fixture['id_keanggotaan'],
            $fixture['source_membership_id'],
        ])->count());
        $this->assertSame(0, $connection->table('keanggotaan_kelas')
            ->where('id_siswa', $fixture['id_siswa'])
            ->where('id_kelas', $fixture['target_class_id'])
            ->where('id_semester', $fixture['target_semester_id'])
            ->count());
        $this->assertSame(0, $connection->table('mata_pelajaran')->where('kode_mapel', $fixture['kode_mapel'])->count());
        $this->assertSame(0, $connection->table('mata_pelajaran')->where('kode_mapel', 'CFG-'.$fixture['suffix'])->count());
        $this->assertSame(0, $connection->table('pendaftaran_ppdb')->where('nomor_pendaftaran', 'IT-PPDB-'.$fixture['suffix'])->count());
        $this->assertSame(0, $connection->table('notifikasi')->whereIn('id_notifikasi', collect($fixture['notification_ids'])
            ->flatten()->all())->count());
        $this->assertSame(0, $connection->table('rapor')->where('id_anggota', $fixture['id_keanggotaan'])->count());
        $this->assertSame(0, $connection->table('nilai_siswa')->where('id_anggota', $fixture['id_keanggotaan'])->count());
        $this->assertSame(0, $connection->table('jadwal_pelajaran')
            ->where('id_kelas', $fixture['id_kelas'])
            ->where('id_semester', $fixture['id_semester'])
            ->count());
        $this->assertSame(0, $connection->table('mutasi_siswa')
            ->where('id_siswa', $fixture['id_siswa'])
            ->where('id_pengaju', $fixture['user_id_by_role']['tu'])
            ->count());
        $this->assertSame(0, $connection->table('tahun_ajaran')->whereIn('id_tahun_ajaran', [
            $fixture['source_year_id'],
            $fixture['target_year_id'],
        ])->count());

        $this->assertSame(
            $fixture['period_statuses'],
            $connection->table('periode_update')
                ->orderBy('id_periode_update')
                ->get(['id_periode_update', 'status'])
                ->mapWithKeys(fn ($row) => [$row->id_periode_update => $row->status])
                ->all()
        );
    }
}
