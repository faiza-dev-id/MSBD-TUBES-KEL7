<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('periode_update');
    Schema::dropIfExists('tahun_ajaran');
    Schema::enableForeignKeyConstraints();

    Schema::create('tahun_ajaran', function (Blueprint $table): void {
        $table->id('id_tahun_ajaran');
        $table->string('nama_tahun_ajaran');
        $table->date('tanggal_mulai');
        $table->date('tanggal_selesai');
        $table->boolean('is_aktif');
    });
    Schema::create('periode_update', function (Blueprint $table): void {
        $table->id('id_periode_update');
        $table->unsignedBigInteger('id_tahun_ajaran');
        $table->string('nama_periode');
        $table->date('tanggal_mulai');
        $table->date('tanggal_selesai');
        $table->string('status');
        $table->unsignedBigInteger('dibuat_oleh')->nullable();
    });
});

it('shows future academic years in the TU period dropdown', function (): void {
    DB::table('tahun_ajaran')->insert([
        [
            'id_tahun_ajaran' => 1,
            'nama_tahun_ajaran' => '2027/2028',
            'tanggal_mulai' => '2027-07-01',
            'tanggal_selesai' => '2028-06-30',
            'is_aktif' => false,
        ],
        [
            'id_tahun_ajaran' => 2,
            'nama_tahun_ajaran' => '2028/2029',
            'tanggal_mulai' => '2028-07-01',
            'tanggal_selesai' => '2029-06-30',
            'is_aktif' => false,
        ],
    ]);

    $response = $this->withoutMiddleware([EnsureRole::class])->get(route('tu.periode'));

    $response->assertOk()
        ->assertSee('2027/2028')
        ->assertSee('2028/2029')
        ->assertSee('value="1"', false)
        ->assertSee('value="2"', false);
});

it('shows an empty state instead of fabricated academic years', function (): void {
    $response = $this->withoutMiddleware([EnsureRole::class])->get(route('tu.periode'));

    $response->assertOk()
        ->assertSee('Tambahkan tahun ajaran terlebih dahulu')
        ->assertDontSee('2026/2027')
        ->assertDontSee('2027/2028');
});

it('accepts a future academic year selected in the period form', function (): void {
    DB::table('tahun_ajaran')->insert([
        'id_tahun_ajaran' => 1,
        'nama_tahun_ajaran' => '2027/2028',
        'tanggal_mulai' => '2027-07-01',
        'tanggal_selesai' => '2028-06-30',
        'is_aktif' => false,
    ]);

    $response = $this->withoutMiddleware([EnsureRole::class])
        ->post(route('tu.periode.simpan'), [
            'id_tahun_ajaran' => 1,
            'tgl_buka' => '2027-07-01',
            'tgl_tutup' => '2027-07-31',
        ]);

    $response->assertRedirect(route('tu.periode'));
    $this->assertDatabaseHas('periode_update', [
        'id_tahun_ajaran' => 1,
        'nama_periode' => 'Update Data 2027/2028',
        'status' => 'aktif',
    ]);
});
