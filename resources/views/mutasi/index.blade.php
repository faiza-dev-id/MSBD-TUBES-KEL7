@extends('layouts.app')
@section('title', $judul)
@section('heading', $judul)
@section('sub', $sub)
@section('content')
@if ($bisaAjukan)
    <form class="card max-w-3xl mb-4 space-y-4" method="POST" action="{{ route($simpanRoute) }}" enctype="multipart/form-data" data-mutasi-form>
        @csrf
        <h2 class="sec-title">Ajukan mutasi siswa</h2>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="jenis">Jenis mutasi</label>
                <select class="input" id="jenis" name="jenis" data-mutasi-type required>
                    @foreach ([
                        'pindah_keluar' => 'Pindah Keluar',
                        'pindah_masuk' => 'Pindah Masuk',
                        'mengundurkan_diri' => 'Mengundurkan Diri',
                        'dikeluarkan' => 'Dikeluarkan',
                        'putus_sekolah' => 'Putus Sekolah',
                        'meninggal_dunia' => 'Meninggal Dunia',
                    ] as $kode => $nama)
                        <option value="{{ $kode }}" @selected(old('jenis', 'pindah_keluar') === $kode)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div data-mutasi-group="siswa">
                <label class="label" for="id_siswa">Pilih siswa</label>
                <select class="input" id="id_siswa" name="id_siswa" data-mutasi-required>
                    <option value="">Pilih siswa aktif</option>
                    @foreach ($siswa as $s)
                        <option value="{{ $s['id'] }}" @selected((string) old('id_siswa') === (string) $s['id'])>{{ $s['nama'] }} — {{ $s['nisn'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="hidden grid gap-4 sm:grid-cols-2" data-mutasi-group="siswa-baru">
            <div>
                <label class="label" for="nisn_baru">NISN siswa</label>
                <input class="input" id="nisn_baru" name="nisn_baru" inputmode="numeric" maxlength="10" value="{{ old('nisn_baru') }}" data-mutasi-required>
            </div>
            <div>
                <label class="label" for="nama_siswa_baru">Nama lengkap</label>
                <input class="input" id="nama_siswa_baru" name="nama_siswa_baru" maxlength="150" value="{{ old('nama_siswa_baru') }}" data-mutasi-required>
            </div>
            <div>
                <label class="label" for="jenis_kelamin_baru">Jenis kelamin</label>
                <select class="input" id="jenis_kelamin_baru" name="jenis_kelamin_baru" data-mutasi-required>
                    <option value="">Pilih</option>
                    <option value="L" @selected(old('jenis_kelamin_baru') === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('jenis_kelamin_baru') === 'P')>Perempuan</option>
                </select>
            </div>
            <div>
                <label class="label" for="id_kelas_tujuan">Kelas tujuan</label>
                <select class="input" id="id_kelas_tujuan" name="id_kelas_tujuan" data-mutasi-required>
                    <option value="">Pilih kelas aktif</option>
                    @foreach ($kelasTujuan as $kelas)
                        <option value="{{ $kelas['id'] }}" @selected((string) old('id_kelas_tujuan') === (string) $kelas['id'])>{{ $kelas['nama'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="tempat_lahir_baru">Tempat lahir <span class="font-normal">(opsional)</span></label>
                <input class="input" id="tempat_lahir_baru" name="tempat_lahir_baru" maxlength="100" value="{{ old('tempat_lahir_baru') }}">
            </div>
            <div>
                <label class="label" for="tanggal_lahir_baru">Tanggal lahir <span class="font-normal">(opsional)</span></label>
                <input class="input" id="tanggal_lahir_baru" name="tanggal_lahir_baru" type="date" max="{{ now()->toDateString() }}" value="{{ old('tanggal_lahir_baru') }}">
            </div>
            <div>
                <label class="label" for="email_baru">Email <span class="font-normal">(opsional)</span></label>
                <input class="input" id="email_baru" name="email_baru" type="email" value="{{ old('email_baru') }}">
            </div>
            <div>
                <label class="label" for="no_telepon_baru">No. telepon <span class="font-normal">(opsional)</span></label>
                <input class="input" id="no_telepon_baru" name="no_telepon_baru" maxlength="30" value="{{ old('no_telepon_baru') }}">
            </div>
        </div>

        <div data-mutasi-group="sekolah-tujuan">
            <label class="label" for="sekolah_tujuan">Sekolah Tujuan <span class="font-normal">(opsional)</span></label>
            <input class="input" id="sekolah_tujuan" name="sekolah_tujuan" type="text" maxlength="255" value="{{ old('sekolah_tujuan') }}" placeholder="Ketik nama sekolah tujuan">
        </div>
        <div class="hidden" data-mutasi-group="sekolah-asal">
            <label class="label" for="id_sekolah_asal">Sekolah Asal <span class="font-normal">(opsional)</span></label>
            <select class="input" id="id_sekolah_asal" name="id_sekolah_asal">
                <option value="">Pilih sekolah asal</option>
                @foreach ($sekolah as $sk)
                    <option value="{{ $sk['id'] }}" @selected((string) old('id_sekolah_asal') === (string) $sk['id'])>{{ $sk['nama'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="tanggal_efektif">Tanggal efektif</label>
                <input class="input" id="tanggal_efektif" name="tanggal_efektif" type="date" value="{{ old('tanggal_efektif', now()->toDateString()) }}" required>
            </div>
            <div>
                <x-berkas label="Lampiran" name="lampiran" hint="PDF/JPG/PNG, opsional" />
            </div>
        </div>

        <div>
            <label class="label" for="alasan">Alasan mutasi</label>
            <textarea class="input" id="alasan" name="alasan" rows="3" maxlength="2000" required>{{ old('alasan') }}</textarea>
        </div>

        <p class="text-xs text-mute">Pindah Masuk akan langsung didaftarkan oleh Tata Usaha. Pengajuan Pindah Keluar dan status keluar dikirim ke Kepala Sekolah untuk diputuskan.</p>
        <button class="btn btn-flame" type="submit">Kirim pengajuan mutasi</button>
    </form>
@endif

<div class="space-y-3">
    @forelse ($mutasi as $m)
        <div class="card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-xs text-mute">Mutasi #{{ $m['id'] }} · diajukan {{ $m['tgl'] }}</div>
                    <h2 class="font-serif text-lg font-bold">{{ $m['nama'] }}</h2>
                    <div class="text-xs text-mute">NISN {{ $m['nisn'] }}</div>
                </div>
                <x-badge :status="$m['status']" />
            </div>

            <dl class="mt-3 grid gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                <div><dt class="text-xs text-mute">Jenis</dt><dd class="font-medium">{{ $m['jenis'] }}</dd></div>
                @if ($m['sekolah_label'])
                    <div><dt class="text-xs text-mute">{{ $m['sekolah_label'] }}</dt><dd class="font-medium">{{ $m['sekolah'] }}</dd></div>
                @endif
                <div><dt class="text-xs text-mute">Tanggal efektif</dt><dd class="font-medium">{{ $m['tanggal_efektif'] }}</dd></div>
                <div><dt class="text-xs text-mute">Diajukan oleh</dt><dd class="font-medium">{{ $m['pengaju'] }}</dd></div>
                <div><dt class="text-xs text-mute">Diputuskan oleh</dt><dd class="font-medium">{{ $m['penyetuju'] }}</dd></div>
                <div><dt class="text-xs text-mute">Tanggal keputusan</dt><dd class="font-medium">{{ $m['keputusan'] }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-mute">Alasan</dt><dd class="font-medium">{{ $m['alasan'] }}</dd></div>
            </dl>

            @if ($m['catatan_keputusan'])
                <div class="mt-3 rounded-xl border border-bad/30 bg-bad/5 p-3 text-sm">
                    <b class="text-bad">Catatan keputusan:</b> {{ $m['catatan_keputusan'] }}
                </div>
            @endif

            @if ($m['ada_lampiran'])
                @php
                    $namaLampiran = $m['nama_lampiran'] ?: 'Lampiran mutasi';
                    $tipeLampiran = strtolower(pathinfo($namaLampiran, PATHINFO_EXTENSION)) === 'pdf' ? 'pdf' : 'gambar';
                    $urlLampiran = route($lampiranRoute, $m['id']);
                    $urlUnduhLampiran = route($lampiranRoute, ['id' => $m['id'], 'download' => 1]);
                @endphp
                <button type="button"
                        class="mt-3 inline-flex text-sm font-semibold text-flame-dark hover:underline cursor-pointer"
                        @click='$dispatch("preview-berkas", { url: @json($urlLampiran), unduhUrl: @json($urlUnduhLampiran), nama: @json($namaLampiran), jenis: @json("Lampiran mutasi"), tipe: @json($tipeLampiran) })'>
                    Lihat lampiran{{ $m['nama_lampiran'] ? ': '.$m['nama_lampiran'] : '' }}
                </button>
            @endif

            @if ($m['bisa_revisi'])
                <details class="mt-4 border-t border-line pt-3">
                    <summary class="cursor-pointer text-sm font-semibold text-flame-dark">Edit / revisi pengajuan</summary>
                    <form method="POST" action="{{ route($revisiRoute, $m['id']) }}" enctype="multipart/form-data" class="mt-3 space-y-3" data-mutasi-form>
                        @csrf
                        @method('PUT')
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label" for="jenis_{{ $m['id'] }}">Jenis mutasi</label>
                                <select class="input" id="jenis_{{ $m['id'] }}" name="jenis" data-mutasi-type required>
                                    @foreach ([
                                        'pindah_keluar' => 'Pindah Keluar',
                                        'mengundurkan_diri' => 'Mengundurkan Diri',
                                        'dikeluarkan' => 'Dikeluarkan',
                                        'putus_sekolah' => 'Putus Sekolah',
                                        'meninggal_dunia' => 'Meninggal Dunia',
                                    ] as $kode => $nama)
                                        <option value="{{ $kode }}" @selected($m['jenis_kode'] === $kode)>{{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div data-mutasi-group="siswa">
                                <label class="label" for="siswa_{{ $m['id'] }}">Pilih siswa aktif</label>
                                <select class="input" id="siswa_{{ $m['id'] }}" name="id_siswa" data-mutasi-required required>
                                    <option value="">Pilih siswa</option>
                                    @foreach ($siswa as $s)
                                        <option value="{{ $s['id'] }}" @selected((string) $m['id_siswa'] === (string) $s['id'])>{{ $s['nama'] }} — {{ $s['nisn'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div data-mutasi-group="sekolah-tujuan">
                                <label class="label" for="sekolah_{{ $m['id'] }}">Sekolah Tujuan <span class="font-normal">(opsional)</span></label>
                                <input class="input" id="sekolah_{{ $m['id'] }}" name="sekolah_tujuan" type="text" maxlength="255" value="{{ old('sekolah_tujuan', $m['sekolah_tujuan']) }}" placeholder="Ketik nama sekolah tujuan">
                            </div>
                            <div>
                                <label class="label" for="tanggal_efektif_{{ $m['id'] }}">Tanggal efektif</label>
                                <input class="input" id="tanggal_efektif_{{ $m['id'] }}" name="tanggal_efektif" type="date" value="{{ $m['tanggal_efektif_raw'] }}" required>
                            </div>
                            <div>
                                <x-berkas label="Ganti lampiran" name="lampiran" hint="PDF/JPG/PNG, opsional" />
                            </div>
                        </div>
                        <div>
                            <label class="label" for="alasan_{{ $m['id'] }}">Alasan</label>
                            <textarea class="input" id="alasan_{{ $m['id'] }}" name="alasan" rows="3" maxlength="2000" required>{{ $m['alasan'] === '-' ? '' : $m['alasan'] }}</textarea>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button class="btn btn-flame" type="submit">Simpan revisi</button>
                        </div>
                    </form>
                </details>

                <form method="POST" action="{{ route($batalkanRoute, $m['id']) }}" class="mt-3" data-konfirmasi="Batalkan pengajuan mutasi ini?">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-ghost !text-bad" type="submit">Batalkan pengajuan</button>
                </form>
            @endif

            @if ($bisaPutuskan && $m['bisa_putuskan'])
                <form method="POST" action="{{ route($putuskanRoute, $m['id']) }}" class="mt-4 space-y-3 border-t border-line pt-3" data-mutasi-decision>
                    @csrf
                    <div>
                        <label class="label" for="catatan_{{ $m['id'] }}">Catatan keputusan <span class="font-normal">(wajib jika ditolak)</span></label>
                        <textarea class="input" id="catatan_{{ $m['id'] }}" name="catatan_keputusan" rows="2" maxlength="2000"></textarea>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button class="btn" type="submit" name="keputusan" value="setujui">Setujui mutasi</button>
                        <button class="btn btn-ghost !text-bad" type="submit" name="keputusan" value="tolak">Tolak mutasi</button>
                    </div>
                </form>
            @endif
        </div>
    @empty
        <div class="card text-sm text-mute">Belum ada pengajuan mutasi siswa.</div>
    @endforelse
</div>

<script>
(function () {
    document.querySelectorAll('[data-mutasi-form]').forEach(function (form) {
        var jenis = form.querySelector('[data-mutasi-type]');
        if (!jenis) return;

        form.querySelectorAll('input[type="file"]').forEach(function (field) {
            var label = field.closest('label');
            var namaFile = label.querySelector('.nama-file');
            var tombolFile = label.querySelector('[data-file-button]');

            field.addEventListener('change', function () {
                var file = field.files && field.files[0];
                if (tombolFile) tombolFile.textContent = file ? file.name : 'Pilih file';
                if (tombolFile) tombolFile.title = file ? file.name : '';
                if (namaFile) namaFile.classList.toggle('hidden', Boolean(file));
            });
        });

        function sinkronkan() {
            var masuk = jenis.value === 'pindah_masuk';
            var pindahKeluar = jenis.value === 'pindah_keluar';
            form.querySelectorAll('[data-mutasi-group]').forEach(function (grup) {
                var nama = grup.getAttribute('data-mutasi-group');
                var tampil = nama === 'siswa' ? !masuk
                    : nama === 'siswa-baru' || nama === 'sekolah-asal' ? masuk
                    : nama === 'sekolah-tujuan' ? pindahKeluar
                    : false;
                grup.classList.toggle('hidden', !tampil);
                grup.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !tampil;
                    if (field.hasAttribute('data-mutasi-required')) field.required = tampil;
                });
            });
        }

        jenis.addEventListener('change', sinkronkan);
        sinkronkan();
    });

    document.querySelectorAll('[data-mutasi-decision] button[name="keputusan"]').forEach(function (button) {
        button.addEventListener('click', function () {
            var catatan = button.form.querySelector('[name="catatan_keputusan"]');
            catatan.required = button.value === 'tolak';
        });
    });
})();
</script>
@endsection
