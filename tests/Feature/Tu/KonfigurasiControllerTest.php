<?php

use App\Http\Middleware\EnsureRole;
use Database\Seeders\MasterPenilaianSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropIfExists('log_audit');
    Schema::dropIfExists('detail_rapor');
    Schema::dropIfExists('skala_predikat');
    Schema::dropIfExists('komponen_penilaian');
    Schema::dropIfExists('mata_pelajaran');
    Schema::dropIfExists('tahun_ajaran');

    Schema::create('tahun_ajaran', function (Blueprint $table): void {
        $table->id('id_tahun_ajaran');
        $table->string('nama_tahun_ajaran');
        $table->date('tanggal_mulai');
        $table->date('tanggal_selesai');
        $table->boolean('is_aktif')->default(false);
    });

    Schema::create('mata_pelajaran', function (Blueprint $table): void {
        $table->id('id_mata_pelajaran');
        $table->string('kode_mapel')->nullable();
        $table->string('nama_mapel');
        $table->boolean('is_aktif')->default(true);
    });

    Schema::create('komponen_penilaian', function (Blueprint $table): void {
        $table->id('id_komponen');
        $table->string('nama_komponen')->unique();
        $table->decimal('bobot_persen', 5, 2);
    });

    Schema::create('skala_predikat', function (Blueprint $table): void {
        $table->id('id_predikat');
        $table->string('huruf', 5)->unique();
        $table->decimal('nilai_min', 5, 2);
        $table->decimal('nilai_maks', 5, 2);
        $table->string('deskripsi')->nullable();
    });

    Schema::create('detail_rapor', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('id_predikat')->nullable();
    });

    Schema::create('nilai_siswa', function (Blueprint $table): void {
        $table->id('id_nilai');
        $table->unsignedBigInteger('id_komponen');
    });

    Schema::create('log_audit', function (Blueprint $table): void {
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
});

it('shows assessment component and predicate configuration tabs', function (): void {
    $this->withoutMiddleware([EnsureRole::class])
        ->get(route('akademik.konfigurasi'))
        ->assertOk()
        ->assertSee('Tahun Ajaran')
        ->assertSee('Semester')
        ->assertSee('Kelas')
        ->assertSee('Mata Pelajaran')
        ->assertSee('Komponen Penilaian')
        ->assertSee('Skala Predikat');

    $this->withoutMiddleware([EnsureRole::class])
        ->get(route('akademik.konfigurasi', 'mapel'))
        ->assertOk()
        ->assertSee('Mata Pelajaran');

    $this->withoutMiddleware([EnsureRole::class])
        ->get(route('akademik.konfigurasi', 'komponen'))
        ->assertOk()
        ->assertSee('Komponen Penilaian');

    $this->withoutMiddleware([EnsureRole::class])
        ->get(route('akademik.konfigurasi', 'predikat'))
        ->assertOk()
        ->assertSee('Simpan skala');
});

it('prevents assessment component weights from exceeding one hundred percent', function (): void {
    $this->withoutMiddleware([EnsureRole::class])
        ->post(route('akademik.konfigurasi.simpan', 'komponen'), [
            'nama_komponen' => 'Tugas',
            'bobot_persen' => 60,
        ])
        ->assertRedirect();

    $this->withoutMiddleware([EnsureRole::class])
        ->post(route('akademik.konfigurasi.simpan', 'komponen'), [
            'nama_komponen' => 'Ujian',
            'bobot_persen' => 40.01,
        ])
        ->assertSessionHasErrors('bobot_persen');

    $this->assertDatabaseCount('komponen_penilaian', 1);
});

it('supports component create and update while blocking deletion when a component is used', function (): void {
    $this->withoutMiddleware([EnsureRole::class])
        ->post(route('akademik.konfigurasi.simpan', 'komponen'), [
            'nama_komponen' => 'Tugas',
            'bobot_persen' => 60,
        ])
        ->assertRedirect();
    $idKomponen = (int) DB::table('komponen_penilaian')->value('id_komponen');

    $this->withoutMiddleware([EnsureRole::class])
        ->put(route('akademik.konfigurasi.ubah', ['komponen', $idKomponen]), [
            'nama_komponen' => 'Tugas Harian',
            'bobot_persen' => 60,
        ])
        ->assertRedirect();

    DB::table('nilai_siswa')->insert(['id_komponen' => $idKomponen]);

    $this->withoutMiddleware([EnsureRole::class])
        ->delete(route('akademik.konfigurasi.hapus', ['komponen', $idKomponen]))
        ->assertSessionHas('galat');

    $this->assertDatabaseHas('komponen_penilaian', ['id_komponen' => $idKomponen, 'nama_komponen' => 'Tugas Harian']);

    DB::table('nilai_siswa')->delete();
    $this->withoutMiddleware([EnsureRole::class])
        ->delete(route('akademik.konfigurasi.hapus', ['komponen', $idKomponen]))
        ->assertRedirect();
    $this->assertDatabaseMissing('komponen_penilaian', ['id_komponen' => $idKomponen]);
});

it('saves only complete non-overlapping predicate ranges from zero to one hundred', function (): void {
    $rows = [
        ['huruf' => 'D', 'nilai_min' => 0, 'nilai_maks' => 59.99, 'deskripsi' => 'Perlu bimbingan'],
        ['huruf' => 'C', 'nilai_min' => 60, 'nilai_maks' => 74.99, 'deskripsi' => 'Cukup'],
        ['huruf' => 'B', 'nilai_min' => 75, 'nilai_maks' => 89.99, 'deskripsi' => 'Baik'],
        ['huruf' => 'A', 'nilai_min' => 90, 'nilai_maks' => 100, 'deskripsi' => 'Sangat baik'],
    ];

    $this->withoutMiddleware([EnsureRole::class])
        ->put(route('akademik.konfigurasi.predikat.simpan'), ['rentang' => $rows])
        ->assertRedirect(route('akademik.konfigurasi', 'predikat'));

    $this->assertDatabaseCount('skala_predikat', 4);

    foreach (DB::table('skala_predikat')->orderBy('nilai_min')->get() as $i => $predikat) {
        $rows[$i]['id_predikat'] = $predikat->id_predikat;
    }
    $rows[3]['deskripsi'] = 'Sangat baik sekali';
    $this->withoutMiddleware([EnsureRole::class])
        ->put(route('akademik.konfigurasi.predikat.simpan'), ['rentang' => $rows])
        ->assertRedirect(route('akademik.konfigurasi', 'predikat'));

    $rows[1]['nilai_min'] = 60.01;
    $this->withoutMiddleware([EnsureRole::class])
        ->put(route('akademik.konfigurasi.predikat.simpan'), ['rentang' => $rows])
        ->assertSessionHasErrors('nilai_min');

    $this->assertDatabaseHas('skala_predikat', ['huruf' => 'C', 'nilai_min' => 60]);
    $this->assertDatabaseHas('skala_predikat', ['huruf' => 'A', 'deskripsi' => 'Sangat baik sekali']);

    $rentangHapus = DB::table('skala_predikat')->orderBy('nilai_min')->get();
    $payloadHapus = [];
    foreach ($rentangHapus as $predikat) {
        $payloadHapus[] = [
            'id_predikat' => $predikat->id_predikat,
            'huruf' => $predikat->huruf,
            'nilai_min' => $predikat->huruf === 'C' ? 0 : $predikat->nilai_min,
            'nilai_maks' => $predikat->huruf === 'C' ? 74.99 : $predikat->nilai_maks,
            'deskripsi' => $predikat->deskripsi,
            'hapus' => $predikat->huruf === 'D' ? 1 : 0,
        ];
    }

    $this->withoutMiddleware([EnsureRole::class])
        ->put(route('akademik.konfigurasi.predikat.simpan'), ['rentang' => $payloadHapus])
        ->assertRedirect(route('akademik.konfigurasi', 'predikat'));
    $this->assertDatabaseCount('skala_predikat', 3);
});

it('seeds default assessment masters only when their tables are empty', function (): void {
    (new MasterPenilaianSeeder())->run();

    $this->assertDatabaseCount('komponen_penilaian', 4);
    $this->assertDatabaseCount('skala_predikat', 4);
    $this->assertSame(100.0, (float) DB::table('komponen_penilaian')->sum('bobot_persen'));

    DB::table('komponen_penilaian')->where('nama_komponen', 'Tugas')->update(['bobot_persen' => 10]);
    (new MasterPenilaianSeeder())->run();

    $this->assertDatabaseCount('komponen_penilaian', 4);
    $this->assertSame(10.0, (float) DB::table('komponen_penilaian')->where('nama_komponen', 'Tugas')->value('bobot_persen'));
});

it('refuses to delete a predicate already referenced by a report', function (): void {
    (new MasterPenilaianSeeder())->run();
    $predikatId = (int) DB::table('skala_predikat')->where('huruf', 'D')->value('id_predikat');
    DB::table('detail_rapor')->insert(['id_predikat' => $predikatId]);
    $rentang = DB::table('skala_predikat')->orderBy('nilai_min')->get();
    $payload = [];

    foreach ($rentang as $item) {
        $payload[] = [
            'id_predikat' => $item->id_predikat,
            'huruf' => $item->huruf,
            'nilai_min' => $item->huruf === 'C' ? 0 : $item->nilai_min,
            'nilai_maks' => $item->nilai_maks,
            'deskripsi' => $item->deskripsi,
            'hapus' => $item->huruf === 'D' ? 1 : 0,
        ];
    }

    $this->withoutMiddleware([EnsureRole::class])
        ->put(route('akademik.konfigurasi.predikat.simpan'), ['rentang' => $payload])
        ->assertSessionHasErrors('rentang');

    $this->assertDatabaseCount('skala_predikat', 4);
});
