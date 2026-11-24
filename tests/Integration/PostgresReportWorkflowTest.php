<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PostgresReportWorkflowTest extends TestCase
{
    public function test_wali_can_save_finalize_and_lock_a_report_on_postgresql(): void
    {
        if (getenv('RUN_LIVE_PGSQL_REPORT_TEST') !== '1') {
            $this->markTestSkipped('Set RUN_LIVE_PGSQL_REPORT_TEST=1 to opt in to the live PostgreSQL test.');
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

        $connection->beginTransaction();
        $fixture = [];

        try {
            $fixture = $this->createFixture($connection);
            $session = [
                'id_pengguna' => $fixture['id_pengguna'],
                'username' => $fixture['username'],
                'role' => 'wali_kelas',
            ];
            $reportUrl = route('guru.rapor', [
                'kelas' => $fixture['id_kelas'],
                'mapel' => $fixture['id_mapel'],
            ]);

            $this->withSession($session)
                ->get($reportUrl)
                ->assertOk()
                ->assertSee($fixture['nama_mapel'])
                ->assertSee($fixture['nama_siswa']);

            $this->withSession($session)
                ->post(route('guru.rapor.simpan'), [
                    'id_kelas' => $fixture['id_kelas'],
                    'id_mapel' => $fixture['id_mapel'],
                    'nilai_mentah' => [
                        $fixture['id_siswa'] => [
                            'tugas' => 80,
                            'keterampilan' => 80,
                            'ujian' => 80,
                        ],
                    ],
                    'catatan_siswa' => [
                        $fixture['id_siswa'] => 'Catatan uji sementara.',
                    ],
                ])
                ->assertRedirect($reportUrl)
                ->assertSessionHas('success', 'Nilai rapor berhasil disimpan untuk seluruh siswa.');

            $report = $connection->table('rapor')
                ->where('id_anggota', $fixture['id_keanggotaan'])
                ->where('id_semester', $fixture['id_semester'])
                ->first();

            $this->assertNotNull($report);
            $this->assertSame('draft', $report->status_rapor);
            $this->assertSame('Catatan uji sementara.', $report->catatan_wali);
            $this->assertEquals(80.0, (float) $report->rata_rata);

            $this->assertSame(
                [80.0, 80.0, 80.0],
                $connection->table('nilai_siswa')
                    ->where('id_anggota', $fixture['id_keanggotaan'])
                    ->where('id_mapel', $fixture['id_mapel'])
                    ->where('id_semester', $fixture['id_semester'])
                    ->orderBy('id_komponen')
                    ->pluck('nilai')
                    ->map(fn ($nilai) => (float) $nilai)
                    ->all()
            );

            $this->assertEquals(
                80.0,
                (float) $connection->table('detail_rapor')
                    ->where('id_rapor', $report->id_rapor)
                    ->where('id_mapel', $fixture['id_mapel'])
                    ->value('nilai_akhir')
            );

            $this->withSession($session)
                ->post(route('guru.rapor.finalisasi'), [
                    'id_rapor' => $report->id_rapor,
                ])
                ->assertRedirect(route('guru.rapor'))
                ->assertSessionHas('success', 'Rapor siswa berhasil dikunci.');

            $this->assertSame(
                'final',
                $connection->table('rapor')->where('id_rapor', $report->id_rapor)->value('status_rapor')
            );

            $this->from($reportUrl)
                ->withSession($session)
                ->post(route('guru.rapor.simpan'), [
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
                ->assertRedirect($reportUrl)
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
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
        }

        $this->assertSame(
            0,
            $connection->table('pengguna')->where('username', $fixture['username'])->count()
        );
        $this->assertSame(
            0,
            $connection->table('mata_pelajaran')->where('kode_mapel', $fixture['kode_mapel'])->count()
        );
        $this->assertSame(
            0,
            $connection->table('siswa')->where('nisn', $fixture['nisn'])->count()
        );
    }

    private function createFixture($connection): array
    {
        if ($connection->table('komponen_penilaian')->exists()) {
            throw new RuntimeException('Live test requires an empty komponen_penilaian table to avoid using existing configuration.');
        }

        $semesterId = (int) ($connection->selectOne('SELECT public.fn_semester_aktif() AS id_semester')->id_semester ?? 0);
        $semester = $connection->table('semester')->where('id_semester', $semesterId)->first();
        $roleId = $connection->table('role')->where('nama_role', 'wali_kelas')->value('id_role');
        $statusSiswaId = $connection->table('status_siswa')->where('kode_status', 'aktif')->value('id_status_siswa');

        if (! $semester || ! $roleId || ! $statusSiswaId) {
            throw new RuntimeException('Live test prerequisites are missing an active semester, wali role, or active student status.');
        }

        $kelas = $connection->table('kelas as k')
            ->leftJoin('penugasan_wali_kelas as pw', function ($join) use ($semesterId) {
                $join->on('pw.id_kelas', '=', 'k.id_kelas')
                    ->where('pw.id_semester', '=', $semesterId);
            })
            ->whereNull('pw.id_kelas')
            ->where('k.is_aktif', true)
            ->where('k.id_tahun_ajaran', $semester->id_tahun_ajaran)
            ->orderBy('k.id_kelas')
            ->first(['k.id_kelas']);

        if (! $kelas) {
            throw new RuntimeException('Live test could not find an unassigned active class in the active semester.');
        }

        $suffix = strtoupper(Str::random(10));
        $username = 'it-rapor-'.$suffix;
        $namaSiswa = 'Siswa Uji '.$suffix;
        $namaMapel = 'Mata Pelajaran Uji '.$suffix;
        $kodeMapel = 'IT'.$suffix;
        $nisn = '9'.$suffix;

        $idPengguna = $connection->table('pengguna')->insertGetId([
            'id_role' => $roleId,
            'username' => $username,
            'password_hash' => Hash::make(Str::random(40)),
            'is_aktif' => true,
        ], 'id_pengguna');

        $idGuru = $connection->table('guru')->insertGetId([
            'id_pengguna' => $idPengguna,
            'nip' => 'IT'.$suffix,
            'nama_guru' => 'Guru Uji '.$suffix,
            'is_aktif' => true,
        ], 'id_guru');

        $idMapel = $connection->table('mata_pelajaran')->insertGetId([
            'kode_mapel' => $kodeMapel,
            'nama_mapel' => $namaMapel,
            'is_aktif' => true,
        ], 'id_mata_pelajaran');

        foreach ([
            ['Tugas Uji '.$suffix, 30],
            ['Keterampilan Uji '.$suffix, 30],
            ['Ujian Uji '.$suffix, 40],
        ] as [$namaKomponen, $bobot]) {
            $connection->table('komponen_penilaian')->insert([
                'nama_komponen' => $namaKomponen,
                'bobot_persen' => $bobot,
            ]);
        }

        $idSiswa = $connection->table('siswa')->insertGetId([
            'id_status_siswa' => $statusSiswaId,
            'nis' => 'IT'.$suffix,
            'nisn' => $nisn,
            'nama_lengkap' => $namaSiswa,
        ], 'id_siswa');

        $idKeanggotaan = $connection->table('keanggotaan_kelas')->insertGetId([
            'id_siswa' => $idSiswa,
            'id_kelas' => $kelas->id_kelas,
            'id_semester' => $semesterId,
            'status' => 'aktif',
        ], 'id_keanggotaan');

        $connection->table('penugasan_wali_kelas')->insert([
            'id_kelas' => $kelas->id_kelas,
            'id_guru' => $idGuru,
            'id_semester' => $semesterId,
        ]);

        return [
            'username' => $username,
            'kode_mapel' => $kodeMapel,
            'nisn' => $nisn,
            'nama_siswa' => $namaSiswa,
            'nama_mapel' => $namaMapel,
            'id_pengguna' => $idPengguna,
            'id_kelas' => (int) $kelas->id_kelas,
            'id_mapel' => $idMapel,
            'id_siswa' => $idSiswa,
            'id_semester' => $semesterId,
            'id_keanggotaan' => $idKeanggotaan,
        ];
    }
}
