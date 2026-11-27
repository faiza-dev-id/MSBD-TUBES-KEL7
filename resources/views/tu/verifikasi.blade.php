@extends('layouts.app')
@section('title', 'Verifikasi Berkas')
@section('heading', 'Verifikasi: ' . $row['nama'])
@section('sub', 'NISN ' . $row['nisn'] . ' · masuk ' . $row['tgl'])
@section('content')
@php
    $bagianData = [
        'Pendaftaran dan data siswa' => [
            'nomor_pendaftaran' => 'Kode pendaftaran',
            'tanggal' => 'Tanggal pengajuan',
            'tahun_pelajaran' => 'Tahun pelajaran',
            'nis' => 'NIS',
            'pendaftaran_untuk' => 'Pendaftaran untuk',
            'jenjang_tujuan' => 'Jenjang tujuan',
            'kelas_tujuan' => 'Kelas dan jurusan',
            'sekolah_asal' => 'Asal sekolah',
            'no_seri_skhun' => 'Nomor seri SKHUN',
            'tahun_skhun' => 'Tahun SKHUN',
            'no_seri_ijazah' => 'Nomor seri ijazah',
            'tahun_ijazah' => 'Tahun ijazah',
            'no_peserta_ujian' => 'Nomor peserta ujian',
            'nama_lengkap' => 'Nama lengkap',
            'jenis_kelamin' => 'Jenis kelamin',
            'nisn' => 'NISN',
            'nik' => 'NIK',
            'tempat_lahir' => 'Tempat lahir',
            'tanggal_lahir' => 'Tanggal lahir',
            'anak_ke' => 'Anak ke',
            'status_keluarga' => 'Status dalam keluarga',
            'agama' => 'Agama',
            'berkebutuhan_khusus' => 'Kebutuhan khusus',
            'no_kk' => 'Nomor Kartu Keluarga',
            'no_hp' => 'Nomor HP',
            'email' => 'Email',
            'hobi' => 'Hobi',
            'cita_cita' => 'Cita-cita',
            'tempat_tinggal' => 'Tempat tinggal',
            'moda_transportasi' => 'Moda transportasi',
            'penerima_kps' => 'Penerima KPS/PKH/KIP',
            'no_kps' => 'Nomor KPS/KKS/PKH/KIP',
            'kewarganegaraan' => 'Kewarganegaraan',
            'layak_pip' => 'Layak diusulkan PIP',
            'alasan_pip' => 'Alasan PIP',
        ],
        'Alamat' => [
            'jalan' => 'Jalan',
            'rt' => 'RT',
            'rw' => 'RW',
            'dusun' => 'Dusun',
            'kelurahan' => 'Kelurahan',
            'kecamatan' => 'Kecamatan',
            'kota' => 'Kota/Kabupaten',
            'provinsi' => 'Provinsi',
            'kode_pos' => 'Kode pos',
            'telp' => 'Telepon',
            'no_hp_alamat' => 'HP',
        ],
        'Data periodik' => [
            'tinggi_badan' => 'Tinggi badan (cm)',
            'berat_badan' => 'Berat badan (kg)',
            'gol_darah' => 'Golongan darah',
            'jarak' => 'Jarak ke sekolah',
            'jarak_km' => 'Jarak (km)',
            'waktu_jam' => 'Waktu tempuh (jam)',
            'waktu_menit' => 'Waktu tempuh (menit)',
            'jumlah_saudara' => 'Jumlah saudara kandung',
        ],
        'Pernyataan dan persetujuan' => [
            'nama_ortu_wali' => 'Nama orang tua/wali',
            'pernyataan' => 'Pernyataan data sesuai dokumen',
            'persetujuan_ortu' => 'Persetujuan orang tua/wali',
        ],
    ];
@endphp
<form class="grid lg:grid-cols-[1.3fr_1fr] gap-4 items-start" method="POST" action="{{ route('tu.verifikasi.simpan') }}">
    @csrf
    <input type="hidden" name="id_pendaftaran" value="{{ $row['id'] ?? '' }}">
    <div class="space-y-4">
        <div class="card">
            <h2 class="font-serif text-lg font-bold mb-1">Seluruh data pendaftar</h2>
            <p class="text-xs text-mute mb-4">Informasi ini hanya dapat dilihat Tata Usaha. Perubahan data dilakukan oleh calon siswa melalui formulir perbaikan.</p>
            @foreach ($bagianData as $judulBagian => $fields)
                <section class="border-t border-line pt-3 mt-3 first:border-0 first:pt-0 first:mt-0">
                    <h3 class="font-semibold text-sm mb-2">{{ $judulBagian }}</h3>
                    <dl class="grid sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        @foreach ($fields as $key => $label)
                            @php
                                $nilai = $detail[$key] ?? null;
                                $tampilan = is_bool($nilai)
                                    ? ($nilai ? 'Ya' : 'Tidak')
                                    : \App\Support\Referensi\OpsiPpdb::label($key, $nilai, $agamaOptions);
                            @endphp
                            <div>
                                <dt class="text-xs text-mute">{{ $label }}</dt>
                                <dd class="font-medium break-words">{{ $tampilan }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach

            @foreach (['Ayah', 'Ibu', 'Wali'] as $index => $hubungan)
                @php $ortu = $detail['ortu'][$index] ?? []; @endphp
                <section class="border-t border-line pt-3 mt-3">
                    <h3 class="font-semibold text-sm mb-2">Data {{ $hubungan }}</h3>
                    <dl class="grid sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        @foreach ([
                            'nama' => 'Nama',
                            'nik' => 'NIK',
                            'tempat_lahir' => 'Tempat lahir',
                            'tanggal_lahir' => 'Tanggal lahir',
                            'pendidikan' => 'Pendidikan',
                            'pekerjaan' => 'Pekerjaan',
                            'penghasilan' => 'Penghasilan bulanan',
                            'kebutuhan' => 'Kebutuhan khusus',
                            'hp' => 'Nomor HP',
                            'alamat' => 'Alamat',
                        ] as $key => $label)
                            <div>
                                <dt class="text-xs text-mute">{{ $label }}</dt>
                            <dd class="font-medium break-words">{{ \App\Support\Referensi\OpsiPpdb::label($key, $ortu[$key] ?? null, $agamaOptions) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach

            <section class="border-t border-line pt-3 mt-3">
                <h3 class="font-semibold text-sm mb-2">Nomor dokumen</h3>
                <dl class="grid sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div><dt class="text-xs text-mute">Nomor akta kelahiran</dt><dd class="font-medium">{{ $detail['nomor_dokumen_1'] ?: '-' }}</dd></div>
                    <div><dt class="text-xs text-mute">Nomor ijazah/SKL</dt><dd class="font-medium">{{ $detail['nomor_dokumen_2'] ?: '-' }}</dd></div>
                </dl>
            </section>

            <section class="border-t border-line pt-3 mt-3">
                <h3 class="font-semibold text-sm mb-2">Prestasi</h3>
                @forelse ($detail['prestasi'] as $prestasi)
                    <p class="text-sm border-b border-line py-2 last:border-0">
                        {{ $prestasi['nama'] ?: 'Prestasi tanpa nama' }} · jenis {{ \App\Support\Referensi\OpsiPpdb::label('prestasi_jenis', $prestasi['jenis'] ?? null) }} · tingkat {{ \App\Support\Referensi\OpsiPpdb::label('prestasi_tingkat', $prestasi['tingkat'] ?? null) }} · tahun {{ $prestasi['tahun'] ?: '-' }} · penyelenggara {{ $prestasi['penyelenggara'] ?: '-' }}
                    </p>
                @empty
                    <p class="text-sm text-mute">Tidak ada data prestasi.</p>
                @endforelse
            </section>

            <section class="border-t border-line pt-3 mt-3">
                <h3 class="font-semibold text-sm mb-2">Beasiswa</h3>
                @forelse ($detail['beasiswa'] as $beasiswa)
                    <p class="text-sm border-b border-line py-2 last:border-0">
                        {{ $beasiswa['keterangan'] ?: 'Tanpa keterangan' }} · jenis {{ \App\Support\Referensi\OpsiPpdb::label('beasiswa_jenis', $beasiswa['jenis'] ?? null) }} · {{ $beasiswa['tahun_mulai'] ?: '-' }}–{{ $beasiswa['tahun_selesai'] ?: '-' }}
                    </p>
                @empty
                    <p class="text-sm text-mute">Tidak ada data beasiswa.</p>
                @endforelse
            </section>
        </div>

        <div class="card">
            <h2 class="font-serif text-lg font-bold mb-3">Berkas yang diunggah</h2>
            @forelse ($berkas as $b)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line py-2.5 text-sm last:border-0">
                    <div class="min-w-0">
                        <b>{{ $b['jenis'] }}</b>
                        <div class="text-xs text-mute">{{ $b['nama'] }} · {{ $b['tgl'] }}</div>
                    </div>
                    @if (!empty($b['url']))
                        <button type="button"
                                class="text-sm font-semibold text-flame-dark hover:underline cursor-pointer"
                                @click='$dispatch("preview-berkas", { url: @json($b["url"]), unduhUrl: @json($b["unduhUrl"]), nama: @json($b["nama"] ?: "Berkas"), jenis: @json($b["jenis"]), tipe: @json($b["tipe"]) })'>
                            Lihat berkas
                        </button>
                    @else
                        <span class="text-sm text-mute">Berkas tidak tersedia</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-mute">Belum ada berkas yang diunggah.</p>
            @endforelse
        </div>

    </div>

    <div class="card lg:sticky lg:top-4">
        <h2 class="sec-title">Keputusan</h2>
        <div class="space-y-3">
            <div>
                <label class="label" for="id_alasan">Alasan pengembalian</label>
                <select class="input" id="id_alasan" name="id_alasan">
                    <option value="">Pilih alasan</option>
                    @foreach ($alasan as $a)
                        <option value="{{ $a }}" @selected(old('id_alasan') === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="id_dokumen_perbaikan">Dokumen yang perlu diperbaiki (opsional)</label>
                <select class="input" id="id_dokumen_perbaikan" name="id_dokumen_perbaikan">
                    <option value="">Tidak ada dokumen khusus</option>
                    @foreach ($berkas as $b)
                        <option value="{{ $b['id'] }}" @selected((string) old('id_dokumen_perbaikan') === (string) $b['id'])>{{ $b['jenis'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-mute">Pilih hanya jika ada kesalahan pada dokumen tersebut. Untuk perbaikan data, cukup jelaskan pada catatan siswa.</p>
            </div>
            <div>
                <label class="label" for="catatan">Catatan untuk siswa</label>
                <textarea class="input" id="catatan" name="catatan" rows="3">{{ old('catatan') }}</textarea>
            </div>
            <button class="btn w-full" name="hasil" value="terima">Terima berkas</button>
            <button class="btn btn-ghost w-full" name="hasil" value="kembalikan">Kembalikan untuk diperbaiki</button>
            <hr class="border-line">
            <button class="btn btn-flame w-full" name="hasil" value="sahkan">Sahkan &amp; buat akun siswa</button>
            <p class="text-[11px] text-mute">Akun siswa dibuat otomatis memakai NISN.</p>
        </div>
    </div>
</form>
@endsection
