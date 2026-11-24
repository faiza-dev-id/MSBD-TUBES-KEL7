@extends('layouts.app')
@section('title', 'Profil Saya')
@section('heading', 'Profil Siswa')
@section('sub', 'Data master terkini siswa dan arsip informasi yang tercatat pada formulir PPDB.')
@section('content')
@php
    $ppdb = $ppdb ?? null;
    $dokumen = $dokumen ?? collect();
    $agamaOptions = $agamaOptions ?? [];
    $formatNilai = fn ($key, $value) => \App\Support\Referensi\OpsiPpdb::label($key, $value, $agamaOptions);
    $formatNilaiOrangTua = fn ($key, $value) => \App\Support\Referensi\OpsiPpdb::label($key, $value, $agamaOptions);
    $bidangPpdb = [
        'Informasi pendaftaran' => [
            'nomor_pendaftaran' => 'Nomor pendaftaran',
            'tanggal' => 'Tanggal pengajuan',
            'tahun_pelajaran' => 'Tahun pelajaran',
            'pendaftaran_untuk' => 'Pendaftaran untuk',
            'jenjang_tujuan' => 'Jenjang tujuan',
            'kelas_tujuan' => 'Kelas tujuan PPDB',
            'nis' => 'NIS',
            'sekolah_asal' => 'Sekolah asal',
            'no_seri_skhun' => 'Nomor seri SKHUN',
            'tahun_skhun' => 'Tahun SKHUN',
            'no_seri_ijazah' => 'Nomor seri ijazah',
            'tahun_ijazah' => 'Tahun ijazah',
            'no_peserta_ujian' => 'Nomor peserta ujian',
        ],
        'Data pribadi PPDB' => [
            'nama_lengkap' => 'Nama lengkap saat mendaftar',
            'jenis_kelamin' => 'Jenis kelamin',
            'nisn' => 'NISN',
            'nik' => 'NIK',
            'no_kk' => 'Nomor Kartu Keluarga',
            'tempat_lahir' => 'Tempat lahir',
            'tanggal_lahir' => 'Tanggal lahir',
            'agama' => 'Agama',
            'email' => 'Email',
            'no_hp' => 'Nomor HP',
            'anak_ke' => 'Anak ke',
            'status_keluarga' => 'Status dalam keluarga',
            'hobi' => 'Hobi',
            'cita_cita' => 'Cita-cita',
            'berkebutuhan_khusus' => 'Kebutuhan khusus',
            'kewarganegaraan' => 'Kewarganegaraan',
        ],
        'Alamat' => [
            'jalan' => 'Jalan',
            'dusun' => 'Dusun',
            'rt' => 'RT',
            'rw' => 'RW',
            'kelurahan' => 'Kelurahan',
            'kecamatan' => 'Kecamatan',
            'kota' => 'Kota / Kabupaten',
            'provinsi' => 'Provinsi',
            'kode_pos' => 'Kode pos',
            'telp' => 'Telepon rumah',
            'no_hp_alamat' => 'Nomor HP pada data alamat',
        ],
        'Data sosial dan periodik' => [
            'tempat_tinggal' => 'Tempat tinggal',
            'moda_transportasi' => 'Moda transportasi',
            'penerima_kps' => 'Penerima KPS',
            'no_kps' => 'Nomor KPS',
            'layak_pip' => 'Layak PIP',
            'alasan_pip' => 'Alasan PIP',
            'tinggi_badan' => 'Tinggi badan (cm)',
            'berat_badan' => 'Berat badan (kg)',
            'gol_darah' => 'Golongan darah',
            'jarak' => 'Jarak ke sekolah',
            'jarak_km' => 'Jarak (km)',
            'waktu_jam' => 'Waktu tempuh (jam)',
            'waktu_menit' => 'Waktu tempuh (menit)',
            'jumlah_saudara' => 'Jumlah saudara kandung',
        ],
        'Informasi keluarga' => [
            'nama_ortu_wali' => 'Nama orang tua / wali pada formulir',
            'persetujuan_ortu' => 'Persetujuan orang tua',
            'pernyataan' => 'Pernyataan pendaftar',
        ],
    ];
    $labelOrangTua = ['ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali'];
    $bidangOrangTua = [
        'nama' => 'Nama lengkap',
        'nik' => 'NIK',
        'tempat_lahir' => 'Tempat lahir',
        'tanggal_lahir' => 'Tanggal lahir',
        'pendidikan' => 'Pendidikan',
        'pekerjaan' => 'Pekerjaan',
        'penghasilan' => 'Penghasilan bulanan',
        'kebutuhan' => 'Kebutuhan khusus',
        'hp' => 'Nomor telepon',
        'alamat' => 'Alamat',
    ];
@endphp

@unless ($terhubung)
    <div class="card text-sm text-mute" role="status">
        Akun ini belum terhubung ke data siswa. Hubungi Tata Usaha untuk membantu pencocokan data.
    </div>
@else
<section class="card mb-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold">{{ $s['nama_lengkap'] }}</h2>
            <p class="mt-1 text-sm text-mute">NIS {{ $s['nis'] }} · NISN {{ $s['nisn'] }}</p>
        </div>
        <a href="{{ route('siswa.pengajuan') }}" class="btn btn-flame">Ajukan perubahan data</a>
    </div>
    <dl class="mt-5 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            'Status siswa' => $s['status'],
            'Kelas saat ini' => $s['kelas'],
            'Nama saat ini' => $s['nama_lengkap'],
            'Jenis kelamin saat ini' => $s['jenis_kelamin'],
            'Tempat, tanggal lahir' => $s['tempat_lahir'].', '.$s['tanggal_lahir'],
            'NIK saat ini' => $s['nik'],
            'Agama' => $s['agama'],
            'Nomor HP saat ini' => $s['no_hp'],
            'Email saat ini' => $s['email'],
            'Sekolah asal' => $s['sekolah_asal'],
            'Alamat saat ini' => $s['alamat'],
            'Ayah' => $s['ayah'],
            'Ibu' => $s['ibu'],
        ] as $label => $value)
            <div><dt class="text-mute">{{ $label }}</dt><dd class="font-medium">{{ $value ?: '-' }}</dd></div>
        @endforeach
    </dl>
</section>

<section class="card mb-4">
    <h2 class="sec-title">Rincian data master saat ini</h2>
    <p class="mb-4 text-sm text-mute">Perubahan yang diajukan akan tampil di sini setelah disetujui Wali Kelas. Informasi PPDB di bawah tetap menjadi arsip saat pendaftaran.</p>
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ([
            'Alamat siswa' => [
                'RT' => $s['alamat_rt'],
                'RW' => $s['alamat_rw'],
                'Kelurahan/Desa' => $s['alamat_kelurahan'],
                'Kecamatan' => $s['alamat_kecamatan'],
                'Kota/Kabupaten' => $s['alamat_kota'],
                'Provinsi' => $s['alamat_provinsi'],
                'Kode pos' => $s['alamat_kode_pos'],
            ],
            'Ayah' => [
                'Nama lengkap' => $s['ayah'],
                'Nomor telepon' => $s['ayah_no_telepon'],
                'Alamat' => $s['ayah_alamat'],
            ],
            'Ibu' => [
                'Nama lengkap' => $s['ibu'],
                'Nomor telepon' => $s['ibu_no_telepon'],
                'Alamat' => $s['ibu_alamat'],
            ],
            'Wali' => [
                'Nama lengkap' => $s['wali'],
                'Nomor telepon' => $s['wali_no_telepon'],
                'Email' => $s['wali_email'],
                'Alamat' => $s['wali_alamat'],
            ],
        ] as $judul => $data)
            <div class="rounded-xl border border-line p-4">
                <h3 class="mb-3 font-semibold">{{ $judul }}</h3>
                <dl class="grid gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                    @foreach ($data as $label => $value)
                        <div><dt class="text-mute">{{ $label }}</dt><dd class="font-medium">{{ $value ?: '-' }}</dd></div>
                    @endforeach
                </dl>
            </div>
        @endforeach
    </div>
</section>

@if ($ppdb)
    @foreach ($bidangPpdb as $judul => $bidang)
        <section class="card mb-4">
            <h2 class="sec-title">{{ $judul }}</h2>
            <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($bidang as $key => $label)
                    @php
                        $value = $ppdb[$key] ?? null;
                        if (is_bool($value)) {
                            $value = $value ? 'Ya' : 'Tidak';
                        }
                    @endphp
                    <div><dt class="text-mute">{{ $label }}</dt><dd class="font-medium">{{ $formatNilai($key, $value) }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endforeach

    <section class="card mb-4">
        <h2 class="sec-title">Data orang tua dan wali</h2>
        <div class="grid gap-4 lg:grid-cols-3">
            @foreach ($ppdb['ortu'] ?? [] as $index => $orangTua)
                @php $hubungan = ['ayah', 'ibu', 'wali'][$index] ?? 'wali'; @endphp
                <div class="rounded-xl border border-line p-4">
                    <h3 class="mb-3 font-semibold">{{ $labelOrangTua[$hubungan] }}</h3>
                    <dl class="space-y-2 text-sm">
                        @foreach ($bidangOrangTua as $key => $label)
                            <div><dt class="text-mute">{{ $label }}</dt><dd class="font-medium">{{ $formatNilaiOrangTua($key, $orangTua[$key] ?? null) }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>
    </section>

    @foreach (['prestasi' => 'Prestasi', 'beasiswa' => 'Beasiswa'] as $key => $judul)
        <section class="card mb-4">
            <h2 class="sec-title">{{ $judul }}</h2>
            @forelse ($ppdb[$key] ?? [] as $baris)
                <dl class="mb-4 grid gap-x-6 gap-y-2 border-b border-line pb-4 text-sm last:mb-0 last:border-0 last:pb-0 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($baris as $field => $value)
                        <div><dt class="text-mute">{{ str_replace('_', ' ', ucfirst($field)) }}</dt><dd class="font-medium">{{ $value ?: '-' }}</dd></div>
                    @endforeach
                </dl>
            @empty
                <p class="text-sm text-mute">Tidak ada data {{ strtolower($judul) }} pada formulir PPDB.</p>
            @endforelse
        </section>
    @endforeach
@else
    <div class="card mb-4 text-sm text-mute">
        Data formulir PPDB belum terhubung ke akun ini. Hubungi Tata Usaha untuk membantu pencocokan data pendaftaran.
    </div>
@endif

<section class="card">
    <h2 class="sec-title">Dokumen PPDB</h2>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead><tr class="border-b-2 border-line text-left text-xs text-mute"><th class="p-2">Jenis</th><th class="p-2">Nama berkas</th><th class="p-2">Status</th><th class="p-2">Tanggal unggah</th></tr></thead>
            <tbody>
                @forelse ($dokumen as $item)
                    <tr class="border-b border-line"><td class="p-2.5">{{ $item->jenis_dokumen }}</td><td class="p-2.5">{{ $item->nama_file_asli }}</td><td class="p-2.5">{{ $item->status_verifikasi ?? '-' }}</td><td class="p-2.5">{{ $item->diunggah_pada ?? '-' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="p-4 text-center text-mute">Belum ada dokumen PPDB yang tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endunless
@endsection