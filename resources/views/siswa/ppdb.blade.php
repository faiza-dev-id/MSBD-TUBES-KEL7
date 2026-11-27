@extends('layouts.publik')
@section('title', ($modePerbaikan ?? false) ? 'Perbaikan PPDB' : 'Formulir PPDB')
@section('heading', ($modePerbaikan ?? false) ? 'Perbaiki Data Calon Siswa' : 'Formulir Data Calon Siswa')
@section('sub', ($modePerbaikan ?? false) ? 'Perbaiki data atau berkas sesuai catatan Tata Usaha, lalu kirim kembali pengajuan yang sama.' : 'Isi sesuai dokumen asli (KK, akta, ijazah). Data akan diperiksa Tata Usaha sebelum disahkan.')
@section('content')
@php
    $form = $form ?? [];
    $kelasTujuan = $kelasTujuan ?? [];
    $modePerbaikan = $modePerbaikan ?? false;
    $kelasTerpilih = old('kelas_tujuan', $form['kelas_tujuan'] ?? '');
    $kelasDikunci = $modePerbaikan && filled($form['kelas_tujuan'] ?? null);
    $opsi = \App\Support\Referensi\OpsiPpdb::pilihan($agamaOptions);
    $punyaDraf = filled($token_draf ?? null);
    $nilaiPilihan = fn ($nama, $draf = '') => (string) old($nama, $punyaDraf ? $draf : '');
@endphp
@if ($modePerbaikan)
    <div class="mb-4 rounded-xl border border-flame/30 bg-flame/5 px-4 py-3 text-sm text-navy" role="status">
        Data yang sudah diisi tetap tersedia. Perbaiki bagian yang keliru dan unggah ulang hanya dokumen yang diminta.
    </div>
@endif
<div class="mb-5">
    <div class="flex gap-1.5" id="stepper">
        @foreach (range(0, 3) as $i)
            <div data-ind="{{ $i }}" class="flex-1 h-1.5 rounded-full bg-line"></div>
        @endforeach
    </div>
    <p id="stepLabel" class="mt-2 text-sm font-semibold text-navy"></p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad" role="alert">
        <p>{{ $errors->first() }}</p>
        @if ($errors->first('nisn') === \App\Services\PpdbService::PESAN_IDENTITAS_TIDAK_DAPAT_DIPROSES)
            <a href="{{ route('ppdb.status') }}" class="inline-block mt-2 font-semibold underline">Buka Cek Status dan perbaiki pengajuan yang sama</a>
        @endif
    </div>
@endif
<div id="ringkas" class="hidden mb-4 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad" role="alert"></div>

<form class="space-y-4" method="POST" action="{{ route('ppdb.store') }}" enctype="multipart/form-data" id="ppdb" novalidate>
    @csrf
    <input type="hidden" name="token_draf" value="{{ $token_draf }}">
    <input type="hidden" name="langkah_terakhir" id="langkah_terakhir" value="{{ old('langkah_terakhir', $form['langkah_terakhir'] ?? 0) }}">
    <div data-step="0" class="space-y-4">
        <div class="card">
            <h2 class="sec-title">Header Formulir</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="label" for="tanggal">Tanggal</label>
                    <input class="input" id="tanggal" name="tanggal" type="date" value="{{ $form['tanggal'] }}">
                </div>
                <div>
                    <label class="label" for="tahun_pelajaran">Tahun Pelajaran</label>
                    <input class="input" id="tahun_pelajaran" name="tahun_pelajaran" type="text" value="{{ $form['tahun_pelajaran'] }}">
                </div>
                <div>
                    <label class="label" for="nis">NIS</label>
                    <input class="input" id="nis" name="nis" type="number" value="{{ $form['nis'] }}">
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="sec-title">A. Registrasi Peserta Didik</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="label" for="pendaftaran_untuk">Pendaftaran untuk</label>
                    <input class="input bg-slate-50 text-slate-700 font-medium cursor-default select-none"
                           id="pendaftaran_untuk" type="text" value="SMA" readonly tabindex="-1" aria-readonly="true">
                    <input type="hidden" name="pendaftaran_untuk" value="SMA">
                </div>
                <div>
                    <label class="label" for="jenjang_tujuan">Jenjang tujuan</label>
                    <input class="input bg-slate-50 text-slate-700 font-medium cursor-default select-none"
                           id="jenjang_tujuan" type="text" value="SMA" readonly tabindex="-1" aria-readonly="true">
                    <input type="hidden" name="jenjang_tujuan" value="SMA">
                </div>
                <div>
                    <label class="label" for="kelas_tujuan">Kelas tujuan</label>
                    <select class="input" id="kelas_tujuan" name="kelas_tujuan" data-wajib data-searchable @disabled($kelasDikunci)>
                        <option value="" @selected($kelasTerpilih === '')>Pilih kelas tujuan</option>
                        @foreach ($kelasTujuan as $namaKelas)
                            <option value="{{ $namaKelas }}" @selected($kelasTerpilih === $namaKelas)>{{ $namaKelas }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-mute mt-1">Termasuk jurusan dan rombel.</p>
                    @if ($kelasDikunci)
                        <input type="hidden" name="kelas_tujuan" value="{{ $form['kelas_tujuan'] }}">
                        <p class="mt-1 text-xs text-mute">Kelas yang sudah dikirim tidak dapat diubah.</p>
                    @endif
                    @if (empty($kelasTujuan))
                        <p class="text-xs text-bad mt-1" role="alert">Kelas aktif belum tersedia untuk tahun ajaran aktif. Hubungi Tata Usaha.</p>
                    @endif
                </div>
                <div>
                    <label class="label" for="no_seri_skhun">No. Seri SKHUN SMP</label>
                    <input class="input" id="no_seri_skhun" name="no_seri_skhun" type="text" value="{{ $form['no_seri_skhun'] }}">
                </div>
                <div>
                    <label class="label" for="tahun_skhun">Tahun SKHUN</label>
                    <input class="input" id="tahun_skhun" name="tahun_skhun" type="number" value="{{ $form['tahun_skhun'] }}">
                </div>
                <div>
                    <label class="label" for="no_seri_ijazah">No. Seri Ijazah SMP</label>
                    <input class="input" id="no_seri_ijazah" name="no_seri_ijazah" type="text" value="{{ $form['no_seri_ijazah'] }}">
                </div>
                <div>
                    <label class="label" for="tahun_ijazah">Tahun Ijazah</label>
                    <input class="input" id="tahun_ijazah" name="tahun_ijazah" type="number" value="{{ $form['tahun_ijazah'] }}">
                </div>
                <div>
                    <label class="label" for="sekolah_asal">Asal Sekolah SMP</label>
                    <input class="input @error('sekolah_asal') !border-bad @enderror" id="sekolah_asal" name="sekolah_asal" type="text" value="{{ old('sekolah_asal', $form['sekolah_asal']) }}" data-wajib>
                    @error('sekolah_asal') <p class="err text-xs text-bad mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="no_peserta_ujian">No. Peserta Ujian SMP</label>
                    <input class="input" id="no_peserta_ujian" name="no_peserta_ujian" type="text" value="{{ $form['no_peserta_ujian'] }}">
                </div>
                <div>
                    <label class="label" for="hobi">Hobi</label>
                    <select class="input" id="hobi" name="hobi" data-searchable>
                        <option value="" @selected($nilaiPilihan('hobi', $form['hobi'] ?? '') === '')>Pilih hobi</option>
                        @foreach ($opsi['hobi'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($nilaiPilihan('hobi', $form['hobi'] ?? '') === (string) $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="cita_cita">Cita-cita</label>
                    <select class="input" id="cita_cita" name="cita_cita" data-searchable>
                        <option value="" @selected($nilaiPilihan('cita_cita', $form['cita_cita'] ?? '') === '')>Pilih cita-cita</option>
                        @foreach ($opsi['cita_cita'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($nilaiPilihan('cita_cita', $form['cita_cita'] ?? '') === (string) $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card" x-data="{ kps: '{{ old('penerima_kps', $form['penerima_kps']) }}', wna: '{{ old('kewarganegaraan', $form['kewarganegaraan']) }}' }">
            <h2 class="sec-title">B. Data Pribadi</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="label" for="nama_lengkap">Nama lengkap</label>
                    <input class="input @error('nama_lengkap') !border-bad @enderror" id="nama_lengkap" name="nama_lengkap" type="text" value="{{ old('nama_lengkap', $form['nama_lengkap']) }}" data-wajib>
                    @error('nama_lengkap') <p class="err text-xs text-bad mt-1" role="alert">{{ $message }}</p> @enderror
                    <p class="text-[11px] text-mute mt-1">Sesuai akta kelahiran</p>
                </div>
                @if (!$modePerbaikan)
                    <div data-password-field>
                        <label class="label" for="password">Buat kata sandi</label>
                        <div class="relative" x-data="{ showPassword: false }">
                            <input class="input pr-11" id="password" name="password" :type="showPassword ? 'text' : 'password'" type="password" autocomplete="new-password" minlength="8" data-wajib>
                            <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="showPassword" class="absolute right-0 top-1/2 flex size-10 -translate-y-1/2 items-center justify-center rounded-r-xl text-slate-400 hover:text-slate-600">
                                <svg x-show="!showPassword" class="block w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.432 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="showPassword" class="block w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 2.036 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5c.993 0 1.953-.138 2.85-.396M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.432 0 .639a10.52 10.52 0 0 1-4.293 5.3" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                </svg>
                            </button>
                        </div>
                        <p class="err text-xs text-bad mt-1 hidden" data-password-error role="alert"></p>
                        <p class="text-[11px] text-mute mt-1">Minimal 8 karakter. Akun hanya dapat digunakan setelah disahkan Tata Usaha.</p>
                    </div>
                    <div data-password-field>
                        <label class="label" for="password_confirmation">Ulangi kata sandi</label>
                        <div class="relative" x-data="{ showPassword: false }">
                            <input class="input pr-11" id="password_confirmation" name="password_confirmation" :type="showPassword ? 'text' : 'password'" type="password" autocomplete="new-password" minlength="8" data-wajib>
                            <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="showPassword" class="absolute right-0 top-1/2 flex size-10 -translate-y-1/2 items-center justify-center rounded-r-xl text-slate-400 hover:text-slate-600">
                                <svg x-show="!showPassword" class="block w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.432 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="showPassword" class="block w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 2.036 11.68a1.012 1.012 0 0 0 0 .639C3.423 16.49 7.36 19.5 12 19.5c.993 0 1.953-.138 2.85-.396M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.432 0 .639a10.52 10.52 0 0 1-4.293 5.3" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18" />
                                </svg>
                            </button>
                        </div>
                        <p class="err text-xs text-bad mt-1 hidden" data-password-error role="alert"></p>
                    </div>
                @endif
                <div>
                    <span class="label">Jenis kelamin <span class="text-bad">*</span></span>
                    <div class="flex flex-wrap items-center gap-4 min-h-11 text-sm">
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="jenis_kelamin" value="L" class="size-4 accent-flame" @checked(old('jenis_kelamin', $form['jenis_kelamin']) === 'L')> Laki-laki</label>
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="jenis_kelamin" value="P" class="size-4 accent-flame" @checked(old('jenis_kelamin', $form['jenis_kelamin']) === 'P')> Perempuan</label>
                    </div>
                </div>
                <div>
                    <label class="label" for="nisn">NISN</label>
                    <input class="input @error('nisn') !border-bad @enderror" id="nisn" name="nisn" type="text" value="{{ old('nisn', $form['nisn']) }}" data-wajib data-digit="10" inputmode="numeric" maxlength="10">
                    @error('nisn') <p class="err text-xs text-bad mt-1" role="alert">{{ $message }}</p> @enderror
                    <p class="text-[11px] text-mute mt-1">10 digit, termasuk angka 0 di depan jika ada. Jika sudah menyimpan draf, lanjutkan melalui tautan draf yang sama.</p>
                </div>
                <div>
                    <label class="label" for="nik">NIK</label>
                    <input class="input" id="nik" name="nik" type="text" value="{{ old('nik', $form['nik']) }}" data-wajib data-digit="16" inputmode="numeric" maxlength="16">
                    <p class="text-[11px] text-mute mt-1">16 digit, termasuk angka 0 di depan jika ada.</p>
                </div>
                <div>
                    <label class="label" for="tempat_lahir">Tempat lahir</label>
                    <input class="input" id="tempat_lahir" name="tempat_lahir" type="text" value="{{ old('tempat_lahir', $form['tempat_lahir']) }}" data-wajib>
                </div>
                <div>
                    <label class="label" for="tanggal_lahir">Tanggal lahir</label>
                    <input class="input @error('tanggal_lahir') !border-bad @enderror" id="tanggal_lahir" name="tanggal_lahir" type="date" value="{{ old('tanggal_lahir', $form['tanggal_lahir']) }}" data-wajib>
                    @error('tanggal_lahir') <p class="err text-xs text-bad mt-1" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="anak_ke">Anak ke-</label>
                    <input class="input" id="anak_ke" name="anak_ke" type="number" value="{{ $form['anak_ke'] }}" min="1">
                </div>
                <div>
                    <label class="label" for="status_keluarga">Status dalam keluarga</label>
                    <select class="input" id="status_keluarga" name="status_keluarga" data-searchable>
                        <option value="" @selected($nilaiPilihan('status_keluarga', $form['status_keluarga'] ?? '') === '')>Pilih status dalam keluarga</option>
                        @foreach ($opsi['status_keluarga'] as $status)
                            <option value="{{ $status }}" @selected($nilaiPilihan('status_keluarga', $form['status_keluarga'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="agama">Agama</label>
                    <select class="input" id="agama" name="agama" data-wajib data-searchable>
                        <option value="" @selected($nilaiPilihan('agama', $form['agama'] ?? '') === '')>Pilih agama</option>
                        @foreach ($opsi['agama'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($nilaiPilihan('agama', $form['agama'] ?? '') === (string) $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="berkebutuhan_khusus">Berkebutuhan Khusus</label>
                    <select class="input" id="berkebutuhan_khusus" name="berkebutuhan_khusus" data-searchable>
                        <option value="" @selected($nilaiPilihan('berkebutuhan_khusus', $form['berkebutuhan_khusus'] ?? '') === '')>Pilih kebutuhan khusus</option>
                        @foreach ($opsi['kebutuhan'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($nilaiPilihan('berkebutuhan_khusus', $form['berkebutuhan_khusus'] ?? '') === (string) $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="no_kk">Nomor Kartu Keluarga</label>
                    <input class="input" id="no_kk" name="no_kk" type="text" value="{{ old('no_kk', $form['no_kk']) }}" data-wajib data-digit="16" inputmode="numeric" maxlength="16">
                    <p class="text-[11px] text-mute mt-1">16 digit. Nomor KK dapat digunakan bersama oleh anggota keluarga dalam satu KK.</p>
                </div>
                <div>
                    <label class="label" for="no_hp">No. HP / WhatsApp</label>
                    <input class="input @error('no_hp') !border-bad @enderror" id="no_hp" name="no_hp" type="text" value="{{ old('no_hp', $form['no_hp']) }}" data-wajib data-hp inputmode="tel">
                    @error('no_hp') <p class="err text-xs text-bad mt-1" role="alert">{{ $message }}</p> @enderror
                    <p class="text-[11px] text-mute mt-1">Kabar status pendaftaran dikirim ke nomor ini</p>
                </div>
                <div>
                    <label class="label" for="email">e-mail Pribadi</label>
                    <input class="input" id="email" name="email" type="email" value="{{ $form['email'] }}">
                </div>
                <div>
                    <label class="label" for="tempat_tinggal">Tempat Tinggal</label>
                    <select class="input" id="tempat_tinggal" name="tempat_tinggal" data-searchable>
                        <option value="" @selected($nilaiPilihan('tempat_tinggal', $form['tempat_tinggal'] ?? '') === '')>Pilih tempat tinggal</option>
                        @foreach ($opsi['tempat_tinggal'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($nilaiPilihan('tempat_tinggal', $form['tempat_tinggal'] ?? '') === (string) $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="moda_transportasi">Moda Transportasi</label>
                    <select class="input" id="moda_transportasi" name="moda_transportasi" data-searchable>
                        <option value="" @selected($nilaiPilihan('moda_transportasi', $form['moda_transportasi'] ?? '') === '')>Pilih moda transportasi</option>
                        @foreach ($opsi['moda_transportasi'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($nilaiPilihan('moda_transportasi', $form['moda_transportasi'] ?? '') === (string) $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <span class="label">Penerima KPS/PKH/KIP</span>
                    <div class="flex flex-wrap items-center gap-4 min-h-11 text-sm">
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="penerima_kps" value="YA" class="size-4 accent-flame" x-model="kps" @checked(old('penerima_kps', $form['penerima_kps']) === 'YA')> YA</label>
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="penerima_kps" value="TIDAK" class="size-4 accent-flame" x-model="kps" @checked(old('penerima_kps', $form['penerima_kps']) === 'TIDAK')> TIDAK</label>
                    </div>
                </div>
                <div x-show="kps === 'YA'">
                    <label class="label" for="no_kps">No. KPS/KKS/PKH/KIP</label>
                    <input class="input" id="no_kps" name="no_kps" type="text" value="{{ $form['no_kps'] }}">
                </div>
                <div>
                    <span class="label">Kewarganegaraan</span>
                    <div class="flex flex-wrap items-center gap-4 min-h-11 text-sm">
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="kewarganegaraan" value="WNI" class="size-4 accent-flame" x-model="wna" @checked(old('kewarganegaraan', $form['kewarganegaraan']) === 'WNI')> WNI</label>
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="kewarganegaraan" value="WNA" class="size-4 accent-flame" x-model="wna" @checked(old('kewarganegaraan', $form['kewarganegaraan']) === 'WNA')> WNA</label>
                    </div>
                </div>
                <div x-show="wna === 'WNA'">
                    <label class="label" for="negara">Sebutkan nama negara</label>
                    <input class="input" id="negara" name="negara" type="text" value="{{ $form['negara'] }}">
                </div>
                <div>
                    <span class="label">Layak diusulkan PIP</span>
                    <div class="flex flex-wrap items-center gap-4 min-h-11 text-sm">
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="layak_pip" value="YA" class="size-4 accent-flame" @checked($form['layak_pip'] === 'YA')> YA</label>
                        <label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" name="layak_pip" value="TIDAK" class="size-4 accent-flame" @checked($form['layak_pip'] === 'TIDAK')> TIDAK</label>
                    </div>
                </div>
                <div>
                    <label class="label" for="alasan_pip">Alasan Layak PIP</label>
                    <select class="input" id="alasan_pip" name="alasan_pip" data-searchable>
                        <option value="" @selected($nilaiPilihan('alasan_pip', $form['alasan_pip'] ?? '') === '')>Pilih alasan layak PIP</option>
                        @foreach ($opsi['alasan_pip'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($nilaiPilihan('alasan_pip', $form['alasan_pip'] ?? '') === (string) $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="sec-title">B. Alamat</h2>
            <x-wilayah
                wajib="1"
                :provinsi="$form['provinsi'] ?? ''"
                :kota="$form['kota'] ?? ''"
                :kecamatan="$form['kecamatan'] ?? ''"
                :kelurahan="$form['kelurahan'] ?? ''"
            />
            <div class="grid grid-cols-2 sm:grid-cols-[1fr_90px_90px] gap-4 mt-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="label" for="jalan">Jalan</label>
                    <input class="input" id="jalan" name="jalan" type="text" value="{{ old('jalan', $form['jalan']) }}" data-wajib>
                </div>
                <div>
                    <label class="label" for="rt">RT</label>
                    <input class="input" id="rt" name="rt" type="text" value="{{ old('rt', $form['rt']) }}" data-wajib>
                </div>
                <div>
                    <label class="label" for="rw">RW</label>
                    <input class="input" id="rw" name="rw" type="text" value="{{ old('rw', $form['rw']) }}" data-wajib>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                <div>
                    <label class="label" for="telp">Telp</label>
                    <input class="input" id="telp" name="telp" type="text" value="{{ $form['telp'] }}">
                </div>
                <div>
                    <label class="label" for="no_hp_alamat">Hp</label>
                    <input class="input" id="no_hp_alamat" name="no_hp_alamat" type="text" value="{{ $form['no_hp_alamat'] }}" data-hp inputmode="tel">
                </div>
                <div>
                    <label class="label" for="dusun">Nama dusun</label>
                    <input class="input" id="dusun" name="dusun" type="text" value="{{ $form['dusun'] }}">
                </div>
                <div>
                    <label class="label" for="kode_pos">Kode Pos</label>
                    <input class="input" id="kode_pos" name="kode_pos" type="text" value="{{ $form['kode_pos'] }}">
                </div>
            </div>
        </div>
    </div>

    <div data-step="1" class="space-y-4 hidden">
        <p class="text-sm text-mute">NIK orang tua/wali dapat sama pada pengajuan saudara dalam satu keluarga.</p>
        @foreach (['C. Data Ayah', 'D. Data Ibu', 'E. Data Wali'] as $judulOrtu)
            @php $i = $loop->index; $o = $form['ortu'][$i]; @endphp
            <div class="card">
                <h2 class="sec-title">{{ $judulOrtu }}</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="label" for="ortu_nama_{{ $i }}">Nama</label>
                        <input class="input" id="ortu_nama_{{ $i }}" name="ortu_nama_{{ $i }}" type="text" value="{{ old('ortu_nama_'.$i, $o['nama']) }}" @if ($i < 2) data-wajib @endif>
                    </div>
                    <div>
                        <label class="label" for="ortu_nik_{{ $i }}">NIK</label>
                        <input class="input" id="ortu_nik_{{ $i }}" name="ortu_nik_{{ $i }}" type="text" value="{{ old('ortu_nik_'.$i, $o['nik']) }}" data-digit="16" inputmode="numeric" maxlength="16">
                    </div>
                    <div>
                        <label class="label" for="ortu_tanggal_lahir_{{ $i }}">Tgl. Lahir</label>
                        <input class="input" id="ortu_tanggal_lahir_{{ $i }}" name="ortu_tanggal_lahir_{{ $i }}" type="date" value="{{ $o['tanggal_lahir'] }}">
                    </div>
                    <div>
                        <label class="label" for="ortu_tempat_lahir_{{ $i }}">Tempat Lahir</label>
                        <input class="input" id="ortu_tempat_lahir_{{ $i }}" name="ortu_tempat_lahir_{{ $i }}" type="text" value="{{ $o['tempat_lahir'] }}">
                    </div>
                    <div>
                        <label class="label" for="ortu_pendidikan_{{ $i }}">Pendidikan</label>
                        <select class="input" id="ortu_pendidikan_{{ $i }}" name="ortu_pendidikan_{{ $i }}" data-searchable>
                            <option value="" @selected($nilaiPilihan('ortu_pendidikan_'.$i, $o['pendidikan'] ?? '') === '')>Pilih pendidikan</option>
                            @foreach ($opsi['pendidikan'] as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($nilaiPilihan('ortu_pendidikan_'.$i, $o['pendidikan'] ?? '') === (string) $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="ortu_kerja_{{ $i }}">Pekerjaan</label>
                        <select class="input" id="ortu_kerja_{{ $i }}" name="ortu_kerja_{{ $i }}" data-searchable @if ($i < 2) data-wajib @endif>
                            <option value="" @selected($nilaiPilihan('ortu_kerja_'.$i, $o['pekerjaan'] ?? '') === '')>Pilih pekerjaan</option>
                            @foreach ($opsi['pekerjaan'] as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($nilaiPilihan('ortu_kerja_'.$i, $o['pekerjaan'] ?? '') === (string) $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="ortu_penghasilan_{{ $i }}">Penghasilan bulanan</label>
                        <select class="input" id="ortu_penghasilan_{{ $i }}" name="ortu_penghasilan_{{ $i }}" data-searchable>
                            <option value="" @selected($nilaiPilihan('ortu_penghasilan_'.$i, $o['penghasilan'] ?? '') === '')>Pilih penghasilan bulanan</option>
                            @foreach ($opsi['penghasilan'] as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($nilaiPilihan('ortu_penghasilan_'.$i, $o['penghasilan'] ?? '') === (string) $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="ortu_kebutuhan_{{ $i }}">Berkebutuhan Khusus</label>
                        <select class="input" id="ortu_kebutuhan_{{ $i }}" name="ortu_kebutuhan_{{ $i }}" data-searchable>
                            <option value="" @selected($nilaiPilihan('ortu_kebutuhan_'.$i, $o['kebutuhan'] ?? '') === '')>Pilih kebutuhan khusus</option>
                            @foreach ($opsi['kebutuhan'] as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($nilaiPilihan('ortu_kebutuhan_'.$i, $o['kebutuhan'] ?? '') === (string) $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="ortu_hp_{{ $i }}">No. HP</label>
                        <input class="input" id="ortu_hp_{{ $i }}" name="ortu_hp_{{ $i }}" type="text" value="{{ old('ortu_hp_'.$i, $o['hp']) }}" data-hp inputmode="tel">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="label" for="ortu_alamat_{{ $i }}">Alamat</label>
                        <input class="input" id="ortu_alamat_{{ $i }}" name="ortu_alamat_{{ $i }}" type="text" value="{{ $o['alamat'] }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div data-step="2" class="space-y-4 hidden">
        <div class="card" x-data="{ jarak: '{{ $nilaiPilihan('jarak', $form['jarak'] ?? '') }}' }">
            <h2 class="sec-title">F. Data Periodik</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="label" for="tinggi_badan">Tinggi Badan (cm)</label>
                    <input class="input" id="tinggi_badan" name="tinggi_badan" type="number" value="{{ $form['tinggi_badan'] }}">
                </div>
                <div>
                    <label class="label" for="berat_badan">Berat Badan (kg)</label>
                    <input class="input" id="berat_badan" name="berat_badan" type="number" value="{{ $form['berat_badan'] }}">
                </div>
                <div>
                    <label class="label" for="gol_darah">Gol. Darah</label>
                    <select class="input" id="gol_darah" name="gol_darah" data-searchable>
                        <option value="" @selected($nilaiPilihan('gol_darah', $form['gol_darah'] ?? '') === '')>Pilih golongan darah</option>
                        @foreach ($opsi['gol_darah'] as $gol)
                            <option value="{{ $gol }}" @selected($nilaiPilihan('gol_darah', $form['gol_darah'] ?? '') === (string) $gol)>{{ $gol }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="jarak">Jarak tempat tinggal ke sekolah</label>
                    <select class="input" id="jarak" name="jarak" x-model="jarak" data-searchable>
                        <option value="" @selected($nilaiPilihan('jarak', $form['jarak'] ?? '') === '')>Pilih jarak tempat tinggal</option>
                        <option value="1" @selected($nilaiPilihan('jarak', $form['jarak'] ?? '') === '1')>Kurang dari 1 km</option>
                        <option value="2" @selected($nilaiPilihan('jarak', $form['jarak'] ?? '') === '2')>Lebih dari 1 km (sebutkan ... km)</option>
                    </select>
                </div>
                <div x-show="jarak === '2'">
                    <label class="label" for="jarak_km">Sebutkan jarak (km)</label>
                    <input class="input" id="jarak_km" name="jarak_km" type="number" step="0.1" value="{{ $form['jarak_km'] }}">
                </div>
                <div>
                    <span class="label">Waktu Tempuh</span>
                    <div class="flex items-center gap-2">
                        <input class="input w-24" id="waktu_jam" name="waktu_jam" type="number" min="0" value="{{ $form['waktu_jam'] }}">
                        <span class="text-sm text-mute">jam</span>
                        <input class="input w-24" id="waktu_menit" name="waktu_menit" type="number" min="0" max="59" value="{{ $form['waktu_menit'] }}">
                        <span class="text-sm text-mute">menit</span>
                    </div>
                </div>
                <div>
                    <label class="label" for="jumlah_saudara">Jumlah Saudara kandung</label>
                    <input class="input" id="jumlah_saudara" name="jumlah_saudara" type="number" value="{{ $form['jumlah_saudara'] }}">
                </div>
            </div>
        </div>

        <div class="card" x-data='{ prestasi: @json($form["prestasi"]), berubah: false }'>
            <h2 class="sec-title">G. Prestasi</h2>
            <input type="hidden" name="prestasi_dikelola" value="" x-bind:value="berubah ? '1' : ''">
            <template x-for="(row, i) in prestasi" :key="i">
                <div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-3 mb-3 items-end">
                    <div>
                        <label class="label">Jenis</label>
                        <select class="input" x-model="row.jenis" name="prestasi_jenis[]" data-searchable>
                            <option value="">Pilih jenis prestasi</option>
                            @foreach ($opsi['prestasi_jenis'] as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Tingkat</label>
                        <select class="input" x-model="row.tingkat" name="prestasi_tingkat[]" data-searchable>
                            <option value="">Pilih tingkat prestasi</option>
                            @foreach ($opsi['prestasi_tingkat'] as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Nama Prestasi</label>
                        <input class="input" type="text" x-model="row.nama" name="prestasi_nama[]">
                    </div>
                    <div>
                        <label class="label">Tahun</label>
                        <input class="input" type="number" x-model="row.tahun" name="prestasi_tahun[]">
                    </div>
                    <div>
                        <label class="label">Penyelenggara</label>
                        <input class="input" type="text" x-model="row.penyelenggara" name="prestasi_penyelenggara[]">
                    </div>
                    <button type="button" class="btn btn-ghost" x-on:click="prestasi.splice(i, 1); berubah = true">Hapus</button>
                </div>
            </template>
            <button type="button" class="btn btn-ghost" x-on:click="prestasi.push({ jenis: '', tingkat: '', nama: '', tahun: '', penyelenggara: '' }); berubah = true">+ Tambah Prestasi</button>
        </div>

        <div class="card" x-data='{ beasiswa: @json($form["beasiswa"]), berubah: false }'>
            <h2 class="sec-title">H. Beasiswa</h2>
            <input type="hidden" name="beasiswa_dikelola" value="" x-bind:value="berubah ? '1' : ''">
            <template x-for="(row, i) in beasiswa" :key="i">
                <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-3 items-end">
                    <div>
                        <label class="label">Jenis</label>
                        <select class="input" x-model="row.jenis" name="beasiswa_jenis[]" data-searchable>
                            <option value="">Pilih jenis beasiswa</option>
                            @foreach ($opsi['beasiswa_jenis'] as $nilai => $label)
                                <option value="{{ $nilai }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Keterangan</label>
                        <input class="input" type="text" x-model="row.keterangan" name="beasiswa_keterangan[]">
                    </div>
                    <div>
                        <label class="label">Tahun Mulai</label>
                        <input class="input" type="number" x-model="row.tahun_mulai" name="beasiswa_tahun_mulai[]">
                    </div>
                    <div>
                        <label class="label">Tahun Selesai</label>
                        <input class="input" type="number" x-model="row.tahun_selesai" name="beasiswa_tahun_selesai[]">
                    </div>
                    <button type="button" class="btn btn-ghost" x-on:click="beasiswa.splice(i, 1); berubah = true">Hapus</button>
                </div>
            </template>
            <button type="button" class="btn btn-ghost" x-on:click="beasiswa.push({ jenis: '', keterangan: '', tahun_mulai: '', tahun_selesai: '' }); berubah = true">+ Tambah Beasiswa</button>
        </div>

        <div class="card">
            <h2 class="sec-title">Unggah berkas</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach (['Kartu Keluarga', 'Akta Kelahiran', 'Ijazah / SKL', 'Pas Foto 3x4'] as $d)
                    @php
                        $daftarBerkas = $berkas ?? [];
                        $berkasAda = array_key_exists($d, $daftarBerkas);
                        $wajibUpload = !$berkasAda || ($modePerbaikan && ($daftarBerkas[$d]->status_verifikasi ?? null) === 'perlu_perbaikan');
                    @endphp
                    <div>
                        <label class="flex items-center justify-between gap-3 border-2 border-dashed border-line rounded-xl p-3.5 text-sm cursor-pointer hover:border-flame">
                            <span class="min-w-0">
                                <span class="block font-medium">{{ $d }} @if ($wajibUpload)<span class="text-bad">*</span>@endif</span>
                                <span class="nama-file block text-xs text-mute truncate">{{ $berkasAda ? $daftarBerkas[$d]->nama_file_asli : 'Belum ada file' }}</span>
                            </span>
                            <span class="btn btn-ghost !py-1.5 shrink-0">Pilih file</span>
                            <input type="file" class="hidden" name="berkas_{{ $loop->index }}" accept=".pdf,.jpg,.jpeg,.png" @if ($wajibUpload) data-wajib @endif data-nama="{{ $d }}">
                        </label>
                        @if (in_array($loop->index, [1, 2]))
                            <div class="mt-2">
                                <label class="label" for="nomor_dokumen_{{ $loop->index }}">{{ $loop->index === 1 ? 'Nomor akta kelahiran' : 'Nomor ijazah / SKL' }}</label>
                                <input class="input" id="nomor_dokumen_{{ $loop->index }}" name="nomor_dokumen_{{ $loop->index }}" type="text" value="{{ old('nomor_dokumen_'.$loop->index, $form['nomor_dokumen_'.$loop->index] ?? '') }}">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="text-[11px] text-mute mt-2">Bisa langsung foto dari HP. Format PDF, JPG, atau PNG, maksimal {{ $maksBerkasMb ?? 2 }} MB per file.</p>
        </div>
    </div>

    <div data-step="3" class="space-y-4 hidden">
        <div class="card">
            <h2 class="sec-title">{{ $modePerbaikan ? 'Periksa perbaikan sebelum mengirim ulang' : 'Periksa sebelum mengirim' }}</h2>
            <p class="text-sm text-mute">{{ $modePerbaikan ? 'Pastikan data atau berkas yang diminta sudah diperbaiki. Pengajuan yang sama akan dikirim kembali untuk diperiksa Tata Usaha.' : 'Pastikan data dan berkas sudah benar. Setelah dikirim, data diperiksa Tata Usaha dan tidak bisa diubah sampai ada hasil pemeriksaan.' }}</p>
            @unless ($modePerbaikan)
                <p class="text-sm text-mute mt-2">Setelah terkirim kamu mendapat <b>kode pendaftaran</b>. Simpan kode itu untuk mengecek status di halaman <a href="{{ route('ppdb.status') }}" class="font-semibold text-flame">Cek Status</a>.</p>
            @endunless
            <label class="flex items-start gap-3 mt-4 text-sm cursor-pointer">
                <input type="checkbox" name="pernyataan" class="size-4 mt-0.5 accent-flame" data-wajib @checked(old('pernyataan', $form['pernyataan']))> Saya menyatakan data yang saya isi sesuai dengan dokumen asli.
            </label>
        </div>

        <div class="card border-l-4 !border-l-flame">
            <h2 class="sec-title">J. Pernyataan / Persetujuan Orang Tua/Wali</h2>
            <p class="text-sm text-mute">Saya, Orang tua / wali dari murid atas nama <b>{{ $form['nama_lengkap'] }}</b> menyatakan "TIDAK KEBERATAN" anak saya untuk mengikuti mata pelajaran Pend. Agama Kristen, sehubungan dengan ciri khas Sekolah Kristen.</p>
            <div class="mt-3">
                <label class="label" for="nama_ortu_wali">Nama Orang Tua / Wali</label>
                <input class="input" id="nama_ortu_wali" name="nama_ortu_wali" type="text" value="{{ $form['nama_ortu_wali'] }}">
            </div>
            <label class="flex items-start gap-3 mt-4 text-sm cursor-pointer">
                <input type="checkbox" name="persetujuan_ortu" class="size-4 mt-0.5 accent-flame" data-wajib data-nama="Persetujuan orang tua/wali" required @checked(old('persetujuan_ortu', $form['persetujuan_ortu']))>
                <span>Saya menyatakan <b>TIDAK KEBERATAN</b> dan menyetujui pernyataan di atas. <span class="text-bad">*</span></span>
            </label>
        </div>
    </div>

    {{-- tombol navigasi: menempel di bawah layar pada HP --}}
    <div class="sticky bottom-0 z-20 -mx-4 px-4 py-3 bg-paper/90 backdrop-blur border-t border-line sm:static sm:mx-0 sm:px-0 sm:py-0 sm:bg-transparent sm:backdrop-blur-none sm:border-0">
        <div class="flex items-center gap-2">
            <button type="button" id="prev" class="btn btn-ghost">Kembali</button>
            @unless ($modePerbaikan)
                <button type="submit" formaction="{{ route('ppdb.draf') }}" formnovalidate class="btn btn-ghost ml-auto">
                    <span class="sm:hidden">Draf</span><span class="hidden sm:inline">Simpan draf</span>
                </button>
            @endunless
            <button type="button" id="next" class="btn btn-flame">Lanjut</button>
        </div>
    </div>
</form>

<script>
(function () {
    var cur = 0, steps = document.querySelectorAll('[data-step]'), inds = document.querySelectorAll('[data-ind]');
    var prev = document.getElementById('prev'), next = document.getElementById('next');
    var modePerbaikan = @json($modePerbaikan);
    var judul = ['Data diri', 'Orang tua', 'Data periodik & berkas', modePerbaikan ? 'Periksa & kirim ulang' : 'Periksa & kirim'];
    var ringkas = document.getElementById('ringkas');
    var ATURAN = '[data-wajib],[data-digit],[data-hp],input[type=file]';

    // ---------- Validasi isian ----------
    // Tanda * merah di label kolom wajib
    document.querySelectorAll('[data-wajib]').forEach(function (el) {
        if (el.type === 'file' || el.type === 'checkbox') return;
        var host = el.closest('[data-password-field]') || el.parentNode;
        var lb = host.querySelector('label');
        if (!lb) return;
        el.dataset.nama = el.dataset.nama || lb.textContent.trim();
        lb.insertAdjacentHTML('beforeend', ' <span class="text-bad">*</span>');
    });

    function nm(el) {
        if (el.dataset.nama) return el.dataset.nama;
        var host = el.closest('[data-password-field]') || el.parentNode;
        var lb = host.querySelector('label');
        return lb ? lb.textContent.trim() : el.name;
    }
    function boxErr(el) {
        var passwordField = el.closest('[data-password-field]');
        var host = (el.type === 'file' || el.type === 'checkbox')
            ? el.closest('label').parentNode
            : (passwordField || el.parentNode);
        var p = passwordField
            ? passwordField.querySelector('[data-password-error]')
            : host.querySelector(':scope > .err');
        if (!p) {
            p = document.createElement('p');
            p.className = 'err text-xs text-bad mt-1 hidden';
            p.setAttribute('role', 'alert');
            host.appendChild(p);
        }
        return p;
    }
    function cek(el) {
        if (el.disabled) return '';
        var nama = nm(el), wajib = el.hasAttribute('data-wajib');
        if (el.type === 'checkbox') return (wajib && !el.checked) ? 'Centang pernyataan ini untuk melanjutkan.' : '';
        if (el.type === 'file') {
            var f = el.files[0];
            if (!f) return wajib ? nama + ' wajib diunggah.' : '';
            if (f.size > {{ $maksBerkasMb ?? 2 }} * 1024 * 1024) return 'Ukuran ' + nama + ' maksimal {{ $maksBerkasMb ?? 2 }} MB.';
            if (!/\.(pdf|jpe?g|png)$/i.test(f.name)) return nama + ' harus berformat PDF, JPG, atau PNG.';
            return '';
        }
        var v = el.value.trim();
        if (!v) return wajib ? nama + (el.tagName === 'SELECT' ? ' wajib dipilih.' : ' wajib diisi.') : '';
        if (el.dataset.digit && !new RegExp('^\\d{' + el.dataset.digit + '}$').test(v)) return nama + ' harus ' + el.dataset.digit + ' digit angka.';
        if (el.hasAttribute('data-hp') && !/^(\+62|62|0)8\d{8,12}$/.test(v.replace(/[\s-]/g, ''))) return nama + ' tidak valid. Contoh: 081234567890.';
        if (el.name === 'password' && v.length < 8) return nama + ' minimal 8 karakter.';
        if (el.name === 'password_confirmation' && v !== document.getElementById('password').value) return nama + ' harus sama.';
        return '';
    }
    function tampil(el) {
        var msg = cek(el), p = boxErr(el);
        p.textContent = msg;
        p.classList.toggle('hidden', !msg);
        if (el.type !== 'checkbox') (el.type === 'file' ? el.closest('label') : (el._dd || el)).classList.toggle('!border-bad', !!msg);
        return msg;
    }
    function periksa(n) {
        var salah = [];
        steps[n].querySelectorAll(ATURAN).forEach(function (el) { if (tampil(el)) salah.push(el); });
        return salah;
    }
    function validasiStep(n) {
        var salah = periksa(n);
        if (salah.length) {
            ringkas.textContent = 'Masih ada ' + salah.length + ' isian yang belum lengkap atau belum sesuai. Periksa kolom yang bertanda merah.';
            ringkas.classList.remove('hidden');
            salah[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (salah[0].type !== 'file' && salah[0].focus) salah[0].focus({ preventScroll: true });
        } else {
            ringkas.classList.add('hidden');
        }
        return salah.length === 0;
    }

    // Pesan muncul saat keluar dari kolom, dan hilang otomatis begitu isian diperbaiki
    document.querySelectorAll(ATURAN).forEach(function (el) {
        var langsung = el.type === 'file' || el.type === 'checkbox' || el.tagName === 'SELECT';
        el.addEventListener(langsung ? 'change' : 'blur', function () { tampil(el); });
        el.addEventListener('input', function () { if (!boxErr(el).classList.contains('hidden')) tampil(el); });
        if (el.type === 'file') {
            el.addEventListener('change', function () {
                var f = el.files[0];
                el.closest('label').querySelector('.nama-file').textContent = f ? f.name : 'Belum ada file';
            });
        }
    });

    // ---------- Langkah ----------
    function show(n) {
        cur = n;
        steps.forEach(function (s, i) { s.classList.toggle('hidden', i !== n); });
        inds.forEach(function (d, i) { d.className = 'flex-1 h-1.5 rounded-full ' + (i <= n ? 'bg-flame' : 'bg-line'); });
        document.getElementById('stepLabel').textContent = 'Langkah ' + (n + 1) + ' dari ' + steps.length + ' · ' + judul[n];
        document.getElementById('langkah_terakhir').value = n;
        prev.style.visibility = n === 0 ? 'hidden' : 'visible';
        next.textContent = n === steps.length - 1 ? (modePerbaikan ? 'Kirim ulang berkas' : 'Kirim pendaftaran') : 'Lanjut';
        window.scrollTo(0, 0);
    }
    prev.onclick = function () { if (cur > 0) { ringkas.classList.add('hidden'); show(cur - 1); } };
    next.onclick = function () {
        if (!validasiStep(cur)) return;
        if (cur < steps.length - 1) { show(cur + 1); return; }

        // Kirim: cek ulang semua langkah, lompat ke langkah pertama yang masih bermasalah
        for (var i = 0; i < steps.length; i++) {
            if (periksa(i).length) { show(i); validasiStep(i); return; }
        }
        document.getElementById('ppdb').requestSubmit();
    };
    show(parseInt(document.getElementById('langkah_terakhir').value || '0', 10));
})();
</script>
@endsection
