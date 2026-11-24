<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach ([
        'detail_absensi',
        'sesi_absensi',
        'status_kehadiran',
        'notifikasi',
        'catatan_wali_kelas',
        'keanggotaan_kelas',
        'penugasan_wali_kelas',
        'guru',
        'mata_pelajaran',
        'kelas',
        'semester',
        'tahun_ajaran',
        'siswa',
        'pengguna',
        'role',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('role', function (Blueprint $table) {
        $table->id('id_role');
        $table->string('nama_role');
        $table->string('kode_role');
    });

    Schema::create('pengguna', function (Blueprint $table) {
        $table->id('id_pengguna');
        $table->unsignedBigInteger('id_role');
        $table->string('username');
        $table->string('password_hash');
        $table->boolean('is_aktif')->default(true);
    });

    Schema::create('guru', function (Blueprint $table) {
        $table->id('id_guru');
        $table->unsignedBigInteger('id_pengguna');
        $table->string('nama_guru');
        $table->boolean('is_aktif')->default(true);
    });

    Schema::create('semester', function (Blueprint $table) {
        $table->id('id_semester');
        $table->unsignedBigInteger('id_tahun_ajaran')->nullable();
        $table->string('nama_semester');
        $table->date('tanggal_mulai')->nullable();
        $table->date('tanggal_selesai')->nullable();
        $table->boolean('is_aktif')->default(false);
    });

    Schema::create('kelas', function (Blueprint $table) {
        $table->id('id_kelas');
    });

    Schema::create('siswa', function (Blueprint $table) {
        $table->id('id_siswa');
        $table->unsignedBigInteger('id_pengguna')->nullable();
    });

    Schema::create('tahun_ajaran', function (Blueprint $table) {
        $table->id('id_tahun_ajaran');
        $table->string('nama_tahun_ajaran');
        $table->date('tanggal_mulai')->nullable();
        $table->boolean('is_aktif')->default(false);
    });

    Schema::create('keanggotaan_kelas', function (Blueprint $table) {
        $table->id('id_keanggotaan');
        $table->unsignedBigInteger('id_siswa');
        $table->unsignedBigInteger('id_kelas');
        $table->unsignedBigInteger('id_semester');
        $table->string('status');
    });

    Schema::create('penugasan_wali_kelas', function (Blueprint $table) {
        $table->id('id_penugasan_wali_kelas');
        $table->unsignedBigInteger('id_kelas');
        $table->unsignedBigInteger('id_guru');
        $table->unsignedBigInteger('id_semester');
    });

    Schema::create('status_kehadiran', function (Blueprint $table) {
        $table->id('id_status_hadir');
        $table->string('kode');
        $table->string('nama_status');
    });

    Schema::create('catatan_wali_kelas', function (Blueprint $table) {
        $table->id('id_catatan');
        $table->unsignedBigInteger('id_siswa');
        $table->unsignedBigInteger('id_guru');
        $table->unsignedBigInteger('id_tahun_ajaran');
        $table->text('isi_catatan');
        $table->timestamp('tgl_dibuat')->nullable();
    });

    Schema::create('notifikasi', function (Blueprint $table) {
        $table->id('id_notifikasi');
        $table->unsignedBigInteger('id_pengguna');
        $table->string('judul');
        $table->text('pesan');
        $table->boolean('dibaca')->default(false);
        $table->timestamp('dibuat_pada')->nullable();
    });

    Schema::create('sesi_absensi', function (Blueprint $table) {
        $table->id('id_sesi');
        $table->unsignedBigInteger('id_jadwal')->nullable();
        $table->unsignedBigInteger('id_kelas');
        $table->unsignedBigInteger('id_semester');
        $table->unsignedBigInteger('id_guru_pencatat');
        $table->date('tanggal');
        $table->text('topik_materi')->nullable();
        $table->time('jam_dibuka')->nullable();
        $table->string('status_sesi');
        $table->unique(['id_kelas', 'id_semester', 'tanggal']);
    });

    Schema::create('detail_absensi', function (Blueprint $table) {
        $table->id('id_detail_absensi');
        $table->unsignedBigInteger('id_sesi');
        $table->unsignedBigInteger('id_siswa');
        $table->unsignedBigInteger('id_status_hadir');
        $table->text('keterangan')->nullable();
        $table->timestamp('waktu_dicatat')->nullable();
        $table->unique(['id_sesi', 'id_siswa']);
    });

    Schema::create('mata_pelajaran', function (Blueprint $table) {
        $table->id('id_mata_pelajaran');
    });

    DB::table('role')->insert(['id_role' => 1, 'nama_role' => 'wali_kelas', 'kode_role' => 'wali_kelas']);
    DB::table('pengguna')->insert(['id_pengguna' => 1, 'id_role' => 1, 'username' => 'wali01', 'password_hash' => 'hash']);
    DB::table('guru')->insert(['id_guru' => 1, 'id_pengguna' => 1, 'nama_guru' => 'Wali Kelas']);
    DB::table('semester')->insert(['id_semester' => 1, 'nama_semester' => 'Ganjil', 'is_aktif' => true]);
    DB::table('tahun_ajaran')->insert([
        'id_tahun_ajaran' => 1,
        'nama_tahun_ajaran' => '2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'is_aktif' => true,
    ]);
    DB::table('kelas')->insert(['id_kelas' => 1]);
    DB::table('siswa')->insert(['id_siswa' => 11, 'id_pengguna' => 2]);
    DB::table('pengguna')->insert([
        'id_pengguna' => 2,
        'id_role' => 1,
        'username' => 'siswa01',
        'password_hash' => 'hash',
    ]);
    DB::table('keanggotaan_kelas')->insert([
        'id_siswa' => 11,
        'id_kelas' => 1,
        'id_semester' => 1,
        'status' => 'aktif',
    ]);
    DB::table('penugasan_wali_kelas')->insert([
        'id_kelas' => 1,
        'id_guru' => 1,
        'id_semester' => 1,
    ]);
});

it('saves daily class attendance without a lesson schedule', function () {
    $tanggal = '2026-11-16';

    $this->withSession([
        'id_pengguna' => 1,
        'username' => 'wali01',
        'role' => 'wali_kelas',
    ])->from(route('guru.absensi'))
        ->post(route('guru.absensi.simpan'), [
            'id_kelas' => 1,
            'tanggal' => $tanggal,
            'status' => [11 => 'A'],
        ])
        ->assertRedirect(route('guru.absensi', ['kelas' => 1, 'tanggal' => $tanggal]))
        ->assertSessionHas('success', 'Presensi kelas berhasil disimpan.');

    $this->assertDatabaseHas('sesi_absensi', [
        'id_jadwal' => null,
        'id_kelas' => 1,
        'id_semester' => 1,
        'tanggal' => $tanggal,
    ]);

    $this->assertDatabaseHas('detail_absensi', [
        'id_siswa' => 11,
        'id_status_hadir' => DB::table('status_kehadiran')->where('kode', 'A')->value('id_status_hadir'),
    ]);
});

it('sends a class note and notification to a student in the homeroom class', function () {
    $this->withSession([
        'id_pengguna' => 1,
        'username' => 'wali01',
        'role' => 'wali_kelas',
    ])->post(route('guru.catatan.simpan'), [
        'id_siswa' => 11,
        'isi_catatan' => 'Pertahankan perkembangan belajar yang baik.',
    ])
        ->assertRedirect(route('guru.catatan'))
        ->assertSessionHas('success', 'Catatan wali kelas berhasil disimpan.');

    $this->assertDatabaseHas('catatan_wali_kelas', [
        'id_siswa' => 11,
        'id_guru' => 1,
        'id_tahun_ajaran' => 1,
        'isi_catatan' => 'Pertahankan perkembangan belajar yang baik.',
    ]);
    $this->assertDatabaseHas('notifikasi', [
        'id_pengguna' => 2,
        'judul' => 'Catatan dari Wali Kelas',
        'pesan' => 'Wali kelas memberikan catatan: Pertahankan perkembangan belajar yang baik.',
        'dibaca' => false,
    ]);
});

it('reuses the daily attendance session and updates the student status on resubmission', function () {
    $tanggal = '2026-11-16';
    $session = [
        'id_pengguna' => 1,
        'username' => 'wali01',
        'role' => 'wali_kelas',
    ];

    $this->withSession($session)
        ->post(route('guru.absensi.simpan'), [
            'id_kelas' => 1,
            'tanggal' => $tanggal,
            'status' => [11 => 'A'],
        ])
        ->assertRedirect(route('guru.absensi', ['kelas' => 1, 'tanggal' => $tanggal]));

    $this->withSession($session)
        ->post(route('guru.absensi.simpan'), [
            'id_kelas' => 1,
            'tanggal' => $tanggal,
            'status' => [11 => 'H'],
        ])
        ->assertRedirect(route('guru.absensi', ['kelas' => 1, 'tanggal' => $tanggal]))
        ->assertSessionHas('success', 'Presensi kelas berhasil disimpan.');

    $this->assertDatabaseCount('sesi_absensi', 1);
    $this->assertDatabaseHas('detail_absensi', [
        'id_sesi' => DB::table('sesi_absensi')->value('id_sesi'),
        'id_siswa' => 11,
        'id_status_hadir' => DB::table('status_kehadiran')->where('kode', 'H')->value('id_status_hadir'),
    ]);
});

it('keeps homeroom reports inaccessible to student accounts', function () {
    DB::table('role')->insert([
        'id_role' => 3,
        'nama_role' => 'siswa',
        'kode_role' => 'siswa',
    ]);
    DB::table('pengguna')->insert([
        'id_pengguna' => 3,
        'id_role' => 3,
        'username' => 'siswa01',
        'password_hash' => 'hash',
    ]);

    $this->withSession([
        'id_pengguna' => 3,
        'username' => 'siswa01',
        'role' => 'siswa',
    ])->get(route('guru.rapor'))
        ->assertNotFound();
});
