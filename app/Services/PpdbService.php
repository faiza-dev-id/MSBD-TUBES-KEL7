<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\PendaftaranPpdb;
use App\Support\AuditContext;
use App\Support\Referensi\OpsiPpdb;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PpdbService
{
    public const PESAN_IDENTITAS_TIDAK_DAPAT_DIPROSES = 'Pendaftaran tidak dapat diproses dengan data tersebut. Jika Anda pernah mengirim formulir, gunakan halaman Cek Status untuk memperbaiki berkas pada pengajuan yang sama. Hubungi Tata Usaha jika membutuhkan bantuan.';

    public const HUBUNGAN = ['ayah', 'ibu', 'wali'];

    public const DOKUMEN = [
        'Kartu Keluarga',
        'Akta Kelahiran',
        'Ijazah / SKL',
        'Pas Foto 3x4',
    ];

    public const CHECKLIST_TIDAK_DIPERIKSA = [
        'KTP Orang Tua',
        'Piagam Prestasi',
        'Rapor Semester',
        'Rapor Semester Terakhir',
        'Surat Keterangan Pindah',
    ];

    public const KEBUTUHAN_KHUSUS = OpsiPpdb::KEBUTUHAN_KHUSUS;

    private const AGAMA_NAMA = [
        '01' => ['islam'],
        '02' => ['kristen', 'kristen protestan'],
        '03' => ['katolik'],
        '04' => ['hindu'],
        '05' => ['buddha', 'budha'],
        '06' => ['konghucu', 'kong hu chu', 'kong hu cu'],
        '07' => ['lainnya'],
    ];

    private const AGAMA_LABEL = [
        '01' => 'Islam',
        '02' => 'Kristen Protestan',
        '03' => 'Katolik',
        '04' => 'Hindu',
        '05' => 'Budha',
        '06' => 'Kong Hu Chu',
        '07' => 'Lainnya',
    ];

    private ?array $agamaMap = null;

    private const PEKERJAAN = [
        '01' => 'Tidak Bekerja',
        '02' => 'Nelayan',
        '03' => 'Petani',
        '04' => 'Peternak',
        '05' => 'PNS/ASN',
        '06' => 'Karyawan Swasta',
        '07' => 'Pedagang',
        '08' => 'Pedagang',
        '09' => 'Wiraswasta',
        '10' => 'Wiraswasta',
        '11' => 'Buruh',
        '12' => 'Pensiunan',
        '13' => 'Lainnya',
    ];

    public function form(?string $token = null): array
    {
        $data = $this->kosong();
        $baris = $token
            ? DB::table('pendaftaran_ppdb')->where('token_draf', $token)->first()
            : null;

        if ($baris) {
            $data = $this->dariBaris($baris, $data);
        }

        $berkas = [];

        if ($baris) {
            foreach (DB::table('dokumen_siswa')->where('id_pendaftaran', $baris->id_pendaftaran)->get() as $dokumen) {
                $berkas[$dokumen->jenis_dokumen] = $dokumen;
                $alias = str_replace(' / ', '/', $dokumen->jenis_dokumen);

                if (! isset($berkas[$alias])) {
                    $berkas[$alias] = $dokumen;
                }

                $alias = str_replace('/', ' / ', $dokumen->jenis_dokumen);

                if (! isset($berkas[$alias])) {
                    $berkas[$alias] = $dokumen;
                }
            }
        }

        if ($baris) {
            $data['nomor_dokumen_1'] = $berkas['Akta Kelahiran']->catatan ?? '';
            $data['nomor_dokumen_2'] = $berkas['Ijazah / SKL']->catatan ?? ($data['no_seri_ijazah'] ?? '');
        }

        return [
            'data' => $data,
            'token' => $baris->token_draf ?? null,
            'nomor' => $baris->nomor_pendaftaran ?? null,
            'berkas' => $berkas,
            'perbaikan' => $baris && in_array($baris->status_pendaftaran, ['perlu_perbaikan', 'ditolak'], true),
        ];
    }

    public function tokenPerluPerbaikan(?string $token): bool
    {
        return $token !== null
            && DB::table('pendaftaran_ppdb')
                ->where('token_draf', $token)
                ->whereIn('status_pendaftaran', ['perlu_perbaikan', 'ditolak'])
                ->exists();
    }

    public function dataPendaftaran(int $id): array
    {
        $baris = DB::table('pendaftaran_ppdb')
            ->where('id_pendaftaran', $id)
            ->first();

        if (! $baris) {
            return $this->kosong();
        }

        $data = $this->dariBaris($baris, $this->kosong());
        $berkas = DB::table('dokumen_siswa')
            ->where('id_pendaftaran', $id)
            ->get(['id_pendaftaran', 'jenis_dokumen', 'catatan'])
            ->keyBy('jenis_dokumen');

        $data['nomor_dokumen_1'] = $berkas['Akta Kelahiran']->catatan ?? '';
        $data['nomor_dokumen_2'] = $berkas['Ijazah / SKL']->catatan ?? ($data['no_seri_ijazah'] ?? '');

        return $data;
    }

    public function kelasTujuan(): array
    {
        $idTahunAjaran = DB::table('tahun_ajaran')
            ->where('is_aktif', true)
            ->orderByDesc('id_tahun_ajaran')
            ->value('id_tahun_ajaran');

        if (! $idTahunAjaran) {
            return [];
        }

        return Kelas::urutAlami(
            DB::table('kelas')
                ->where('id_tahun_ajaran', $idTahunAjaran)
                ->where('is_aktif', true)
                ->get(['id_kelas', 'nama_kelas'])
                ->map(fn ($baris) => new Kelas((array) $baris))
                ->filter(fn (Kelas $kelas) => preg_match('/^(XII|XI|X)\s+(MIA|IIS)(?:\s|$)/i', $kelas->nama_kelas))
        )
            ->pluck('nama_kelas')
            ->all();
    }

    public function agamaOptions(): array
    {
        return array_intersect_key(self::AGAMA_LABEL, $this->agamaMap());
    }

    public function simpan(array $input, ?string $token = null, array $berkas = [], bool $final = false, ?string $nomor = null): array
    {
        try {
            return AuditContext::transaksi(function () use ($input, $token, $berkas, $final, $nomor) {
                $baris = $token
                    ? DB::table('pendaftaran_ppdb')->where('token_draf', $token)->lockForUpdate()->first()
                    : null;

                if ($token && ! $baris) {
                    throw ValidationException::withMessages([
                        'token_draf' => 'Sesi draf tidak ditemukan. Silakan isi formulir kembali.',
                    ]);
                }

                if ($baris && ! $final && $baris->status_pendaftaran !== 'draf') {
                    throw ValidationException::withMessages([
                        'token_draf' => 'Pendaftaran yang sudah dikirim tidak dapat diubah sebagai draf.',
                    ]);
                }

                if ($baris && $final && in_array($baris->status_pendaftaran, ['menunggu', 'diverifikasi', 'diterima'], true)) {
                    throw ValidationException::withMessages([
                        'token_draf' => 'Pendaftaran ini sudah dikirim dan tidak dapat dikirim ulang tanpa status perlu perbaikan.',
                    ]);
                }

                if ($final && $this->duplikat($input['nisn'] ?? null, $input['nik'] ?? null, $token)) {
                    throw ValidationException::withMessages([
                        'nisn' => self::PESAN_IDENTITAS_TIDAK_DAPAT_DIPROSES,
                    ]);
                }

                $isPerbaikan = $baris && in_array($baris->status_pendaftaran, ['perlu_perbaikan', 'ditolak'], true);
                $atribut = $this->atribut($input);

                if ($final) {
                    $this->sinkronkanReferensi($input);
                }

                if ($baris) {
                    $id = (int) $baris->id_pendaftaran;
                    $token = $baris->token_draf;
                    $nomor = $baris->nomor_pendaftaran;
                    $update = $atribut + ['status_pendaftaran' => $final ? 'menunggu' : 'draf'];

                    if ($final) {
                        $update['diajukan_pada'] = now();
                        $update['catatan_verifikasi'] = null;
                        $update['id_pengguna_pemeriksa'] = null;
                        $update['diperiksa_pada'] = null;
                    }

                    DB::table('pendaftaran_ppdb')->where('id_pendaftaran', $id)->update($update);
                } else {
                    $token = $token ?: Str::random(40);
                    $nomor = $nomor ?: 'PPDB-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
                    $id = (int) DB::table('pendaftaran_ppdb')->insertGetId([
                        'nomor_pendaftaran' => $nomor,
                        'token_draf' => $token,
                        'status_pendaftaran' => $final ? 'menunggu' : 'draf',
                        'diajukan_pada' => now(),
                    ] + $atribut, 'id_pendaftaran');
                }

                if ($final && ! empty($input['password'])) {
                    $this->buatAkunPendaftar(
                        (string) $input['nisn'],
                        (string) $input['password'],
                        (bool) $isPerbaikan
                    );
                }

                if ($final && $isPerbaikan && $baris->status_pendaftaran === 'ditolak') {
                    DB::table('dokumen_siswa')
                        ->where('id_pendaftaran', $id)
                        ->where('status_verifikasi', 'ditolak')
                        ->update(['status_verifikasi' => 'menunggu']);
                }

                $this->simpanAnak($id, $input, $final);
                $this->simpanBerkas($id, $berkas, $input);

                return [
                    'id' => $id,
                    'token' => $token,
                    'nomor' => $nomor,
                    'status' => $final ? 'menunggu' : 'draf',
                ];
            });
        } catch (QueryException $exception) {
            if (! $this->adalahKonflikIdentitasTerkirim($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'nisn' => self::PESAN_IDENTITAS_TIDAK_DAPAT_DIPROSES,
            ]);
        }
    }

    private function adalahKonflikIdentitasTerkirim(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'pendaftaran_ppdb_nisn_terkirim_unique')
            || str_contains($exception->getMessage(), 'pendaftaran_ppdb_nik_terkirim_unique');
    }

    private function sinkronkanReferensi(array $input): void
    {
        $namaSekolah = trim((string) ($input['sekolah_asal'] ?? ''));
        if ($namaSekolah !== '' && ! DB::table('sekolah')
            ->select('id_sekolah')
            ->whereRaw('LOWER(TRIM(nama_sekolah)) = ?', [mb_strtolower($namaSekolah)])
            ->exists()) {
            DB::table('sekolah')->insert([
                'nama_sekolah' => $namaSekolah,
                'is_aktif' => true,
            ]);
        }

        $namaProvinsi = trim((string) ($input['provinsi'] ?? ''));
        if ($namaProvinsi === '') {
            return;
        }

        $idProvinsi = DB::table('provinsi')
            ->whereRaw('LOWER(TRIM(nama_provinsi)) = ?', [mb_strtolower($namaProvinsi)])
            ->value('id_provinsi');

        if (! $idProvinsi) {
            $idProvinsi = DB::table('provinsi')->insertGetId([
                'nama_provinsi' => $namaProvinsi,
            ], 'id_provinsi');
        }

        $namaKota = trim((string) ($input['kota'] ?? ''));
        if ($namaKota !== '' && ! DB::table('kota')
            ->where('id_provinsi', $idProvinsi)
            ->whereRaw('LOWER(TRIM(nama_kota)) = ?', [mb_strtolower($namaKota)])
            ->exists()) {
            DB::table('kota')->insert([
                'id_provinsi' => $idProvinsi,
                'nama_kota' => $namaKota,
            ]);
        }
    }

    private function buatAkunPendaftar(string $nisn, string $password, bool $isPerbaikan): void
    {
        $idRole = DB::table('role')
            ->whereRaw('LOWER(nama_role) = ?', ['siswa'])
            ->value('id_role');

        if (! $idRole) {
            throw ValidationException::withMessages([
                'nisn' => 'Akun belum dapat dibuat. Hubungi Tata Usaha untuk bantuan.',
            ]);
        }

        $akun = DB::table('pengguna')
            ->where('username', $nisn)
            ->lockForUpdate()
            ->first(['id_pengguna', 'id_role', 'is_aktif']);

        if ($akun) {
            $bolehDigunakanKembali = $isPerbaikan
                && ! (bool) $akun->is_aktif
                && (int) $akun->id_role === (int) $idRole
                && ! DB::table('siswa')->where('id_pengguna', $akun->id_pengguna)->exists();

            if (! $bolehDigunakanKembali) {
                throw ValidationException::withMessages([
                    'nisn' => self::PESAN_IDENTITAS_TIDAK_DAPAT_DIPROSES,
                ]);
            }

            DB::table('pengguna')
                ->where('id_pengguna', $akun->id_pengguna)
                ->update([
                    'password_hash' => Hash::make($password),
                    'is_aktif' => false,
                    'diubah_pada' => now(),
                ]);

            return;
        }

        DB::table('pengguna')->insert([
            'id_role' => $idRole,
            'username' => $nisn,
            'password_hash' => Hash::make($password),
            'is_aktif' => false,
            'dibuat_pada' => now(),
            'diubah_pada' => now(),
        ]);
    }

    public function daftar(array $data): PendaftaranPpdb
    {
        $input = $data;
        $input['nama_lengkap'] = $data['nama_calon_siswa'] ?? null;
        $input['no_hp'] = $data['no_telepon'] ?? null;
        $input['jalan'] = $data['alamat'] ?? null;
        $input['no_hp_alamat'] = $data['no_telepon'] ?? null;

        $hasil = $this->simpan($input, null, [], true, $data['nomor_pendaftaran'] ?? null);

        return PendaftaranPpdb::query()->findOrFail($hasil['id']);
    }

    public function punyaBerkas(?string $token, string $jenis): bool
    {
        if (! $token) {
            return false;
        }

        $id = DB::table('pendaftaran_ppdb')->where('token_draf', $token)->value('id_pendaftaran');

        if (! $id) {
            return false;
        }

        return DB::table('dokumen_siswa')
            ->where('id_pendaftaran', $id)
            ->whereRaw("REPLACE(jenis_dokumen, ' ', '') = ?", [$this->normalJenis($jenis)])
            ->where(function ($query) {
                $query->whereNull('status_verifikasi')
                    ->orWhere('status_verifikasi', '<>', 'perlu_perbaikan');
            })
            ->exists();
    }

    public function punyaSemuaBerkas(?string $token): bool
    {
        foreach (self::DOKUMEN as $jenis) {
            if (! $this->punyaBerkas($token, $jenis)) {
                return false;
            }
        }

        return $token !== null;
    }

    public function punyaSemuaBerkasId(int $idPendaftaran): bool
    {
        foreach (self::DOKUMEN as $jenis) {
            $ada = DB::table('dokumen_siswa')
                ->where('id_pendaftaran', $idPendaftaran)
                ->whereRaw("REPLACE(jenis_dokumen, ' ', '') = ?", [$this->normalJenis($jenis)])
                ->whereIn('status_verifikasi', ['menunggu', 'valid'])
                ->exists();

            if (! $ada) {
                return false;
            }
        }

        return true;
    }

    public function duplikat(?string $nisn, ?string $nik, ?string $token = null): bool
    {
        $query = DB::table('pendaftaran_ppdb')
            ->where('status_pendaftaran', '!=', 'draf')
            ->when($token, fn ($q) => $q->where(fn ($x) => $x->whereNull('token_draf')->orWhere('token_draf', '!=', $token)));

        return ($nisn && ((clone $query)->whereRaw('TRIM(nisn) = ?', [$nisn])->exists()
                || DB::table('siswa')->select('id_siswa')->whereRaw('TRIM(nisn) = ?', [$nisn])->exists()))
            || ($nik && ((clone $query)->whereRaw('TRIM(nik) = ?', [$nik])->exists()
                || DB::table('siswa')->select('id_siswa')->whereRaw('TRIM(nik) = ?', [$nik])->exists()));
    }

    public function verifikasi(
        int $id,
        int $petugas,
        string $status,
        ?string $catatan = null,
        ?int $idDokumenPerbaikan = null
    ): PendaftaranPpdb {
        $allowed = ['diverifikasi', 'diterima', 'perlu_perbaikan'];

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'Status PPDB tidak valid']);
        }

        return AuditContext::transaksi(function () use ($id, $petugas, $status, $catatan, $idDokumenPerbaikan) {
            $p = PendaftaranPpdb::query()->lockForUpdate()->findOrFail($id);

            $transisi = [
                'menunggu' => ['diverifikasi', 'perlu_perbaikan'],
                'perlu_perbaikan' => ['diverifikasi'],
                'diverifikasi' => ['diterima', 'perlu_perbaikan'],
                'ditolak' => ['perlu_perbaikan'],
            ];

            if (! in_array($status, $transisi[$p->status_pendaftaran] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status pendaftaran tidak dapat diubah dari '.$p->status_pendaftaran.' menjadi '.$status.'.',
                ]);
            }

            if ($status === 'perlu_perbaikan'
                && $idDokumenPerbaikan !== null
                && ! DB::table('dokumen_siswa')
                    ->where('id_dokumen', $idDokumenPerbaikan)
                    ->where('id_pendaftaran', $id)
                    ->exists()) {
                throw ValidationException::withMessages([
                    'id_dokumen_perbaikan' => 'Pilih dokumen dari pendaftaran ini untuk diperbaiki.',
                ]);
            }

            if ($status === 'diterima' && ! $this->punyaSemuaBerkasId($id)) {
                throw ValidationException::withMessages([
                    'status' => 'Pendaftaran belum dapat diterima karena berkas wajib belum lengkap.',
                ]);
            }

            $statusSebelumnya = $p->status_pendaftaran;

            $p->update([
                'status_pendaftaran' => $status,
                'id_pengguna_pemeriksa' => $petugas,
                'catatan_verifikasi' => $catatan,
                'diperiksa_pada' => now(),
            ]);

            $statusDokumen = match ($status) {
                'diverifikasi', 'diterima' => 'valid',
                'perlu_perbaikan' => 'perlu_perbaikan',
            };

            if ($status === 'perlu_perbaikan' && $statusSebelumnya === 'ditolak') {
                DB::table('dokumen_siswa')
                    ->where('id_pendaftaran', $id)
                    ->where('status_verifikasi', 'ditolak')
                    ->update(['status_verifikasi' => 'menunggu']);
            }

            if ($status === 'perlu_perbaikan' && $idDokumenPerbaikan !== null) {
                DB::table('dokumen_siswa')
                    ->where('id_dokumen', $idDokumenPerbaikan)
                    ->where('id_pendaftaran', $id)
                    ->update(['status_verifikasi' => $statusDokumen]);
            } elseif ($status !== 'perlu_perbaikan') {
                DB::table('dokumen_siswa')
                    ->where('id_pendaftaran', $id)
                    ->update(['status_verifikasi' => $statusDokumen]);
            }

            return $p->refresh();
        });
    }

    public function simpanBerkas(int $idPendaftaran, array $berkas, array $input = []): void
    {
        $nomor = [
            1 => $this->teks($input['nomor_dokumen_1'] ?? null),
            2 => $this->teks($input['nomor_dokumen_2'] ?? null),
        ];

        foreach (self::DOKUMEN as $index => $jenis) {
            $file = $berkas[$index] ?? null;
            $catatan = $nomor[$index] ?? null;

            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                if ($catatan !== null) {
                    DB::table('dokumen_siswa')
                        ->where('id_pendaftaran', $idPendaftaran)
                        ->whereRaw("REPLACE(jenis_dokumen, ' ', '') = ?", [$this->normalJenis($jenis)])
                        ->update(['catatan' => $catatan]);
                }

                continue;
            }

            $lama = DB::table('dokumen_siswa')
                ->where('id_pendaftaran', $idPendaftaran)
                ->whereRaw("REPLACE(jenis_dokumen, ' ', '') = ?", [$this->normalJenis($jenis)])
                ->first();

            $path = $file->store('ppdb/'.$idPendaftaran, 'private');

            DB::table('dokumen_siswa')
                ->where('id_pendaftaran', $idPendaftaran)
                ->whereRaw("REPLACE(jenis_dokumen, ' ', '') = ?", [$this->normalJenis($jenis)])
                ->delete();

            DB::table('dokumen_siswa')->insert([
                'id_pendaftaran' => $idPendaftaran,
                'jenis_dokumen' => $jenis,
                'nama_file_asli' => $file->getClientOriginalName(),
                'path_file' => $path,
                'status_verifikasi' => 'menunggu',
                'catatan' => $catatan ?: ($lama->catatan ?? null),
                'diunggah_pada' => now(),
            ]);

        }
    }

    private function kosong(): array
    {
        $tahun = DB::table('tahun_ajaran')->where('is_aktif', true)->value('nama_tahun_ajaran') ?? '';

        return [
            'nomor_pendaftaran' => '',
            'tanggal' => now()->toDateString(),
            'tahun_pelajaran' => $tahun,
            'nis' => '',
            'pendaftaran_untuk' => 'SMA',
            'jenjang_tujuan' => 'SMA',
            'kelas_tujuan' => '',
            'no_seri_skhun' => '',
            'tahun_skhun' => '',
            'no_seri_ijazah' => '',
            'tahun_ijazah' => '',
            'sekolah_asal' => '',
            'no_peserta_ujian' => '',
            'hobi' => '',
            'cita_cita' => '',
            'nama_lengkap' => '',
            'jenis_kelamin' => '',
            'nisn' => '',
            'nik' => '',
            'tempat_lahir' => '',
            'tanggal_lahir' => '',
            'anak_ke' => '',
            'status_keluarga' => 'Anak kandung',
            'agama' => '',
            'berkebutuhan_khusus' => '00',
            'no_kk' => '',
            'no_hp' => '',
            'email' => '',
            'tempat_tinggal' => '1',
            'moda_transportasi' => '02',
            'penerima_kps' => 'TIDAK',
            'no_kps' => '',
            'kewarganegaraan' => 'WNI',
            'negara' => '',
            'layak_pip' => 'TIDAK',
            'alasan_pip' => '1',
            'provinsi' => '',
            'kota' => '',
            'kecamatan' => '',
            'kelurahan' => '',
            'jalan' => '',
            'rt' => '',
            'rw' => '',
            'telp' => '',
            'no_hp_alamat' => '',
            'dusun' => '',
            'kode_pos' => '',
            'ortu' => [
                $this->ortuKosong(),
                $this->ortuKosong(),
                $this->ortuKosong(),
            ],
            'tinggi_badan' => '',
            'berat_badan' => '',
            'gol_darah' => 'O',
            'jarak' => '1',
            'jarak_km' => '',
            'waktu_jam' => '',
            'waktu_menit' => '',
            'jumlah_saudara' => '',
            'prestasi' => [],
            'beasiswa' => [],
            'keluar_karena' => '1',
            'tanggal_keluar' => '',
            'alasan_keluar' => '',
            'nama_ortu_wali' => '',
            'persetujuan_ortu' => false,
            'pernyataan' => false,
            'langkah_terakhir' => 0,
        ];
    }

    private function ortuKosong(): array
    {
        return [
            'nama' => '',
            'nik' => '',
            'tanggal_lahir' => '',
            'tempat_lahir' => '',
            'pendidikan' => '01',
            'pekerjaan' => '01',
            'penghasilan' => '1',
            'kebutuhan' => '00',
            'hp' => '',
            'alamat' => '',
        ];
    }

    private function dariBaris(object $baris, array $data): array
    {
        $id = (int) $baris->id_pendaftaran;

        $data['nomor_pendaftaran'] = (string) ($baris->nomor_pendaftaran ?? '');
        $data['tanggal'] = $baris->diajukan_pada
            ? Carbon::parse($baris->diajukan_pada)->toDateString()
            : now()->toDateString();

        $peta = [
            'nis' => 'nis',
            'tahun_pelajaran' => 'tahun_pelajaran',
            'pendaftaran_untuk' => 'pendaftaran_untuk',
            'jenjang_tujuan' => 'jenjang_tujuan',
            'kelas_tujuan' => 'kelas_tujuan',
            'no_seri_skhun' => 'no_seri_skhun',
            'tahun_skhun' => 'tahun_skhun',
            'no_seri_ijazah' => 'no_seri_ijazah',
            'tahun_ijazah' => 'tahun_ijazah',
            'sekolah_asal' => 'sekolah_asal',
            'no_peserta_ujian' => 'no_peserta_ujian',
            'hobi' => 'hobi',
            'cita_cita' => 'cita_cita',
            'nama_lengkap' => 'nama_calon_siswa',
            'jenis_kelamin' => 'jenis_kelamin',
            'nisn' => 'nisn',
            'nik' => 'nik',
            'tempat_lahir' => 'tempat_lahir',
            'tanggal_lahir' => 'tanggal_lahir',
            'anak_ke' => 'anak_ke',
            'status_keluarga' => 'status_keluarga',
            'berkebutuhan_khusus' => 'berkebutuhan_khusus',
            'no_kk' => 'no_kk',
            'no_hp' => 'no_telepon',
            'email' => 'email',
            'tempat_tinggal' => 'tempat_tinggal',
            'moda_transportasi' => 'moda_transportasi',
            'penerima_kps' => 'penerima_kps',
            'no_kps' => 'no_kps',
            'kewarganegaraan' => 'kewarganegaraan',
            'negara' => 'negara',
            'layak_pip' => 'layak_pip',
            'alasan_pip' => 'alasan_pip',
            'nama_ortu_wali' => 'nama_ortu_wali',
        ];

        foreach ($peta as $kunci => $kolom) {
            $nilai = $baris->{$kolom} ?? null;
            $data[$kunci] = $nilai === null ? '' : (string) $nilai;
        }

        $data['agama'] = $this->kodeAgama($baris->id_agama ?? null);
        $data['persetujuan_ortu'] = (bool) $baris->persetujuan_ortu;
        $data['pernyataan'] = (bool) $baris->pernyataan;
        $data['langkah_terakhir'] = (int) ($baris->langkah_terakhir ?? 0);

        $alamat = DB::table('ppdb_alamat')->where('id_pendaftaran', $id)->first();
        if ($alamat) {
            foreach (['jalan', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kota', 'provinsi', 'dusun', 'kode_pos', 'telp'] as $kolom) {
                $data[$kolom] = $alamat->{$kolom} ?? '';
            }
            $data['no_hp_alamat'] = $alamat->no_hp ?? '';
        }

        $periodik = DB::table('ppdb_periodik')->where('id_pendaftaran', $id)->first();
        if ($periodik) {
            $data['tinggi_badan'] = $periodik->tinggi_badan ?? '';
            $data['berat_badan'] = $periodik->berat_badan ?? '';
            $data['gol_darah'] = $periodik->gol_darah ?? '';
            $data['jarak'] = $periodik->jarak ?? '1';
            $data['jarak_km'] = $periodik->jarak_km ?? '';
            $data['waktu_jam'] = $periodik->waktu_tempuh_jam ?? '';
            $data['waktu_menit'] = $periodik->waktu_tempuh_menit ?? '';
            $data['jumlah_saudara'] = $periodik->jumlah_saudara ?? '';
        }

        $ortu = DB::table('ppdb_orang_tua')->where('id_pendaftaran', $id)->get()->keyBy('hubungan');
        $data['ortu'] = [];
        foreach (self::HUBUNGAN as $hubungan) {
            $o = $ortu->get($hubungan);
            $data['ortu'][] = [
                'nama' => $o->nama_lengkap ?? '',
                'nik' => $o->nik ?? '',
                'tanggal_lahir' => $o->tanggal_lahir ?? '',
                'tempat_lahir' => $o->tempat_lahir ?? '',
                'pendidikan' => $o->pendidikan ?? '01',
                'pekerjaan' => $this->kodePekerjaan($o->id_pekerjaan ?? null),
                'penghasilan' => $o->penghasilan ?? '1',
                'kebutuhan' => $o->berkebutuhan_khusus ?? '01',
                'hp' => $o->no_telepon ?? '',
                'alamat' => $o->alamat ?? '',
            ];
        }

        $data['prestasi'] = DB::table('ppdb_prestasi')->where('id_pendaftaran', $id)->get()->map(fn ($row) => [
            'jenis' => $row->jenis ?? '',
            'tingkat' => $row->tingkat ?? '',
            'nama' => $row->nama_prestasi ?? '',
            'tahun' => $row->tahun ?? '',
            'penyelenggara' => $row->penyelenggara ?? '',
        ])->all();

        $data['beasiswa'] = DB::table('ppdb_beasiswa')->where('id_pendaftaran', $id)->get()->map(fn ($row) => [
            'jenis' => ($row->jenis ?? '') === '5' ? '05' : ($row->jenis ?? ''),
            'keterangan' => $row->keterangan ?? '',
            'tahun_mulai' => $row->tahun_mulai ?? '',
            'tahun_selesai' => $row->tahun_selesai ?? '',
        ])->all();

        $keluar = DB::table('ppdb_keluar')->where('id_pendaftaran', $id)->first();
        if ($keluar) {
            $data['keluar_karena'] = $keluar->keluar_karena ?? '1';
            $data['tanggal_keluar'] = $keluar->tanggal_keluar ?? '';
            $data['alasan_keluar'] = $keluar->alasan ?? '';
        }

        return $data;
    }

    private function atribut(array $d): array
    {
        $peta = [
            'nis', 'tahun_pelajaran', 'pendaftaran_untuk', 'jenjang_tujuan', 'kelas_tujuan',
            'no_seri_skhun', 'tahun_skhun', 'no_seri_ijazah', 'tahun_ijazah', 'sekolah_asal',
            'no_peserta_ujian', 'hobi', 'cita_cita', 'jenis_kelamin', 'nisn', 'nik',
            'tempat_lahir', 'status_keluarga', 'berkebutuhan_khusus', 'no_kk', 'email',
            'tempat_tinggal', 'moda_transportasi', 'penerima_kps', 'no_kps', 'kewarganegaraan',
            'negara', 'layak_pip', 'alasan_pip', 'nama_ortu_wali',
        ];

        $out = [];
        foreach ($peta as $kunci) {
            if (array_key_exists($kunci, $d)) {
                $out[$kunci] = $this->teks($d[$kunci]);
            }
        }

        if (array_key_exists('nama_lengkap', $d)) {
            $out['nama_calon_siswa'] = $this->teks($d['nama_lengkap']) ?? '';
        }
        if (array_key_exists('no_hp', $d)) {
            $out['no_telepon'] = $this->teks($d['no_hp']);
        }
        if (array_key_exists('agama', $d)) {
            $out['id_agama'] = $this->agamaMap()[(string) $d['agama']] ?? null;
        }
        if (array_key_exists('tanggal_lahir', $d)) {
            $out['tanggal_lahir'] = $this->teks($d['tanggal_lahir']);
        }
        if (array_key_exists('anak_ke', $d)) {
            $out['anak_ke'] = $this->angka($d['anak_ke']);
        }
        if (array_key_exists('persetujuan_ortu', $d)) {
            $out['persetujuan_ortu'] = (bool) $d['persetujuan_ortu'];
        }
        if (array_key_exists('pernyataan', $d)) {
            $out['pernyataan'] = (bool) $d['pernyataan'];
        }
        if (array_key_exists('jalan', $d) || array_key_exists('kelurahan', $d)) {
            $out['alamat'] = $this->gabungAlamat($d);
        }
        if (array_key_exists('langkah_terakhir', $d)) {
            $out['langkah_terakhir'] = (int) $d['langkah_terakhir'];
        }

        return $out;
    }

    private function agamaMap(): array
    {
        if ($this->agamaMap !== null) {
            return $this->agamaMap;
        }

        $rows = DB::table('agama')
            ->where('is_aktif', true)
            ->get(['id_agama', 'nama_agama']);
        $agama = [];

        foreach (self::AGAMA_NAMA as $kode => $namaYangDikenal) {
            $baris = $rows->first(fn ($row) => in_array(
                mb_strtolower(trim($row->nama_agama)),
                $namaYangDikenal,
                true
            ));

            if ($baris) {
                $agama[$kode] = (int) $baris->id_agama;
            }
        }

        return $this->agamaMap = $agama;
    }

    private function kodeAgama(?int $idAgama): string
    {
        if ($idAgama === null) {
            return '';
        }

        $kode = array_search($idAgama, $this->agamaMap(), true);

        return $kode === false ? '' : (string) $kode;
    }

    private function simpanAnak(int $id, array $d, bool $final): void
    {
        $alamat = [
            'jalan' => $this->teks($d['jalan'] ?? null),
            'rt' => $this->teks($d['rt'] ?? null),
            'rw' => $this->teks($d['rw'] ?? null),
            'kelurahan' => $this->teks($d['kelurahan'] ?? null),
            'kecamatan' => $this->teks($d['kecamatan'] ?? null),
            'kota' => $this->teks($d['kota'] ?? null),
            'provinsi' => $this->teks($d['provinsi'] ?? null),
            'dusun' => $this->teks($d['dusun'] ?? null),
            'kode_pos' => $this->teks($d['kode_pos'] ?? null),
            'telp' => $this->teks($d['telp'] ?? null),
            'no_hp' => $this->teks($d['no_hp_alamat'] ?? null),
        ];

        if ($final || array_filter($alamat, fn ($nilai) => $nilai !== null)) {
            DB::table('ppdb_alamat')->updateOrInsert(['id_pendaftaran' => $id], $alamat);
        }

        $periodik = [
            'tinggi_badan' => $this->angka($d['tinggi_badan'] ?? null),
            'berat_badan' => $this->angka($d['berat_badan'] ?? null),
            'gol_darah' => $this->teks($d['gol_darah'] ?? null),
            'jarak' => $this->teks($d['jarak'] ?? null),
            'jarak_km' => $this->desimal($d['jarak_km'] ?? null),
            'waktu_tempuh_jam' => $this->angka($d['waktu_jam'] ?? null),
            'waktu_tempuh_menit' => $this->angka($d['waktu_menit'] ?? null),
            'jumlah_saudara' => $this->angka($d['jumlah_saudara'] ?? null),
        ];

        if ($final || array_filter($periodik, fn ($nilai) => $nilai !== null)) {
            DB::table('ppdb_periodik')->updateOrInsert(['id_pendaftaran' => $id], $periodik);
        }

        if ($final) {
            DB::table('ppdb_orang_tua')->where('id_pendaftaran', $id)->delete();
        }

        foreach (self::HUBUNGAN as $index => $hubungan) {
            $payload = [
                'nama_lengkap' => $this->teks($d['ortu_nama_'.$index] ?? null),
                'nik' => $this->teks($d['ortu_nik_'.$index] ?? null),
                'tempat_lahir' => $this->teks($d['ortu_tempat_lahir_'.$index] ?? null),
                'tanggal_lahir' => $this->teks($d['ortu_tanggal_lahir_'.$index] ?? null),
                'pendidikan' => $this->teks($d['ortu_pendidikan_'.$index] ?? null),
                'id_pekerjaan' => $this->idPekerjaan($d['ortu_kerja_'.$index] ?? null),
                'penghasilan' => $this->teks($d['ortu_penghasilan_'.$index] ?? null),
                'berkebutuhan_khusus' => $this->teks($d['ortu_kebutuhan_'.$index] ?? null),
                'no_telepon' => $this->teks($d['ortu_hp_'.$index] ?? null),
                'alamat' => $this->teks($d['ortu_alamat_'.$index] ?? null),
            ];

            if ($final || array_filter($payload, fn ($nilai) => $nilai !== null)) {
                DB::table('ppdb_orang_tua')->updateOrInsert(
                    ['id_pendaftaran' => $id, 'hubungan' => $hubungan],
                    $payload
                );
            }
        }

        $namaPrestasi = $d['prestasi_nama'] ?? null;
        if ($final || ($d['prestasi_dikelola'] ?? null) === '1' || $namaPrestasi !== null) {
            DB::table('ppdb_prestasi')->where('id_pendaftaran', $id)->delete();

            foreach ((array) $namaPrestasi as $i => $nama) {
                if ($this->teks($nama) === null) {
                    continue;
                }

                DB::table('ppdb_prestasi')->insert([
                    'id_pendaftaran' => $id,
                    'jenis' => $this->teks($d['prestasi_jenis'][$i] ?? null),
                    'tingkat' => $this->teks($d['prestasi_tingkat'][$i] ?? null),
                    'nama_prestasi' => $this->teks($nama),
                    'tahun' => $this->teks($d['prestasi_tahun'][$i] ?? null),
                    'penyelenggara' => $this->teks($d['prestasi_penyelenggara'][$i] ?? null),
                ]);
            }
        }

        if ($final || ($d['beasiswa_dikelola'] ?? null) === '1' || array_key_exists('beasiswa_keterangan', $d)) {
            DB::table('ppdb_beasiswa')->where('id_pendaftaran', $id)->delete();

            foreach ((array) ($d['beasiswa_keterangan'] ?? []) as $i => $keterangan) {
                if ($this->teks($keterangan) === null) {
                    continue;
                }

                $jenis = $this->teks($d['beasiswa_jenis'][$i] ?? null);

                DB::table('ppdb_beasiswa')->insert([
                    'id_pendaftaran' => $id,
                    'jenis' => $jenis === '5' ? '05' : $jenis,
                    'keterangan' => $this->teks($keterangan),
                    'tahun_mulai' => $this->teks($d['beasiswa_tahun_mulai'][$i] ?? null),
                    'tahun_selesai' => $this->teks($d['beasiswa_tahun_selesai'][$i] ?? null),
                ]);
            }
        }

        if (array_key_exists('keluar_karena', $d)
            || array_key_exists('tanggal_keluar', $d)
            || array_key_exists('alasan_keluar', $d)) {
            $keluar = [
                'keluar_karena' => $this->teks($d['keluar_karena'] ?? null),
                'tanggal_keluar' => $this->teks($d['tanggal_keluar'] ?? null),
                'alasan' => $this->teks($d['alasan_keluar'] ?? null),
            ];

            DB::table('ppdb_keluar')->updateOrInsert(['id_pendaftaran' => $id], $keluar);
        }
    }

    private function gabungAlamat(array $d): ?string
    {
        if (array_key_exists('alamat', $d) && ! array_key_exists('jalan', $d)) {
            return $this->teks($d['alamat']);
        }

        $bagian = [];
        $jalan = $this->teks($d['jalan'] ?? null);
        $dusun = $this->teks($d['dusun'] ?? null);
        $rt = $this->teks($d['rt'] ?? null);
        $rw = $this->teks($d['rw'] ?? null);
        $kelurahan = $this->teks($d['kelurahan'] ?? null);
        $kecamatan = $this->teks($d['kecamatan'] ?? null);
        $kota = $this->teks($d['kota'] ?? null);
        $provinsi = $this->teks($d['provinsi'] ?? null);

        if ($jalan) {
            $bagian[] = $jalan;
        }
        if ($dusun) {
            $bagian[] = 'Dusun '.$dusun;
        }
        if ($rt || $rw) {
            $bagian[] = trim('RT '.($rt ?? '').'/RW '.($rw ?? ''));
        }
        if ($kelurahan) {
            $bagian[] = 'Kel. '.$kelurahan;
        }
        if ($kecamatan) {
            $bagian[] = 'Kec. '.$kecamatan;
        }
        if ($kota) {
            $bagian[] = $kota;
        }
        if ($provinsi) {
            $bagian[] = $provinsi;
        }

        return $bagian ? implode(', ', $bagian) : null;
    }

    private function idPekerjaan($kode): ?int
    {
        $nama = self::PEKERJAAN[(string) $kode] ?? null;

        if (! $nama) {
            return null;
        }

        return DB::table('pekerjaan')->where('nama_pekerjaan', $nama)->value('id_pekerjaan');
    }

    private function kodePekerjaan($id): string
    {
        if (! $id) {
            return '01';
        }

        $nama = DB::table('pekerjaan')->where('id_pekerjaan', $id)->value('nama_pekerjaan');
        $kode = array_search($nama, self::PEKERJAAN, true);

        return $kode === false ? '13' : (string) $kode;
    }

    private function normalJenis(string $jenis): string
    {
        return str_replace(' ', '', $jenis);
    }

    private function teks($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        if (is_bool($nilai)) {
            $nilai = $nilai ? '1' : '0';
        }

        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }

    private function angka($nilai): ?int
    {
        $nilai = $this->teks($nilai);

        return $nilai === null || ! is_numeric($nilai) ? null : (int) $nilai;
    }

    private function desimal($nilai): ?float
    {
        $nilai = $this->teks($nilai);

        return $nilai === null || ! is_numeric($nilai) ? null : (float) $nilai;
    }
}
