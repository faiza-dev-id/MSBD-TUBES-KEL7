<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    buatSkemaMutasiWorkflow();

    DB::table('status_siswa')->insert([
        ['id_status_siswa' => 1, 'kode_status' => 'aktif', 'nama_status' => 'Aktif', 'boleh_diubah_siswa' => false],
        ['id_status_siswa' => 2, 'kode_status' => 'mutasi', 'nama_status' => 'Mutasi', 'boleh_diubah_siswa' => false],
        ['id_status_siswa' => 3, 'kode_status' => 'mengundurkan_diri', 'nama_status' => 'Mengundurkan Diri', 'boleh_diubah_siswa' => false],
        ['id_status_siswa' => 4, 'kode_status' => 'dikeluarkan', 'nama_status' => 'Dikeluarkan', 'boleh_diubah_siswa' => false],
        ['id_status_siswa' => 5, 'kode_status' => 'putus_sekolah', 'nama_status' => 'Putus Sekolah', 'boleh_diubah_siswa' => false],
        ['id_status_siswa' => 6, 'kode_status' => 'meninggal_dunia', 'nama_status' => 'Meninggal Dunia', 'boleh_diubah_siswa' => false],
    ]);

    $migration = require database_path('migrations/2026_11_16_022124_add_mutation_workflow_fields_to_mutasi_siswa_table.php');
    $migration->up();
    $migrationSekolahTujuan = require database_path('migrations/2026_11_16_025800_add_nama_sekolah_tujuan_to_mutasi_siswa_table.php');
    $migrationSekolahTujuan->up();

    DB::table('role')->insert([
        ['id_role' => 1, 'nama_role' => 'siswa', 'kode_role' => 'siswa'],
        ['id_role' => 3, 'nama_role' => 'tu', 'kode_role' => 'tu'],
        ['id_role' => 4, 'nama_role' => 'kepsek', 'kode_role' => 'kepsek'],
    ]);

    DB::table('pengguna')->insert([
        ['id_pengguna' => 10, 'id_role' => 3, 'username' => 'petugas-tu', 'password_hash' => 'hash', 'is_aktif' => true],
        ['id_pengguna' => 20, 'id_role' => 4, 'username' => 'kepsek', 'password_hash' => 'hash', 'is_aktif' => true],
    ]);

    DB::table('sekolah')->insert([
        'id_sekolah' => 1,
        'nama_sekolah' => 'SMP Asal',
    ]);
    DB::table('tahun_ajaran')->insert([
        'id_tahun_ajaran' => 1,
        'nama_tahun_ajaran' => '2026/2027',
        'is_aktif' => true,
    ]);
    DB::table('semester')->insert([
        'id_semester' => 1,
        'id_tahun_ajaran' => 1,
        'nama_semester' => 'Ganjil',
        'tanggal_mulai' => '2026-07-01',
        'is_aktif' => true,
    ]);
    DB::table('kelas')->insert([
        'id_kelas' => 1,
        'id_tahun_ajaran' => 1,
        'nama_kelas' => 'X MIA 1',
        'is_aktif' => true,
    ]);

    Cache::put('pengaturan_sistem', ['ukuran_berkas_mb' => '2'], 300);
});

it('registers incoming students directly for TU and assigns the selected active class', function () {
    $response = $this->withSession(sesiMutasiWorkflow('tu'))
        ->post(route('tu.mutasi.simpan'), [
            'jenis' => 'pindah_masuk',
            'nisn_baru' => '1234567890',
            'nama_siswa_baru' => 'Siswa Pindahan',
            'jenis_kelamin_baru' => 'P',
            'id_sekolah_asal' => 1,
            'id_kelas_tujuan' => 1,
            'tanggal_efektif' => '2026-11-20',
            'alasan' => 'Pindah domisili.',
        ]);

    $response->assertRedirect(route('tu.mutasi'))
        ->assertSessionHas('success');

    $mutasi = DB::table('mutasi_siswa')->where('jenis', 'pindah_masuk')->first();
    $siswa = DB::table('siswa')->where('nisn', '1234567890')->first();

    expect($mutasi->status)->toBe('disetujui')
        ->and($mutasi->id_siswa)->toBe($siswa->id_siswa)
        ->and((int) $siswa->id_status_siswa)->toBe(1)
        ->and($siswa->id_sekolah_asal)->toBe(1);

    $this->assertDatabaseHas('keanggotaan_kelas', [
        'id_siswa' => $siswa->id_siswa,
        'id_kelas' => 1,
        'id_semester' => 1,
        'status' => 'aktif',
    ]);
    $this->assertDatabaseHas('pengguna', [
        'username' => '1234567890',
        'id_role' => 1,
    ]);
    $this->assertDatabaseHas('log_audit', [
        'nama_tabel' => 'mutasi_siswa',
        'operasi' => 'INSERT',
    ]);
});

it('sends outgoing requests to the principal and stores their attachment', function () {
    Storage::fake('private');
    $idSiswa = siswaAktifMutasiWorkflow();
    $lampiran = UploadedFile::fake()->create('surat-pindah.pdf', 32, 'application/pdf');

    $response = $this->withSession(sesiMutasiWorkflow('tu'))
        ->post(route('tu.mutasi.simpan'), [
            'jenis' => 'pindah_keluar',
            'id_siswa' => $idSiswa,
            'sekolah_tujuan' => 'SMA Bina Karya',
            'tanggal_efektif' => '2026-11-20',
            'alasan' => 'Mengikuti orang tua.',
            'lampiran' => $lampiran,
        ]);

    $response->assertRedirect(route('tu.mutasi'))
        ->assertSessionHas('success');

    $mutasi = DB::table('mutasi_siswa')->where('jenis', 'pindah_keluar')->first();

    expect($mutasi->status)->toBe('menunggu')
        ->and($mutasi->tanggal_efektif)->toBe('2026-11-20')
        ->and($mutasi->nama_lampiran)->toBe('surat-pindah.pdf')
        ->and($mutasi->nama_sekolah_tujuan)->toBe('SMA Bina Karya')
        ->and($mutasi->id_sekolah_tujuan)->toBeNull();

    Storage::disk('private')->assertExists($mutasi->path_lampiran);
    $this->assertDatabaseHas('notifikasi', [
        'id_pengguna' => 20,
        'judul' => 'Pengajuan mutasi siswa',
    ]);

    $this->withSession(sesiMutasiWorkflow('tu'))
        ->get(route('tu.mutasi'))
        ->assertOk()
        ->assertSee('value="SMA Bina Karya"', false)
        ->assertSee('Sekolah Tujuan', false)
        ->assertDontSee('<select class="input" id="id_sekolah_tujuan"', false);
});

it('restricts outgoing decisions to the principal and requires a rejection note', function () {
    $idSiswa = siswaAktifMutasiWorkflow();
    $idMutasi = mutasiMenungguWorkflow($idSiswa, 'pindah_keluar');

    $this->withSession(sesiMutasiWorkflow('tu'))
        ->post(route('kepsek.mutasi.putuskan', $idMutasi), [
            'keputusan' => 'setujui',
        ])
        ->assertNotFound();

    $this->withSession(sesiMutasiWorkflow('kepsek'))
        ->post(route('kepsek.mutasi.putuskan', $idMutasi), [
            'keputusan' => 'tolak',
        ])
        ->assertSessionHasErrors('catatan_keputusan');

    $this->assertDatabaseHas('mutasi_siswa', [
        'id_mutasi' => $idMutasi,
        'status' => 'menunggu',
    ]);
    $this->assertDatabaseMissing('notifikasi', [
        'id_pengguna' => 10,
        'judul' => 'Keputusan mutasi siswa',
    ]);
});

it('records the principal rejection note and notifies the requesting TU', function () {
    $idSiswa = siswaAktifMutasiWorkflow();
    $idMutasi = mutasiMenungguWorkflow($idSiswa, 'pindah_keluar');

    $this->withSession(sesiMutasiWorkflow('kepsek'))
        ->post(route('kepsek.mutasi.putuskan', $idMutasi), [
            'keputusan' => 'tolak',
            'catatan_keputusan' => 'Dokumen pendukung belum lengkap.',
        ])
        ->assertRedirect(route('kepsek.mutasi'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('mutasi_siswa', [
        'id_mutasi' => $idMutasi,
        'status' => 'ditolak',
        'id_penyetuju' => 20,
        'catatan_keputusan' => 'Dokumen pendukung belum lengkap.',
    ]);
    $this->assertDatabaseHas('siswa', [
        'id_siswa' => $idSiswa,
        'id_status_siswa' => 1,
    ]);
    $this->assertDatabaseHas('keanggotaan_kelas', [
        'id_siswa' => $idSiswa,
        'status' => 'aktif',
    ]);
    $this->assertDatabaseHas('notifikasi', [
        'id_pengguna' => 10,
        'judul' => 'Keputusan mutasi siswa',
    ]);
});

it('allows the principal to reject all outgoing mutation types without changing student status', function () {
    foreach (['pindah_keluar', 'mengundurkan_diri', 'dikeluarkan', 'putus_sekolah', 'meninggal_dunia'] as $jenis) {
        $idSiswa = siswaAktifMutasiWorkflow();
        $idMutasi = mutasiMenungguWorkflow($idSiswa, $jenis);

        $this->withSession(sesiMutasiWorkflow('kepsek'))
            ->post(route('kepsek.mutasi.putuskan', $idMutasi), [
                'keputusan' => 'tolak',
                'catatan_keputusan' => 'Pengajuan ditolak dalam pengujian.',
            ])
            ->assertRedirect(route('kepsek.mutasi'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('mutasi_siswa', [
            'id_mutasi' => $idMutasi,
            'status' => 'ditolak',
        ]);
        $this->assertDatabaseHas('siswa', [
            'id_siswa' => $idSiswa,
            'id_status_siswa' => 1,
        ]);
        $this->assertDatabaseHas('keanggotaan_kelas', [
            'id_siswa' => $idSiswa,
            'status' => 'aktif',
        ]);
    }
});

it('updates student status and class history when the principal approves exit cases', function () {
    $cases = [
        'pindah_keluar' => ['mutasi', 'pindah'],
        'mengundurkan_diri' => ['mengundurkan_diri', 'keluar'],
        'dikeluarkan' => ['dikeluarkan', 'keluar'],
        'putus_sekolah' => ['putus_sekolah', 'keluar'],
        'meninggal_dunia' => ['meninggal_dunia', 'keluar'],
    ];

    foreach ($cases as $jenis => [$kodeStatus, $statusAnggota]) {
        $idSiswa = siswaAktifMutasiWorkflow();
        $idMutasi = mutasiMenungguWorkflow($idSiswa, $jenis);

        $this->withSession(sesiMutasiWorkflow('kepsek'))
            ->post(route('kepsek.mutasi.putuskan', $idMutasi), [
                'keputusan' => 'setujui',
            ])
            ->assertRedirect(route('kepsek.mutasi'))
            ->assertSessionHas('success');

        $idStatus = DB::table('status_siswa')->where('kode_status', $kodeStatus)->value('id_status_siswa');
        $this->assertDatabaseHas('siswa', [
            'id_siswa' => $idSiswa,
            'id_status_siswa' => $idStatus,
        ]);
        $this->assertDatabaseHas('keanggotaan_kelas', [
            'id_siswa' => $idSiswa,
            'status' => $statusAnggota,
            'tanggal_selesai' => '2026-11-20',
        ]);
        $this->assertDatabaseHas('mutasi_siswa', [
            'id_mutasi' => $idMutasi,
            'status' => 'disetujui',
            'id_penyetuju' => 20,
        ]);
    }

    $this->assertSame(5, DB::table('notifikasi')
        ->where('id_pengguna', 10)
        ->where('judul', 'Keputusan mutasi siswa')
        ->count());
    $this->assertSame(5, DB::table('log_audit')
        ->where('nama_tabel', 'mutasi_siswa')
        ->where('operasi', 'UPDATE')
        ->count());
});

it('allows TU to revise and cancel only pending outgoing requests', function () {
    $idSiswa = siswaAktifMutasiWorkflow();
    $idMutasi = mutasiMenungguWorkflow($idSiswa, 'pindah_keluar');

    $this->withSession(sesiMutasiWorkflow('tu'))
        ->put(route('tu.mutasi.revisi', $idMutasi), [
            'jenis' => 'mengundurkan_diri',
            'id_siswa' => $idSiswa,
            'tanggal_efektif' => '2026-11-21',
            'alasan' => 'Pengajuan direvisi.',
        ])
        ->assertRedirect(route('tu.mutasi'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('mutasi_siswa', [
        'id_mutasi' => $idMutasi,
        'jenis' => 'mengundurkan_diri',
        'status' => 'menunggu',
        'tanggal_efektif' => '2026-11-21',
    ]);

    $this->withSession(sesiMutasiWorkflow('tu'))
        ->delete(route('tu.mutasi.batalkan', $idMutasi))
        ->assertRedirect(route('tu.mutasi'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('mutasi_siswa', [
        'id_mutasi' => $idMutasi,
        'status' => 'dibatalkan',
    ]);
});

function buatSkemaMutasiWorkflow(): void
{
    Schema::disableForeignKeyConstraints();

    foreach ([
        'mutasi_siswa',
        'log_audit',
        'notifikasi',
        'status_siswa',
        'keanggotaan_kelas',
        'siswa',
        'pengguna',
        'role',
        'semester',
        'tahun_ajaran',
        'kelas',
        'sekolah',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::enableForeignKeyConstraints();

    Schema::create('role', function (Blueprint $table) {
        $table->id('id_role');
        $table->string('nama_role');
        $table->string('kode_role')->nullable();
    });

    Schema::create('pengguna', function (Blueprint $table) {
        $table->id('id_pengguna');
        $table->unsignedBigInteger('id_role');
        $table->string('username')->unique();
        $table->string('password_hash');
        $table->boolean('is_aktif')->default(true);
        $table->timestamp('dibuat_pada')->nullable();
        $table->timestamp('diubah_pada')->nullable();
    });

    Schema::create('status_siswa', function (Blueprint $table) {
        $table->id('id_status_siswa');
        $table->string('kode_status')->unique();
        $table->string('nama_status');
        $table->boolean('boleh_diubah_siswa')->default(false);
    });

    Schema::create('sekolah', function (Blueprint $table) {
        $table->id('id_sekolah');
        $table->string('nama_sekolah');
    });

    Schema::create('tahun_ajaran', function (Blueprint $table) {
        $table->id('id_tahun_ajaran');
        $table->string('nama_tahun_ajaran');
        $table->boolean('is_aktif')->default(false);
    });

    Schema::create('semester', function (Blueprint $table) {
        $table->id('id_semester');
        $table->unsignedBigInteger('id_tahun_ajaran');
        $table->string('nama_semester');
        $table->date('tanggal_mulai');
        $table->boolean('is_aktif')->default(false);
    });

    Schema::create('kelas', function (Blueprint $table) {
        $table->id('id_kelas');
        $table->unsignedBigInteger('id_tahun_ajaran');
        $table->string('nama_kelas');
        $table->boolean('is_aktif')->default(true);
    });

    Schema::create('siswa', function (Blueprint $table) {
        $table->id('id_siswa');
        $table->unsignedBigInteger('id_pengguna')->nullable();
        $table->unsignedBigInteger('id_status_siswa');
        $table->unsignedBigInteger('id_sekolah_asal')->nullable();
        $table->string('nisn', 10)->unique();
        $table->string('nama_lengkap');
        $table->char('jenis_kelamin', 1)->nullable();
        $table->string('tempat_lahir')->nullable();
        $table->date('tanggal_lahir')->nullable();
        $table->string('email')->nullable();
        $table->string('no_telepon')->nullable();
        $table->date('tanggal_masuk')->nullable();
        $table->timestamp('dibuat_pada')->nullable();
        $table->timestamp('diubah_pada')->nullable();
    });

    Schema::create('keanggotaan_kelas', function (Blueprint $table) {
        $table->id('id_anggota');
        $table->unsignedBigInteger('id_siswa');
        $table->unsignedBigInteger('id_kelas');
        $table->unsignedBigInteger('id_semester');
        $table->date('tanggal_mulai');
        $table->date('tanggal_selesai')->nullable();
        $table->string('status');
    });

    Schema::create('notifikasi', function (Blueprint $table) {
        $table->id('id_notifikasi');
        $table->unsignedBigInteger('id_pengguna');
        $table->string('judul');
        $table->text('pesan');
        $table->boolean('dibaca')->default(false);
        $table->timestamp('dibuat_pada');
    });

    Schema::create('log_audit', function (Blueprint $table) {
        $table->id('id_log');
        $table->string('nama_tabel');
        $table->string('operasi');
        $table->string('id_data')->nullable();
        $table->json('nilai_lama')->nullable();
        $table->json('nilai_baru')->nullable();
        $table->unsignedBigInteger('id_pengguna_pengubah')->nullable();
        $table->string('username_pengubah')->nullable();
        $table->timestamp('waktu');
    });

    Schema::create('mutasi_siswa', function (Blueprint $table) {
        $table->id('id_mutasi');
        $table->unsignedBigInteger('id_siswa');
        $table->unsignedBigInteger('id_pengaju');
        $table->unsignedBigInteger('id_penyetuju')->nullable();
        $table->unsignedBigInteger('id_sekolah_tujuan')->nullable();
        $table->string('jenis', 40);
        $table->text('alasan')->nullable();
        $table->string('status', 30)->default('menunggu');
        $table->timestamp('tgl_pengajuan')->nullable();
        $table->timestamp('tgl_keputusan')->nullable();
    });
}

function sesiMutasiWorkflow(string $role): array
{
    $pengguna = $role === 'kepsek'
        ? ['id_pengguna' => 20, 'username' => 'kepsek', 'nama' => 'Kepala Sekolah']
        : ['id_pengguna' => 10, 'username' => 'petugas-tu', 'nama' => 'Tata Usaha'];

    return [...$pengguna, 'role' => $role];
}

function siswaAktifMutasiWorkflow(): int
{
    $idSiswa = (int) DB::table('siswa')->insertGetId([
        'id_status_siswa' => 1,
        'nisn' => (string) (sprintf('%010d', random_int(1, 9999999999))),
        'nama_lengkap' => 'Siswa Aktif',
        'jenis_kelamin' => 'L',
        'tanggal_masuk' => '2026-07-01',
    ], 'id_siswa');

    DB::table('keanggotaan_kelas')->insert([
        'id_siswa' => $idSiswa,
        'id_kelas' => 1,
        'id_semester' => 1,
        'tanggal_mulai' => '2026-07-01',
        'status' => 'aktif',
    ]);

    return $idSiswa;
}

function mutasiMenungguWorkflow(int $idSiswa, string $jenis): int
{
    return (int) DB::table('mutasi_siswa')->insertGetId([
        'id_siswa' => $idSiswa,
        'id_pengaju' => 10,
        'jenis' => $jenis,
        'alasan' => 'Alasan pengajuan.',
        'status' => 'menunggu',
        'tanggal_efektif' => '2026-11-20',
        'tgl_pengajuan' => now(),
    ], 'id_mutasi');
}
