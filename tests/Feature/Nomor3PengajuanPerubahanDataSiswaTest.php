<?php

namespace Tests\Feature;

use App\Http\Controllers\AksiFormController;
use App\Http\Controllers\Api\PengajuanPerubahanController;
use App\Http\Middleware\EnsureRole;
use App\Http\Requests\StorePengajuanPerubahanRequest;
use App\Models\DetailPengajuan;
use App\Models\PengajuanPerubahan;
use App\Services\PengajuanPerubahanService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Nomor3PengajuanPerubahanDataSiswaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createNomor3Schema();
        $this->seedNomor3Reference();
        $this->seedNomor3Data();
        Cache::forget('pengaturan_sistem');
    }

    protected function createNomor3Schema(): void
    {
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('detail_pengajuan');
        Schema::dropIfExists('pengajuan_perubahan');
        Schema::dropIfExists('pengaturan_sistem');
        Schema::dropIfExists('ref_field_data');
        Schema::dropIfExists('alamat_siswa');
        Schema::dropIfExists('orang_tua_wali');
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('periode_update');
        Schema::dropIfExists('role');
        Schema::dropIfExists('pengguna');

        Schema::create('role', function (Blueprint $table) {
            $table->id('id_role');
            $table->string('kode_role');
            $table->string('nama_role');
        });

        Schema::create('pengguna', function (Blueprint $table) {
            $table->id('id_pengguna');
            $table->unsignedBigInteger('id_role')->nullable();
            $table->string('username', 100)->nullable()->unique();
            $table->boolean('is_aktif')->default(true);
        });

        Schema::create('periode_update', function (Blueprint $table) {
            $table->id('id_periode_update');
            $table->string('nama_periode', 120);
            $table->string('status', 20)->default('menunggu');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedBigInteger('id_tahun_ajaran')->nullable();
        });

        Schema::create('pengaturan_sistem', function (Blueprint $table) {
            $table->id('id_pengaturan');
            $table->string('kunci', 120)->unique();
            $table->text('nilai')->nullable();
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->id('id_siswa');
            $table->unsignedBigInteger('id_pengguna')->nullable();
            $table->string('nama_lengkap', 160);
            $table->string('nisn', 30)->nullable();
            $table->string('nik', 30)->nullable();
            $table->string('jenis_kelamin', 2)->nullable();
            $table->string('tempat_lahir', 120)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('email', 160)->nullable();
            $table->string('no_telepon', 40)->nullable();
            $table->timestamp('diubah_pada')->nullable();
        });

        Schema::create('ref_field_data', function (Blueprint $table) {
            $table->id('id_field');
            $table->string('nama_tabel', 60);
            $table->string('nama_kolom', 80);
            $table->string('label_field', 180);
            $table->boolean('boleh_diubah_siswa')->default(false);
            $table->boolean('perlu_bukti')->default(false);
        });

        Schema::create('pengajuan_perubahan', function (Blueprint $table) {
            $table->id('id_pengajuan');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_periode_update');
            $table->string('status_pengajuan', 30)->default('menunggu');
            $table->text('alasan')->nullable();
            $table->unsignedBigInteger('id_pemeriksa')->nullable();
            $table->text('catatan_pemeriksa')->nullable();
            $table->timestamp('tgl_pengajuan')->useCurrent();
            $table->timestamp('diputuskan_pada')->nullable();
        });

        Schema::create('detail_pengajuan', function (Blueprint $table) {
            $table->id('id_detail_pengajuan');
            $table->unsignedBigInteger('id_pengajuan');
            $table->string('nama_field', 100);
            $table->json('nilai_lama')->nullable();
            $table->json('nilai_baru')->nullable();
            $table->string('bukti_path', 500)->nullable();
        });

        Schema::create('alamat_siswa', function (Blueprint $table) {
            $table->id('id_alamat');
            $table->unsignedBigInteger('id_siswa');
            $table->text('alamat')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('kode_pos', 10)->nullable();
        });

        Schema::create('orang_tua_wali', function (Blueprint $table) {
            $table->id('id_orang_tua_wali');
            $table->unsignedBigInteger('id_siswa');
            $table->string('hubungan', 20);
            $table->string('nama_lengkap', 160)->nullable();
            $table->string('no_telepon', 40)->nullable();
            $table->string('email', 160)->nullable();
            $table->text('alamat')->nullable();
        });

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->unsignedBigInteger('id_pengguna');
            $table->string('judul', 200);
            $table->text('pesan');
            $table->boolean('dibaca')->default(false);
            $table->timestamp('dibuat_pada')->useCurrent();
        });
    }

    protected function seedNomor3Reference(): void
    {
        DB::table('role')->insert([
            ['id_role' => 1, 'kode_role' => 'siswa', 'nama_role' => 'siswa'],
            ['id_role' => 2, 'kode_role' => 'wali_kelas', 'nama_role' => 'wali_kelas'],
            ['id_role' => 3, 'kode_role' => 'tu', 'nama_role' => 'tu'],
        ]);

        DB::table('ref_field_data')->insert([
            ['id_field' => 1, 'nama_tabel' => 'siswa', 'nama_kolom' => 'nama_lengkap', 'label_field' => 'Nama lengkap', 'boleh_diubah_siswa' => true, 'perlu_bukti' => true],
            ['id_field' => 2, 'nama_tabel' => 'siswa', 'nama_kolom' => 'nik', 'label_field' => 'NIK', 'boleh_diubah_siswa' => true, 'perlu_bukti' => true],
            ['id_field' => 3, 'nama_tabel' => 'siswa', 'nama_kolom' => 'jenis_kelamin', 'label_field' => 'Jenis kelamin', 'boleh_diubah_siswa' => false, 'perlu_bukti' => false],
            ['id_field' => 4, 'nama_tabel' => 'alamat_siswa', 'nama_kolom' => 'alamat', 'label_field' => 'Alamat', 'boleh_diubah_siswa' => true, 'perlu_bukti' => true],
        ]);
    }

    protected function seedNomor3Data(): void
    {
        DB::table('pengguna')->insert([
            ['id_pengguna' => 10, 'id_role' => 1, 'username' => 'siswaA', 'is_aktif' => true],
            ['id_pengguna' => 11, 'id_role' => 1, 'username' => 'siswaB', 'is_aktif' => true],
            ['id_pengguna' => 20, 'id_role' => 3, 'username' => 'tu01', 'is_aktif' => true],
        ]);

        DB::table('periode_update')->insert([
            ['id_periode_update' => 1, 'nama_periode' => 'Periode 2026', 'status' => 'aktif', 'tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-12-31'],
            ['id_periode_update' => 2, 'nama_periode' => 'Periode 2027', 'status' => 'ditutup', 'tanggal_mulai' => '2027-01-01', 'tanggal_selesai' => '2027-12-31'],
        ]);

        DB::table('siswa')->insert([
            ['id_siswa' => 1, 'id_pengguna' => 10, 'nama_lengkap' => 'Siswa A', 'nisn' => 'NISN-001', 'nik' => '3201010101010001', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '2008-05-01', 'email' => 'a@example.com', 'no_telepon' => '0811111111'],
            ['id_siswa' => 2, 'id_pengguna' => 11, 'nama_lengkap' => 'Siswa B', 'nisn' => 'NISN-002', 'nik' => '3201010101010002', 'jenis_kelamin' => 'P', 'tempat_lahir' => 'Jakarta', 'tanggal_lahir' => '2008-06-02', 'email' => 'b@example.com', 'no_telepon' => '0822222222'],
        ]);
    }

    private function prepareStudentProfileSchema(): void
    {
        DB::table('pengguna')->where('id_pengguna', 10)->update([
            'username' => 'siswaA',
            'id_role' => 1,
        ]);

        Schema::table('siswa', function (Blueprint $table) {
            $table->string('nis', 30)->nullable();
            $table->unsignedBigInteger('id_agama')->nullable();
            $table->unsignedBigInteger('id_sekolah_asal')->nullable();
            $table->unsignedBigInteger('id_status_siswa')->nullable();
        });
        Schema::create('agama', function (Blueprint $table) {
            $table->id('id_agama');
            $table->string('nama_agama');
        });
        Schema::create('status_siswa', function (Blueprint $table) {
            $table->id('id_status_siswa');
            $table->string('nama_status');
        });
        DB::table('status_siswa')->insert([
            'id_status_siswa' => 1,
            'nama_status' => 'Aktif',
        ]);
        DB::table('siswa')->where('id_siswa', 1)->update([
            'nis' => 'NIS-001',
            'id_status_siswa' => 1,
        ]);

        Schema::create('kelas', function (Blueprint $table) {
            $table->id('id_kelas');
            $table->string('nama_kelas');
        });
        Schema::create('keanggotaan_kelas', function (Blueprint $table) {
            $table->id('id_keanggotaan');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_kelas');
        });
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->id('id_tahun_ajaran');
            $table->string('nama_tahun_ajaran');
            $table->boolean('is_aktif')->default(false);
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
        Schema::create('pendaftaran_ppdb', function (Blueprint $table) {
            $table->id('id_pendaftaran');
            $table->string('nomor_pendaftaran')->nullable();
            $table->timestamp('diajukan_pada')->nullable();
            $table->string('nama_calon_siswa')->nullable();
            $table->string('nisn')->nullable();
            $table->string('nis')->nullable();
            $table->string('sekolah_asal')->nullable();
            $table->string('nik')->nullable();
            $table->string('no_kk')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('no_telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('tahun_pelajaran')->nullable();
            $table->string('pendaftaran_untuk')->nullable();
            $table->string('jenjang_tujuan')->nullable();
            $table->string('kelas_tujuan')->nullable();
            $table->unsignedBigInteger('id_agama')->nullable();
            $table->boolean('persetujuan_ortu')->default(false);
            $table->boolean('pernyataan')->default(false);
            $table->unsignedSmallInteger('langkah_terakhir')->default(0);
        });
        Schema::create('ppdb_alamat', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pendaftaran');
            $table->string('jalan')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('dusun')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('telp')->nullable();
            $table->string('no_hp')->nullable();
        });
        Schema::create('ppdb_periodik', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pendaftaran');
            $table->string('tinggi_badan')->nullable();
            $table->string('berat_badan')->nullable();
            $table->string('gol_darah')->nullable();
            $table->string('jarak')->nullable();
            $table->string('jarak_km')->nullable();
            $table->string('waktu_tempuh_jam')->nullable();
            $table->string('waktu_tempuh_menit')->nullable();
            $table->string('jumlah_saudara')->nullable();
        });
        Schema::create('ppdb_orang_tua', function (Blueprint $table) {
            $table->id('id_orang_tua');
            $table->unsignedBigInteger('id_pendaftaran');
            $table->string('hubungan');
            $table->string('nama_lengkap')->nullable();
            $table->string('nik')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('pendidikan')->nullable();
            $table->unsignedBigInteger('id_pekerjaan')->nullable();
            $table->string('penghasilan')->nullable();
            $table->string('berkebutuhan_khusus')->nullable();
            $table->string('no_telepon')->nullable();
            $table->text('alamat')->nullable();
        });
        Schema::create('ppdb_prestasi', function (Blueprint $table) {
            $table->id('id_prestasi');
            $table->unsignedBigInteger('id_pendaftaran');
            $table->string('jenis')->nullable();
            $table->string('tingkat')->nullable();
            $table->string('nama_prestasi')->nullable();
            $table->string('tahun')->nullable();
            $table->string('penyelenggara')->nullable();
        });
        Schema::create('ppdb_beasiswa', function (Blueprint $table) {
            $table->id('id_beasiswa');
            $table->unsignedBigInteger('id_pendaftaran');
            $table->string('jenis')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('tahun_mulai')->nullable();
            $table->string('tahun_selesai')->nullable();
        });
        Schema::create('ppdb_keluar', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pendaftaran');
            $table->string('keluar_karena')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->text('alasan')->nullable();
        });
    }

    public function test_profil_siswa_menampilkan_data_ppdb_keluarga_dan_dokumen(): void
    {
        $this->prepareStudentProfileSchema();
        DB::table('siswa')->where('id_siswa', 1)->update([
            'email' => 'email.terbaru@example.com',
        ]);
        DB::table('alamat_siswa')->insert([
            'id_siswa' => 1,
            'alamat' => 'Alamat master terbaru',
            'rt' => '05',
            'rw' => '02',
            'kelurahan' => 'Kelurahan master',
            'kecamatan' => 'Kecamatan master',
            'kota' => 'Kota master',
            'provinsi' => 'Provinsi master',
            'kode_pos' => '12345',
        ]);
        DB::table('orang_tua_wali')->insert([
            ['id_siswa' => 1, 'hubungan' => 'ayah', 'nama_lengkap' => 'Ayah master', 'no_telepon' => '08123456789', 'email' => 'ayah.master@example.com', 'alamat' => 'Alamat ayah master'],
            ['id_siswa' => 1, 'hubungan' => 'ibu', 'nama_lengkap' => 'Ibu master', 'no_telepon' => '08123456780', 'email' => 'ibu.master@example.com', 'alamat' => 'Alamat ibu master'],
            ['id_siswa' => 1, 'hubungan' => 'wali', 'nama_lengkap' => 'Wali master', 'no_telepon' => '08123456781', 'email' => 'wali.master@example.com', 'alamat' => 'Alamat wali master'],
        ]);
        DB::table('pendaftaran_ppdb')->insert([
            'id_pendaftaran' => 1,
            'nomor_pendaftaran' => 'PPDB-PROFIL-001',
            'diajukan_pada' => '2025-06-01',
            'nama_calon_siswa' => 'Nama PPDB Siswa A',
            'nisn' => 'NISN-001',
            'nis' => 'NIS-001',
            'sekolah_asal' => 'SMP Asal Siswa A',
            'nik' => '3201010101010001',
            'tahun_pelajaran' => '2025/2026',
            'jenjang_tujuan' => 'SMA',
            'kelas_tujuan' => 'X MIA 1',
        ]);
        DB::table('ppdb_alamat')->insert([
            'id_pendaftaran' => 1,
            'jalan' => 'Jalan PPDB Siswa A',
            'kota' => 'Kota PPDB',
        ]);
        DB::table('ppdb_periodik')->insert([
            'id_pendaftaran' => 1,
            'tinggi_badan' => '165',
            'berat_badan' => '55',
        ]);
        DB::table('ppdb_orang_tua')->insert([
            ['id_pendaftaran' => 1, 'hubungan' => 'ayah', 'nama_lengkap' => 'Ayah PPDB Siswa A'],
            ['id_pendaftaran' => 1, 'hubungan' => 'ibu', 'nama_lengkap' => 'Ibu PPDB Siswa A'],
        ]);
        DB::table('ppdb_prestasi')->insert([
            'id_pendaftaran' => 1,
            'nama_prestasi' => 'Prestasi PPDB Siswa A',
        ]);
        DB::table('ppdb_beasiswa')->insert([
            'id_pendaftaran' => 1,
            'keterangan' => 'Beasiswa PPDB Siswa A',
        ]);
        DB::table('dokumen_siswa')->insert([
            'id_siswa' => 1,
            'id_pendaftaran' => 1,
            'jenis_dokumen' => 'Akta Kelahiran',
            'nama_file_asli' => 'akta-siswa-a.pdf',
            'status_verifikasi' => 'valid',
            'diunggah_pada' => '2025-06-02',
        ]);

        $response = $this->withSession([
            'id_pengguna' => 10,
            'username' => 'siswaA',
        ])->get('/siswa/profil');

        $response->assertOk()
            ->assertSee('Nama PPDB Siswa A')
            ->assertSee('Nomor pendaftaran')
            ->assertSee('Jalan PPDB Siswa A')
            ->assertSee('Ayah PPDB Siswa A')
            ->assertSee('Ibu PPDB Siswa A')
            ->assertSee('email.terbaru@example.com')
            ->assertSee('Kelurahan master')
            ->assertDontSee('ayah.master@example.com')
            ->assertDontSee('ibu.master@example.com')
            ->assertSee('wali.master@example.com')
            ->assertSee('Prestasi PPDB Siswa A')
            ->assertSee('Beasiswa PPDB Siswa A')
            ->assertSee('akta-siswa-a.pdf');
    }

    public function test_profil_siswa_menjelaskan_jika_data_ppdb_belum_terhubung(): void
    {
        $this->prepareStudentProfileSchema();

        $response = $this->withSession([
            'id_pengguna' => 10,
            'username' => 'siswaA',
        ])->get('/siswa/profil');

        $response->assertOk()
            ->assertSee('Data formulir PPDB belum terhubung ke akun ini.')
            ->assertSee('Belum ada dokumen PPDB yang tercatat.');
    }

    public function test_siswa_dapat_mengajukan_perubahan_field_yang_diizinkan(): void
    {
        $hasil = (new PengajuanPerubahanService)->ajukan(
            1,
            1,
            'Perbaikan data nama',
            [[
                'nama_field' => 'nama_lengkap',
                'nilai_lama' => 'Siswa A',
                'nilai_baru' => 'Siswa A Putra',
                'bukti_path' => 'bukti/1.pdf',
            ]]
        );

        $this->assertInstanceOf(PengajuanPerubahan::class, $hasil);
        $this->assertSame('menunggu', $hasil->status_pengajuan);
        $this->assertDatabaseHas('pengajuan_perubahan', ['id_siswa' => 1, 'status_pengajuan' => 'menunggu']);
        $this->assertDatabaseHas('detail_pengajuan', ['id_pengajuan' => $hasil->id_pengajuan, 'nama_field' => 'nama_lengkap']);
        $this->assertSame('Siswa A', DB::table('siswa')->where('id_siswa', 1)->value('nama_lengkap'));
    }

    public function test_siswa_dapat_mengirim_pengajuan_perubahan_email_dari_form(): void
    {
        DB::table('ref_field_data')->insert([
            'id_field' => 5,
            'nama_tabel' => 'siswa',
            'nama_kolom' => 'email',
            'label_field' => 'Email',
            'boleh_diubah_siswa' => true,
            'perlu_bukti' => false,
        ]);

        $this->withSession([
            'id_pengguna' => 10,
            'username' => 'siswaA',
            'role' => 'siswa',
            'nama' => 'Siswa A',
        ])->post(route('siswa.pengajuan.simpan'), [
            'id_field' => 5,
            'nilai_baru' => 'siswa.baru@example.test',
        ])->assertRedirect(route('siswa.pengajuan'))
            ->assertSessionHas('success', 'Pengajuan perubahan berhasil dikirim dan menunggu validasi wali kelas.');

        $idPengajuan = DB::table('pengajuan_perubahan')
            ->where('id_siswa', 1)
            ->value('id_pengajuan');

        $this->assertDatabaseHas('pengajuan_perubahan', [
            'id_pengajuan' => $idPengajuan,
            'status_pengajuan' => 'menunggu',
        ]);
        $this->assertDatabaseHas('detail_pengajuan', [
            'id_pengajuan' => $idPengajuan,
            'nama_field' => 'email',
        ]);

        $detail = DB::table('detail_pengajuan')
            ->where('id_pengajuan', $idPengajuan)
            ->first();

        $this->assertSame(json_encode('a@example.com', JSON_THROW_ON_ERROR), $detail->nilai_lama);
        $this->assertSame(json_encode('siswa.baru@example.test', JSON_THROW_ON_ERROR), $detail->nilai_baru);

        $detailModel = DetailPengajuan::query()->where('id_pengajuan', $idPengajuan)->firstOrFail();

        $this->assertSame('a@example.com', $detailModel->nilai_lama);
        $this->assertSame('siswa.baru@example.test', $detailModel->nilai_baru);
    }

    public function test_siswa_dapat_mengajukan_perubahan_alamat_dengan_bukti(): void
    {
        Storage::fake('private');
        DB::table('alamat_siswa')->insert([
            'id_siswa' => 1,
            'alamat' => 'Alamat lama',
        ]);
        $bukti = UploadedFile::fake()->createWithContent(
            'kartu-keluarga.pdf',
            "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF",
            'application/pdf'
        );

        $this->withSession([
            'id_pengguna' => 10,
            'username' => 'siswaA',
            'role' => 'siswa',
            'nama' => 'Siswa A',
        ])->post(route('siswa.pengajuan.simpan'), [
            'id_field' => 4,
            'nilai_baru' => 'Alamat baru',
            'bukti' => $bukti,
        ])->assertRedirect(route('siswa.pengajuan'))
            ->assertSessionHas('success', 'Pengajuan perubahan berhasil dikirim dan menunggu validasi wali kelas.');

        $detail = DB::table('detail_pengajuan')->first();

        $this->assertDatabaseHas('detail_pengajuan', [
            'id_pengajuan' => $detail->id_pengajuan,
            'nama_field' => 'alamat',
        ]);
        Storage::disk('private')->assertExists($detail->bukti_path);
    }

    public function test_siswa_mendapat_pesan_jika_field_yang_dipilih_mewajibkan_bukti(): void
    {
        $this->from(route('siswa.pengajuan'))
            ->withSession([
                'id_pengguna' => 10,
                'username' => 'siswaA',
                'role' => 'siswa',
                'nama' => 'Siswa A',
            ])->post(route('siswa.pengajuan.simpan'), [
                'id_field' => 4,
                'nilai_baru' => 'Alamat baru',
            ])->assertRedirect(route('siswa.pengajuan'))
            ->assertSessionHasErrors([
                'bukti' => 'Bukti pendukung wajib dilampirkan untuk perubahan data ini.',
            ]);

        $this->assertDatabaseCount('pengajuan_perubahan', 0);
    }

    public function test_siswa_dapat_mengajukan_perubahan_data_orang_tua(): void
    {
        DB::table('ref_field_data')->insert([
            'id_field' => 5,
            'nama_tabel' => 'orang_tua_wali',
            'nama_kolom' => 'ayah_email',
            'label_field' => 'Email ayah',
            'boleh_diubah_siswa' => true,
            'perlu_bukti' => false,
        ]);
        DB::table('orang_tua_wali')->insert([
            'id_siswa' => 1,
            'hubungan' => 'ayah',
            'nama_lengkap' => 'Ayah Siswa A',
            'email' => 'ayah.lama@example.test',
        ]);

        $this->withSession([
            'id_pengguna' => 10,
            'username' => 'siswaA',
            'role' => 'siswa',
            'nama' => 'Siswa A',
        ])->post(route('siswa.pengajuan.simpan'), [
            'id_field' => 5,
            'nilai_baru' => 'ayah.baru@example.test',
        ])->assertRedirect(route('siswa.pengajuan'))
            ->assertSessionHas('success', 'Pengajuan perubahan berhasil dikirim dan menunggu validasi wali kelas.');

        $this->assertDatabaseHas('detail_pengajuan', [
            'nama_field' => 'ayah_email',
            'nilai_lama' => json_encode('ayah.lama@example.test', JSON_THROW_ON_ERROR),
            'nilai_baru' => json_encode('ayah.baru@example.test', JSON_THROW_ON_ERROR),
        ]);
    }

    public function test_siswa_dapat_mengajukan_nilai_untuk_field_yang_sebelumnya_kosong(): void
    {
        DB::table('siswa')->where('id_siswa', 1)->update(['nik' => null]);

        $hasil = (new PengajuanPerubahanService)->ajukan(
            1,
            1,
            'Melengkapi NIK',
            [[
                'nama_field' => 'nik',
                'nilai_lama' => null,
                'nilai_baru' => '3201010101010009',
            ]]
        );

        $this->assertSame('menunggu', $hasil->status_pengajuan);
        $this->assertDatabaseHas('detail_pengajuan', [
            'id_pengajuan' => $hasil->id_pengajuan,
            'nama_field' => 'nik',
        ]);

        $detail = DetailPengajuan::query()->where('id_pengajuan', $hasil->id_pengajuan)->firstOrFail();

        $this->assertNull($detail->nilai_lama);
        $this->assertSame('3201010101010009', $detail->nilai_baru);
        $this->assertNull(DB::table('siswa')->where('id_siswa', 1)->value('nik'));
    }

    public function test_field_yang_tidak_diizinkan_atau_tidak_boleh_diubah_ditolak(): void
    {
        $this->expectException(ValidationException::class);
        (new PengajuanPerubahanService)->ajukan(
            1,
            1,
            'Field tidak valid',
            [[
                'nama_field' => 'jenis_kelamin',
                'nilai_lama' => 'L',
                'nilai_baru' => 'P',
            ]]
        );
    }

    public function test_pengajuan_tanpa_detail_perubahan_seharusnya_ditolak(): void
    {
        $this->expectException(ValidationException::class);

        (new PengajuanPerubahanService)->ajukan(
            1,
            1,
            'Tidak ada detail',
            []
        );
    }

    public function test_input_tidak_valid_tidak_menghasilkan_pengajuan_sebagian(): void
    {
        $before = DB::table('pengajuan_perubahan')->count();

        try {
            (new PengajuanPerubahanService)->ajukan(
                1,
                1,
                'Field invalid',
                [[
                    'nama_field' => 'kolom_tidak_ada',
                    'nilai_lama' => 'lama',
                    'nilai_baru' => 'baru',
                ]]
            );
            $this->fail('Seharusnya menolak field yang tidak dikenali.');
        } catch (ValidationException $e) {
            $this->assertSame($before, DB::table('pengajuan_perubahan')->count());
            $this->assertSame(0, DB::table('detail_pengajuan')->count());
        }
    }

    public function test_role_route_terproteksi_dengan_middleware_yang_benar(): void
    {
        $siswaRoute = app('router')->getRoutes()->getByName('siswa.pengajuan.simpan');
        $guruRoute = app('router')->getRoutes()->getByName('guru.pengajuan.simpan');
        $tuRoute = app('router')->getRoutes()->getByName('tu.persetujuan.simpan');

        $this->assertNotNull($siswaRoute);
        $this->assertNotNull($guruRoute);
        $this->assertNull($tuRoute);
        $this->assertStringContainsString('role:siswa', implode('|', $siswaRoute->gatherMiddleware()));
        $this->assertStringContainsString('role:wali_kelas', implode('|', $guruRoute->gatherMiddleware()));
    }

    public function test_wali_kelas_dapat_membuka_daftar_pengajuan_dengan_nilai_json(): void
    {
        Schema::create('semester', function (Blueprint $table) {
            $table->id('id_semester');
            $table->date('tanggal_mulai');
            $table->boolean('is_aktif')->default(false);
        });
        Schema::create('guru', function (Blueprint $table) {
            $table->id('id_guru');
            $table->unsignedBigInteger('id_pengguna');
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

        DB::table('semester')->insert([
            'id_semester' => 1,
            'tanggal_mulai' => '2026-01-01',
            'is_aktif' => true,
        ]);
        DB::table('guru')->insert([
            'id_guru' => 1,
            'id_pengguna' => 20,
            'is_aktif' => true,
        ]);
        DB::table('kelas')->insert(['id_kelas' => 1, 'nama_kelas' => 'X MIA 1']);
        DB::table('penugasan_wali_kelas')->insert([
            'id_kelas' => 1,
            'id_guru' => 1,
            'id_semester' => 1,
        ]);
        DB::table('keanggotaan_kelas')->insert([
            'id_siswa' => 1,
            'id_kelas' => 1,
            'id_semester' => 1,
            'status' => 'aktif',
        ]);

        $idPengajuan = DB::table('pengajuan_perubahan')->insertGetId([
            'id_siswa' => 1,
            'id_periode_update' => 1,
            'alasan' => 'Ubah alamat',
            'status_pengajuan' => 'menunggu',
            'tgl_pengajuan' => now(),
        ]);
        DB::table('detail_pengajuan')->insert([
            'id_pengajuan' => $idPengajuan,
            'nama_field' => 'alamat',
            'nilai_lama' => json_encode('Alamat Lama'),
            'nilai_baru' => json_encode('Alamat Baru'),
        ]);
        $this->withoutMiddleware([EnsureRole::class])
            ->withSession(['id_pengguna' => 20])
            ->get('/guru/pengajuan-perubahan?kelas=1')
            ->assertOk()
            ->assertSee('Alamat Lama')
            ->assertSee('Alamat Baru')
            ->assertSee('Setujui')
            ->assertSee('Tolak')
            ->assertDontSee('Kembalikan');
    }

    public function test_halaman_login_tidak_meminta_pengguna_memilih_peran(): void
    {
        $this->get('/masuk')
            ->assertOk()
            ->assertDontSee('name="role"', false)
            ->assertSee('Masuk menggunakan username dan kata sandi akun.');
    }

    public function test_controller_api_menggunakan_siswa_yang_terautentikasi_saat_membuat_pengajuan(): void
    {
        $user = new class
        {
            public function siswa()
            {
                return new class
                {
                    public function firstOrFail()
                    {
                        return (object) ['id_siswa' => 1];
                    }
                };
            }
        };

        $request = new class($user) extends StorePengajuanPerubahanRequest
        {
            protected $user;

            public function __construct($user)
            {
                $this->user = $user;
            }

            public function user($guard = null)
            {
                return $this->user;
            }

            public function validated($key = null, $default = null)
            {
                $payload = [
                    'id_periode_update' => 1,
                    'alasan' => 'Perbaikan data',
                    'detail' => [[
                        'nama_field' => 'nama_lengkap',
                        'nilai_lama' => 'Siswa A',
                        'nilai_baru' => 'Siswa A Baru',
                        'bukti_path' => 'bukti/api.pdf',
                    ]],
                ];

                return $key === null ? $payload : data_get($payload, $key, $default);
            }
        };

        $controller = new PengajuanPerubahanController(app(PengajuanPerubahanService::class));
        $response = $controller->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('pengajuan_perubahan', ['id_siswa' => 1, 'status_pengajuan' => 'menunggu']);
    }

    public function test_controller_api_menolak_keputusan_jika_pengguna_tidak_memiliki_izin(): void
    {
        $user = new class
        {
            public function hasPermission(string $code): bool
            {
                return false;
            }
        };

        $request = Request::create('/api/pengajuan-perubahan/1/keputusan', 'POST', [
            'keputusan' => 'ditolak',
            'catatan' => 'Tidak berhak',
        ]);
        $request->setUserResolver(fn () => $user);

        $this->expectException(HttpException::class);
        (new PengajuanPerubahanController(app(PengajuanPerubahanService::class)))
            ->keputusan($request, 1);
    }

    public function test_controller_api_menolak_keputusan_kembalikan(): void
    {
        $user = new class
        {
            public int $id_pengguna = 20;

            public function hasPermission(string $code): bool
            {
                return true;
            }
        };

        $request = Request::create('/api/pengajuan-perubahan/1/keputusan', 'POST', [
            'keputusan' => 'perlu_perbaikan',
        ]);
        $request->setUserResolver(fn () => $user);

        try {
            (new PengajuanPerubahanController(app(PengajuanPerubahanService::class)))
                ->keputusan($request, 1);
            $this->fail('API tidak boleh menerima keputusan kembalikan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('keputusan', $exception->errors());
        }
    }

    public function test_pengajuan_dengan_status_menunggu_dapat_diproses_satu_kali(): void
    {
        $id = DB::table('pengajuan_perubahan')->insertGetId([
            'id_siswa' => 1,
            'id_periode_update' => 1,
            'alasan' => 'Perbaikan NIK',
            'status_pengajuan' => 'menunggu',
            'tgl_pengajuan' => now(),
        ]);

        DB::table('detail_pengajuan')->insert([
            'id_pengajuan' => $id,
            'nama_field' => 'nik',
            'nilai_lama' => json_encode('3201010101010001'),
            'nilai_baru' => json_encode('3201010101010009'),
            'bukti_path' => 'bukti/nik.pdf',
        ]);

        (new PengajuanPerubahanService)->putuskan($id, 20, 'ditolak', 'NIK belum sesuai dokumen');
        $this->assertSame('ditolak', DB::table('pengajuan_perubahan')->where('id_pengajuan', $id)->value('status_pengajuan'));

        $this->expectException(ValidationException::class);
        (new PengajuanPerubahanService)->putuskan($id, 20, 'ditolak', 'lagi');
    }

    public function test_wali_kelas_hanya_dapat_memutuskan_pengajuan_siswa_di_kelas_aktifnya(): void
    {
        Schema::create('semester', function (Blueprint $table) {
            $table->id('id_semester');
            $table->date('tanggal_mulai');
            $table->boolean('is_aktif')->default(false);
        });
        Schema::create('guru', function (Blueprint $table) {
            $table->id('id_guru');
            $table->unsignedBigInteger('id_pengguna');
            $table->boolean('is_aktif')->default(true);
        });
        Schema::create('kelas', function (Blueprint $table) {
            $table->id('id_kelas');
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

        DB::table('pengguna')->where('id_pengguna', 20)->update(['id_role' => 2]);
        DB::table('semester')->insert([
            'id_semester' => 1,
            'tanggal_mulai' => '2026-01-01',
            'is_aktif' => true,
        ]);
        DB::table('guru')->insert([
            'id_guru' => 1,
            'id_pengguna' => 20,
            'is_aktif' => true,
        ]);
        DB::table('penugasan_wali_kelas')->insert([
            'id_kelas' => 1,
            'id_guru' => 1,
            'id_semester' => 1,
        ]);
        DB::table('keanggotaan_kelas')->insert([
            ['id_siswa' => 1, 'id_kelas' => 1, 'id_semester' => 1, 'status' => 'aktif'],
            ['id_siswa' => 2, 'id_kelas' => 2, 'id_semester' => 1, 'status' => 'aktif'],
        ]);

        $idAssigned = DB::table('pengajuan_perubahan')->insertGetId([
            'id_siswa' => 1,
            'id_periode_update' => 1,
            'alasan' => 'Perbaikan data',
            'status_pengajuan' => 'menunggu',
            'tgl_pengajuan' => now(),
        ]);
        $idUnassigned = DB::table('pengajuan_perubahan')->insertGetId([
            'id_siswa' => 2,
            'id_periode_update' => 1,
            'alasan' => 'Perbaikan data',
            'status_pengajuan' => 'menunggu',
            'tgl_pengajuan' => now(),
        ]);

        $service = new PengajuanPerubahanService;
        $service->putuskan($idAssigned, 20, 'ditolak', 'Bukti perlu diperbaiki.');

        try {
            $service->putuskan($idUnassigned, 20, 'ditolak', 'Bukti perlu diperbaiki.');
            $this->fail('Wali kelas tidak boleh memutuskan pengajuan siswa dari kelas lain.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pengajuan', $exception->errors());
        }

        $this->assertDatabaseHas('pengajuan_perubahan', [
            'id_pengajuan' => $idAssigned,
            'status_pengajuan' => 'ditolak',
        ]);
        $this->assertDatabaseHas('pengajuan_perubahan', [
            'id_pengajuan' => $idUnassigned,
            'status_pengajuan' => 'menunggu',
        ]);

        try {
            $service->putuskan($idUnassigned, 10, 'ditolak', 'Bukti perlu diperbaiki.');
            $this->fail('Siswa tidak boleh memproses keputusan pengajuan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pengajuan', $exception->errors());
        }

        $this->assertDatabaseHas('pengajuan_perubahan', [
            'id_pengajuan' => $idUnassigned,
            'status_pengajuan' => 'menunggu',
        ]);
    }

    public function test_penolakan_tidak_mengubah_data_utama_dan_riwayat_nilai_tersimpan(): void
    {
        $id = DB::table('pengajuan_perubahan')->insertGetId([
            'id_siswa' => 1,
            'id_periode_update' => 1,
            'alasan' => 'Ubah alamat',
            'status_pengajuan' => 'menunggu',
            'tgl_pengajuan' => now(),
        ]);

        DB::table('detail_pengajuan')->insert([
            'id_pengajuan' => $id,
            'nama_field' => 'alamat',
            'nilai_lama' => json_encode('Jalan Lama'),
            'nilai_baru' => json_encode('Jalan Baru'),
            'bukti_path' => null,
        ]);

        $before = DB::table('siswa')->where('id_siswa', 1)->value('nama_lengkap');
        (new PengajuanPerubahanService)->putuskan($id, 20, 'ditolak', 'Dokumen belum lengkap');

        $this->assertSame($before, DB::table('siswa')->where('id_siswa', 1)->value('nama_lengkap'));
        $this->assertDatabaseHas('detail_pengajuan', ['id_pengajuan' => $id, 'nama_field' => 'alamat']);
        $this->assertSame('ditolak', DB::table('pengajuan_perubahan')->where('id_pengajuan', $id)->value('status_pengajuan'));
    }

    public function test_keputusan_selain_setujui_atau_tolak_ditolak_tanpa_mengubah_pengajuan(): void
    {
        Schema::create('semester', function (Blueprint $table) {
            $table->id('id_semester');
            $table->date('tanggal_mulai');
            $table->boolean('is_aktif')->default(false);
        });
        Schema::create('guru', function (Blueprint $table) {
            $table->id('id_guru');
            $table->unsignedBigInteger('id_pengguna');
            $table->boolean('is_aktif')->default(true);
        });
        Schema::create('kelas', function (Blueprint $table) {
            $table->id('id_kelas');
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
        DB::table('semester')->insert([
            'id_semester' => 1,
            'tanggal_mulai' => '2026-01-01',
            'is_aktif' => true,
        ]);
        DB::table('guru')->insert([
            'id_guru' => 1,
            'id_pengguna' => 20,
            'is_aktif' => true,
        ]);
        DB::table('penugasan_wali_kelas')->insert([
            'id_kelas' => 1,
            'id_guru' => 1,
            'id_semester' => 1,
        ]);
        DB::table('keanggotaan_kelas')->insert([
            'id_siswa' => 1,
            'id_kelas' => 1,
            'id_semester' => 1,
            'status' => 'aktif',
        ]);
        DB::table('pengajuan_perubahan')->insert([
            'id_pengajuan' => 50,
            'id_siswa' => 1,
            'id_periode_update' => 1,
            'alasan' => 'Ubah alamat',
            'status_pengajuan' => 'menunggu',
            'tgl_pengajuan' => now(),
        ]);

        DB::table('detail_pengajuan')->insert([
            'id_pengajuan' => 50,
            'nama_field' => 'alamat',
            'nilai_lama' => json_encode('Alamat lama'),
            'nilai_baru' => json_encode('Alamat baru'),
            'bukti_path' => 'bukti/alamat.pdf',
        ]);

        session()->put('id_pengguna', 20);
        $request = Request::create('/tu/persetujuan', 'POST', [
            'id_pengajuan' => 50,
            'keputusan' => 'kembalikan',
            'catatan' => 'Perbaiki bukti Anda.',
        ]);

        try {
            (new AksiFormController)->persetujuanSimpan($request, app(PengajuanPerubahanService::class));
            $this->fail('Keputusan selain setujui atau tolak seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('keputusan', $exception->errors());
        }

        $this->assertSame('menunggu', DB::table('pengajuan_perubahan')->where('id_pengajuan', 50)->value('status_pengajuan'));
        $this->assertDatabaseCount('notifikasi', 0);
    }

    public function test_wali_kelas_hanya_melihat_siswa_yang_diampu_pada_form_catatan(): void
    {
        $this->seedCatatanSchema();

        $this->withoutMiddleware([EnsureRole::class])
            ->withSession(['id_pengguna' => 20])
            ->get('/guru/catatan')
            ->assertOk()
            ->assertSee('Siswa A')
            ->assertDontSee('Siswa B');
    }

    public function test_catatan_wali_kelas_mengirim_notifikasi_dan_menolak_siswa_dari_kelas_lain(): void
    {
        $this->seedCatatanSchema();

        $this->withoutMiddleware([EnsureRole::class])
            ->withSession(['id_pengguna' => 20])
            ->post('/guru/catatan', [
                'id_siswa' => 1,
                'isi_catatan' => 'Perlu meningkatkan kehadiran.',
            ])
            ->assertRedirect(route('guru.catatan'));

        $this->assertDatabaseHas('catatan_wali_kelas', [
            'id_siswa' => 1,
            'isi_catatan' => 'Perlu meningkatkan kehadiran.',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'id_pengguna' => 10,
            'judul' => 'Catatan dari Wali Kelas',
            'pesan' => 'Wali kelas memberikan catatan: Perlu meningkatkan kehadiran.',
        ]);

        $this->post('/guru/catatan', [
            'id_siswa' => 2,
            'isi_catatan' => 'Siswa dari kelas lain.',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('id_siswa');

        $this->assertDatabaseCount('catatan_wali_kelas', 1);
        $this->assertDatabaseCount('notifikasi', 1);
    }

    public function test_notifikasi_catatan_wali_menampilkan_waktu_medan(): void
    {
        DB::table('notifikasi')->insert([
            'id_pengguna' => 10,
            'judul' => 'Catatan dari Wali Kelas',
            'pesan' => 'Perlu meningkatkan kehadiran.',
            'dibaca' => false,
            'dibuat_pada' => '2026-11-15 04:15:00+00:00',
        ]);

        $this->withoutMiddleware([EnsureRole::class])
            ->withSession(['id_pengguna' => 10])
            ->get('/notifikasi')
            ->assertOk()
            ->assertSee('15 Nov 2026 11:15');
    }

    private function seedCatatanSchema(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->id('id_tahun_ajaran');
            $table->string('nama_tahun_ajaran');
            $table->date('tanggal_mulai');
            $table->boolean('is_aktif')->default(false);
        });
        Schema::create('semester', function (Blueprint $table) {
            $table->id('id_semester');
            $table->date('tanggal_mulai');
            $table->boolean('is_aktif')->default(false);
        });
        Schema::create('guru', function (Blueprint $table) {
            $table->id('id_guru');
            $table->unsignedBigInteger('id_pengguna');
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
            $table->unsignedBigInteger('id_tahun_ajaran');
            $table->text('isi_catatan');
            $table->timestamp('tgl_dibuat');
        });

        DB::table('tahun_ajaran')->insert([
            'id_tahun_ajaran' => 1,
            'nama_tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'is_aktif' => true,
        ]);
        DB::table('semester')->insert([
            'id_semester' => 1,
            'tanggal_mulai' => '2026-07-01',
            'is_aktif' => true,
        ]);
        DB::table('guru')->insert([
            'id_guru' => 1,
            'id_pengguna' => 20,
            'is_aktif' => true,
        ]);
        DB::table('kelas')->insert([
            ['id_kelas' => 1, 'nama_kelas' => 'X MIA 1'],
            ['id_kelas' => 2, 'nama_kelas' => 'X IIS 1'],
        ]);
        DB::table('penugasan_wali_kelas')->insert([
            'id_kelas' => 1,
            'id_guru' => 1,
            'id_semester' => 1,
        ]);
        DB::table('keanggotaan_kelas')->insert([
            ['id_siswa' => 1, 'id_kelas' => 1, 'id_semester' => 1, 'status' => 'aktif'],
            ['id_siswa' => 2, 'id_kelas' => 2, 'id_semester' => 1, 'status' => 'aktif'],
        ]);
    }

    public function test_siswa_dapat_mengirim_pengajuan_melalui_fungsi_definer_tanpa_hak_update_periode(): void
    {
        $ownerConnection = DB::connection('pgsql_owner');
        $studentConnection = DB::connection('pgsql_siswa');
        $schema = 'test_pengajuan_fn_'.bin2hex(random_bytes(6));
        $schemaCreated = false;

        try {
            $ownerConnection->statement('CREATE SCHEMA '.$schema);
            $schemaCreated = true;
            $ownerConnection->unprepared(<<<SQL
CREATE TABLE {$schema}.siswa (
    id_siswa BIGINT PRIMARY KEY
);
CREATE TABLE {$schema}.periode_update (
    id_periode_update BIGINT PRIMARY KEY,
    status VARCHAR(20) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL
);
CREATE TABLE {$schema}.pengajuan_perubahan (
    id_pengajuan BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    id_siswa BIGINT NOT NULL REFERENCES {$schema}.siswa(id_siswa),
    id_periode_update BIGINT NOT NULL REFERENCES {$schema}.periode_update(id_periode_update),
    tgl_pengajuan TIMESTAMPTZ NOT NULL,
    status_pengajuan VARCHAR(30) NOT NULL,
    alasan TEXT NOT NULL
);
SQL);
            $ownerConnection->table($schema.'.siswa')->insert(['id_siswa' => 1]);
            $ownerConnection->table($schema.'.periode_update')->insert([
                'id_periode_update' => 1,
                'status' => 'aktif',
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => now()->addDay()->toDateString(),
            ]);

            $migration = file_get_contents(base_path('database/migrations/2026_11_17_192402_create_security_definer_pengajuan_function.php'));
            $this->assertIsString($migration);
            $matchedFunction = preg_match(
                '/CREATE OR REPLACE FUNCTION app_owner\.fn_buat_pengajuan_perubahan\(.*?\n\$function\$;/s',
                $migration,
                $matches
            );
            $this->assertSame(1, $matchedFunction);

            $functionSql = str_replace(
                [
                    'app_owner.fn_buat_pengajuan_perubahan',
                    'public.periode_update',
                    'public.pengajuan_perubahan',
                ],
                [
                    $schema.'.fn_buat_pengajuan_perubahan',
                    $schema.'.periode_update',
                    $schema.'.pengajuan_perubahan',
                ],
                $matches[0]
            );
            $ownerConnection->unprepared($functionSql);
            $ownerConnection->statement('GRANT USAGE ON SCHEMA '.$schema.' TO role_siswa');
            $ownerConnection->statement('GRANT EXECUTE ON FUNCTION '.$schema.'.fn_buat_pengajuan_perubahan(bigint, bigint, text) TO role_siswa');

            $roleCheck = $studentConnection->selectOne(
                "SELECT CASE WHEN has_table_privilege(current_user, '{$schema}.periode_update', 'UPDATE') THEN 'allowed' ELSE 'denied' END AS update_access"
            );
            $this->assertSame('denied', $roleCheck->update_access);

            $result = $studentConnection->selectOne(
                'SELECT '.$schema.'.fn_buat_pengajuan_perubahan(?, ?, ?) AS id_pengajuan',
                [1, 1, 'Pengujian fungsi definer']
            );

            $this->assertSame('1', (string) $result->id_pengajuan);
            $this->assertSame('menunggu', $ownerConnection->table($schema.'.pengajuan_perubahan')->value('status_pengajuan'));
        } finally {
            if ($schemaCreated) {
                $ownerConnection->statement('DROP SCHEMA '.$schema.' CASCADE');
            }

            DB::purge('pgsql_owner');
            DB::purge('pgsql_siswa');
        }
    }

    public function test_persetujuan_mengubah_data_siswa_dan_menandai_pengajuan_disetujui_di_postgresql(): void
    {
        $defaultConnection = DB::getDefaultConnection();
        $connection = DB::connection('pgsql_owner');
        $schema = 'test_pengajuan_'.bin2hex(random_bytes(6));
        $schemaCreated = false;

        try {
            $connection->statement('CREATE SCHEMA '.$schema);
            $schemaCreated = true;
            $connection->statement('SET search_path TO '.$schema.', public');
            DB::setDefaultConnection('pgsql_owner');

            $connection->unprepared(<<<'SQL'
CREATE TABLE role (
    id_role BIGINT PRIMARY KEY,
    nama_role VARCHAR(100) NOT NULL
);
CREATE TABLE pengguna (
    id_pengguna BIGINT PRIMARY KEY,
    id_role BIGINT NOT NULL
);
CREATE TABLE siswa (
    id_siswa BIGINT PRIMARY KEY,
    nama_lengkap VARCHAR(255) NOT NULL,
    diubah_pada TIMESTAMP NULL
);
CREATE TABLE ref_field_data (
    id_field BIGINT PRIMARY KEY,
    nama_tabel VARCHAR(100) NOT NULL,
    nama_kolom VARCHAR(100) NOT NULL,
    label_field VARCHAR(255) NOT NULL,
    boleh_diubah_siswa BOOLEAN NOT NULL,
    perlu_bukti BOOLEAN NOT NULL DEFAULT FALSE
);
CREATE TABLE pengajuan_perubahan (
    id_pengajuan BIGINT PRIMARY KEY,
    id_siswa BIGINT NOT NULL,
    status_pengajuan VARCHAR(30) NOT NULL,
    id_pemeriksa BIGINT NULL,
    catatan_pemeriksa TEXT NULL,
    diputuskan_pada TIMESTAMP NULL
);
CREATE TABLE detail_pengajuan (
    id_detail_pengajuan BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    id_pengajuan BIGINT NOT NULL,
    nama_field VARCHAR(100) NOT NULL,
    nilai_baru JSONB NULL,
    bukti_path TEXT NULL
);
SQL);

            $migrationPath = base_path('database/migrations/2026_11_08_000003_perbaikan_persetujuan_pengajuan.php');
            $migration = file_get_contents($migrationPath);
            $this->assertIsString($migration);
            $matchedProcedure = preg_match(
                '/DB::unprepared\(<<<\'SQL\'\R(.*?)\RSQL\);/s',
                $migration,
                $matches
            );
            $this->assertSame(1, $matchedProcedure);

            $procedureSql = str_replace(
                'CREATE OR REPLACE PROCEDURE public.sp_setujui_pengajuan',
                'CREATE OR REPLACE PROCEDURE '.$schema.'.sp_setujui_pengajuan',
                $matches[1]
            );
            $connection->unprepared($procedureSql);

            $connection->table('role')->insert(['id_role' => 1, 'nama_role' => 'tu']);
            $connection->table('pengguna')->insert(['id_pengguna' => 10, 'id_role' => 1]);
            $connection->table('siswa')->insert([
                'id_siswa' => 1,
                'nama_lengkap' => 'Nama Lama',
            ]);
            $connection->table('ref_field_data')->insert([
                'id_field' => 1,
                'nama_tabel' => 'siswa',
                'nama_kolom' => 'nama_lengkap',
                'label_field' => 'Nama lengkap',
                'boleh_diubah_siswa' => true,
                'perlu_bukti' => false,
            ]);
            $connection->table('pengajuan_perubahan')->insert([
                'id_pengajuan' => 1,
                'id_siswa' => 1,
                'status_pengajuan' => 'menunggu',
            ]);
            $connection->table('detail_pengajuan')->insert([
                'id_pengajuan' => 1,
                'nama_field' => 'nama_lengkap',
                'nilai_baru' => json_encode('Nama Baru', JSON_THROW_ON_ERROR),
            ]);

            app(PengajuanPerubahanService::class)->putuskan(1, 10, 'disetujui', 'Data diverifikasi.');

            $this->assertSame('Nama Baru', $connection->table('siswa')->where('id_siswa', 1)->value('nama_lengkap'));
            $this->assertSame('disetujui', $connection->table('pengajuan_perubahan')->where('id_pengajuan', 1)->value('status_pengajuan'));
            $this->assertSame(10, (int) $connection->table('pengajuan_perubahan')->where('id_pengajuan', 1)->value('id_pemeriksa'));
            $this->assertSame('Data diverifikasi.', $connection->table('pengajuan_perubahan')->where('id_pengajuan', 1)->value('catatan_pemeriksa'));
            $this->assertNotNull($connection->table('pengajuan_perubahan')->where('id_pengajuan', 1)->value('diputuskan_pada'));
        } finally {
            try {
                if ($schemaCreated) {
                    $connection->statement('DROP SCHEMA '.$schema.' CASCADE');
                }
            } finally {
                DB::setDefaultConnection($defaultConnection);
                DB::purge('pgsql_owner');
            }
        }
    }
}
