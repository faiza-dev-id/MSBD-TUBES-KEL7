<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    DB::statement('DROP VIEW IF EXISTS vw_nilai_rata_siswa');

    foreach ([
        'detail_rapor',
        'skala_predikat',
        'mata_pelajaran',
        'rapor',
        'keanggotaan_kelas',
        'kelas',
        'semester',
        'tahun_ajaran',
        'siswa',
        'notifikasi',
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
    Schema::create('siswa', function (Blueprint $table) {
        $table->id('id_siswa');
        $table->unsignedBigInteger('id_pengguna');
    });
    Schema::create('tahun_ajaran', function (Blueprint $table) {
        $table->id('id_tahun_ajaran');
        $table->string('nama_tahun_ajaran');
        $table->date('tanggal_mulai')->nullable();
        $table->boolean('is_aktif')->default(false);
    });
    Schema::create('semester', function (Blueprint $table) {
        $table->id('id_semester');
        $table->unsignedBigInteger('id_tahun_ajaran');
        $table->string('nama_semester');
        $table->date('tanggal_mulai')->nullable();
    });
    Schema::create('kelas', function (Blueprint $table) {
        $table->id('id_kelas');
        $table->string('nama_kelas');
    });
    Schema::create('keanggotaan_kelas', function (Blueprint $table) {
        $table->id('id_keanggotaan');
        $table->unsignedBigInteger('id_siswa');
        $table->unsignedBigInteger('id_kelas');
        $table->unsignedBigInteger('id_semester');
    });
    Schema::create('rapor', function (Blueprint $table) {
        $table->id('id_rapor');
        $table->unsignedBigInteger('id_anggota');
        $table->unsignedBigInteger('id_semester');
        $table->decimal('rata_rata', 5, 2)->nullable();
        $table->integer('jumlah_sakit')->default(0);
        $table->integer('jumlah_izin')->default(0);
        $table->integer('jumlah_alpa')->default(0);
        $table->text('catatan_wali')->nullable();
        $table->string('status_rapor');
        $table->timestamp('tgl_dibuat')->nullable();
    });
    Schema::create('detail_rapor', function (Blueprint $table) {
        $table->id('id_detail_rapor');
        $table->unsignedBigInteger('id_rapor');
        $table->unsignedBigInteger('id_mapel');
        $table->unsignedBigInteger('id_predikat')->nullable();
        $table->decimal('nilai_akhir', 5, 2);
    });
    Schema::create('mata_pelajaran', function (Blueprint $table) {
        $table->id('id_mata_pelajaran');
        $table->string('nama_mapel');
    });
    Schema::create('skala_predikat', function (Blueprint $table) {
        $table->id('id_predikat');
        $table->string('huruf');
    });
    Schema::create('notifikasi', function (Blueprint $table) {
        $table->id('id_notifikasi');
        $table->unsignedBigInteger('id_pengguna');
        $table->boolean('dibaca')->default(false);
    });
    DB::statement('CREATE VIEW vw_nilai_rata_siswa AS SELECT NULL AS id_anggota, NULL AS id_semester, NULL AS nilai_rata_rata WHERE 0');

    DB::table('role')->insert(['id_role' => 1, 'nama_role' => 'siswa', 'kode_role' => 'siswa']);
    DB::table('pengguna')->insert([
        ['id_pengguna' => 1, 'id_role' => 1, 'username' => 'student-one', 'password_hash' => 'hash'],
        ['id_pengguna' => 2, 'id_role' => 1, 'username' => 'student-two', 'password_hash' => 'hash'],
    ]);
    DB::table('siswa')->insert([
        ['id_siswa' => 11, 'id_pengguna' => 1],
        ['id_siswa' => 22, 'id_pengguna' => 2],
    ]);
    DB::table('tahun_ajaran')->insert([
        'id_tahun_ajaran' => 1,
        'nama_tahun_ajaran' => '2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'is_aktif' => true,
    ]);
    DB::table('semester')->insert([
        'id_semester' => 1,
        'id_tahun_ajaran' => 1,
        'nama_semester' => 'Ganjil',
        'tanggal_mulai' => '2026-07-01',
    ]);
    DB::table('kelas')->insert(['id_kelas' => 1, 'nama_kelas' => 'X IPA 1']);
    DB::table('keanggotaan_kelas')->insert([
        ['id_keanggotaan' => 101, 'id_siswa' => 11, 'id_kelas' => 1, 'id_semester' => 1],
        ['id_keanggotaan' => 202, 'id_siswa' => 22, 'id_kelas' => 1, 'id_semester' => 1],
    ]);
    DB::table('rapor')->insert([
        [
            'id_rapor' => 100,
            'id_anggota' => 101,
            'id_semester' => 1,
            'catatan_wali' => 'Catatan untuk siswa pertama.',
            'status_rapor' => 'final',
            'tgl_dibuat' => '2026-11-01 10:00:00',
        ],
        [
            'id_rapor' => 200,
            'id_anggota' => 202,
            'id_semester' => 1,
            'catatan_wali' => 'Catatan untuk siswa kedua.',
            'status_rapor' => 'final',
            'tgl_dibuat' => '2026-11-01 10:00:00',
        ],
    ]);
});

it('shows only the signed-in student report and omits its internal status', function () {
    $this->withSession([
        'id_pengguna' => 1,
        'username' => 'student-one',
        'role' => 'siswa',
    ])->get(route('siswa.rapor', ['id' => 100]))
        ->assertOk()
        ->assertSee('Catatan untuk siswa pertama.')
        ->assertDontSee('Catatan untuk siswa kedua.')
        ->assertDontSee('Final');
});

it('returns not found when a student requests another student report id', function () {
    $this->withSession([
        'id_pengguna' => 1,
        'username' => 'student-one',
        'role' => 'siswa',
    ])->get(route('siswa.rapor', ['id' => 200]))
        ->assertNotFound();
});
