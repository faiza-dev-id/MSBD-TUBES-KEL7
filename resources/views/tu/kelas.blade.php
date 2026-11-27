@extends('layouts.app')
@section('title', 'Daftar Kelas')
@section('heading', 'Kelas dan Wali Kelas')
@section('sub', 'Kelola kelas setiap tahun ajaran, tetapkan wali kelas, dan buka daftar siswa per kelas. Wali kelas ditetapkan per semester.')
@section('content')
@if (!$sem)
    <div class="card text-sm text-mute">Belum ada semester. Tambahkan tahun ajaran dan semester di
        <a class="text-flame-dark font-semibold" href="{{ route('akademik.konfigurasi', 'semester') }}">Konfigurasi Akademik</a>.</div>
@else
@php
    // Peringatan: kelas tanpa wali, dan pengguna yang menjadi wali di beberapa kelas.
    $tanpaWali = $kelas->filter(fn ($k) => $k->id_guru === null)->pluck('nama_kelas');
    $waliGanda = $kelas->filter(fn ($k) => $k->id_guru !== null)->groupBy('nama_guru')->filter(fn ($g) => $g->count() > 1);
@endphp
<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3" data-auto-filter>
    <div><label class="label" for="semester">Semester</label>
        <select class="input" id="semester" name="semester">
            @foreach ($semesters as $s)
                <option value="{{ $s->id_semester }}" @selected($s->id_semester === $sem->id_semester)>{{ $s->nama_semester }} {{ $s->nama_tahun_ajaran }}{{ $s->is_aktif ? ' (aktif)' : '' }}</option>
            @endforeach
        </select></div>
    <div><label class="label" for="filterTingkat">Tingkat</label>
        <select class="input" id="filterTingkat" name="tingkat">
            <option value="">Semua tingkat</option>
            @foreach (['X', 'XI', 'XII'] as $namaTingkat)
                <option value="{{ $namaTingkat }}" @selected($filterTingkat === $namaTingkat)>{{ $namaTingkat }}</option>
            @endforeach
        </select></div>
    <div><label class="label" for="filterJurusan">Jurusan</label>
        <select class="input" id="filterJurusan" name="jurusan">
            <option value="">Semua jurusan</option>
            @foreach (['MIA', 'IIS'] as $kodeJurusan)
                <option value="{{ $kodeJurusan }}" @selected($filterJurusan === $kodeJurusan)>{{ $kodeJurusan }}</option>
            @endforeach
        </select></div>
    <a href="{{ route('tu.kelas', ['semester' => $sem->id_semester]) }}" class="btn btn-ghost">Reset filter</a>
</form>

{{-- @if ($tanpaWali->isNotEmpty())
    <div class="mb-4 rounded-2xl border border-sun/40 bg-sun/10 px-4 py-3 text-sm text-sun-dark" role="status">
        Kelas yang belum punya wali: <b>{{ $tanpaWali->join(', ') }}</b>.
    </div>
@endif --}}
@foreach ($waliGanda as $nama => $grup)
    <div class="card mb-4 bg-white !border-sun/50 text-sm text-sun-dark" role="status">
        Periksa lagi: <b>{{ $nama }}</b> tercatat menjadi wali di {{ $grup->pluck('nama_kelas')->join(' dan ') }}.
    </div>
@endforeach

<div class="grid lg:grid-cols-[1.4fr_1fr] gap-4 items-start">
    <div class="card">
        <div class="flex flex-wrap items-start justify-between gap-3 pb-3 mb-1 border-b border-line">
            <div>
                <h2 class="sec-title !border-0 !pb-0 !mb-1">Daftar kelas</h2>
                <p class="text-xs text-mute">Tahun ajaran {{ $sem->nama_tahun_ajaran }} · {{ $kelas->count() }} kelas</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('tu.kelas.wali') }}" onsubmit="return confirm('Buat 120 kelas standar (X–XII, MIA & IIS, nomor 1–20) untuk tahun ajaran {{ e($sem->nama_tahun_ajaran) }}? Kelas yang namanya sudah ada akan dilewati.')">
                    @csrf
                    <input type="hidden" name="aksi" value="buat">
                    <input type="hidden" name="id_tahun_ajaran" value="{{ $sem->id_tahun_ajaran }}">
                    <input type="hidden" name="id_semester" value="{{ $sem->id_semester }}">
                    <button type="submit" class="btn btn-ghost !py-1.5" title="Buat 120 kelas standar secara otomatis">
                        <x-icon name="file" size="w-4 h-4" /> Buat 120 kelas
                    </button>
                </form>
                <form method="POST" action="{{ route('tu.kelas.wali') }}" onsubmit="return confirm('Duplikat semua kelas dari tahun ajaran sebelumnya ke {{ e($sem->nama_tahun_ajaran) }}? Kelas yang namanya sudah ada akan dilewati.')">
                    @csrf
                    <input type="hidden" name="aksi" value="duplikat">
                    <input type="hidden" name="id_tahun_ajaran" value="{{ $sem->id_tahun_ajaran }}">
                    <input type="hidden" name="id_semester" value="{{ $sem->id_semester }}">
                    <button type="submit" class="btn btn-ghost !py-1.5" title="Salin daftar kelas dari tahun ajaran sebelumnya">
                        <x-icon name="file" size="w-4 h-4" /> Duplikat tahun lalu
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto -mx-5 px-5">
            <table class="w-full text-sm min-w-[38rem]">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-mute border-b border-line">
                        <th class="py-2.5 pr-3 font-semibold">Nama kelas</th>
                        <th class="py-2.5 pr-3 font-semibold">Tahun ajaran</th>
                        <th class="py-2.5 pr-3 font-semibold">Wali kelas</th>
                        <th class="py-2.5 pr-3 font-semibold">Siswa</th>
                        <th class="py-2.5 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($kelas as $k)
                    <tr class="border-b border-line last:border-0 align-middle"
                        data-id="{{ $k->id_kelas }}"
                        data-nama="{{ $k->nama_kelas }}"
                        data-tahun="{{ $k->id_tahun_ajaran }}"
                        data-tingkat="{{ $k->id_tingkat }}"
                        data-jurusan="{{ $k->id_jurusan }}"
                        data-kapasitas="{{ $k->kapasitas }}"
                        data-guru="{{ $k->id_guru }}">
                        <td class="py-3 pr-3">
                            <div class="font-semibold text-ink">{{ $k->nama_kelas }}</div>
                            @if ($k->nama_tingkat || $k->nama_jurusan)
                                <div class="text-xs text-mute">
                                    {{ $k->nama_tingkat ? 'Tingkat '.$k->nama_tingkat : '' }}{{ $k->nama_tingkat && $k->nama_jurusan ? ' · ' : '' }}{{ $k->nama_jurusan }}
                                </div>
                            @endif
                        </td>
                        <td class="py-3 pr-3 text-mute">{{ $k->nama_tahun_ajaran ?? '-' }}</td>
                        <td class="py-3 pr-3">
                            @if ($k->id_guru)
                                <span class="text-ink">{{ $k->nama_guru }}</span>
                                <span class="text-xs text-mute">({{ $k->role_wali === 'wali_kelas' ? 'Wali Kelas' : 'Guru' }})</span>
                            @else
                                <span class="text-bad font-medium">Belum ditetapkan</span>
                            @endif
                        </td>
                        <td class="py-3 pr-3 text-mute">{{ $k->jumlah }}{{ $k->kapasitas ? ' / '.$k->kapasitas : '' }}</td>
                        <td class="py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('tu.kelas.detail', ['id' => $k->id_kelas, 'semester' => $sem->id_semester]) }}" class="p-2 rounded-lg text-flame hover:bg-tint" title="Daftar siswa">
                                    <x-icon name="users" size="w-4 h-4" />
                                </a>
                                @if ($k->id_penugasan_wali_kelas)
                                    <form method="POST" action="{{ route('tu.kelas.wali.cabut', $k->id_penugasan_wali_kelas) }}" onsubmit="return confirm('Cabut wali kelas {{ e($k->nama_kelas) }}?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg text-mute hover:bg-paper hover:text-bad" title="Cabut wali kelas">
                                            <x-icon name="close" size="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                                <button type="button" class="p-2 rounded-lg text-navy hover:bg-tint" title="Edit kelas" onclick="bukaKelasUbah(this)">
                                    <x-icon name="edit" size="w-4 h-4" />
                                </button>
                                <button type="button" class="p-2 rounded-lg text-bad hover:bg-bad/10" title="Hapus kelas" onclick="bukaHapusKelas(this)">
                                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-mute">Tidak ada kelas yang cocok dengan filter ini.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <form class="card space-y-4" method="POST" action="{{ route('tu.kelas.wali') }}">
        @csrf
        <h2 class="sec-title">Tetapkan wali kelas</h2>
        <input type="hidden" name="id_semester" value="{{ $sem->id_semester }}">
        <p class="text-xs text-mute">Berlaku untuk semester {{ $sem->nama_semester }} {{ $sem->nama_tahun_ajaran }}. Jika kelas sudah punya wali, wali lama diganti.</p>
        <div><label class="label" for="id_kelas">Kelas</label>
            <select class="input" id="id_kelas" name="id_kelas" required>
                @foreach ($kelas as $k)<option value="{{ $k->id_kelas }}" @selected((string) old('id_kelas') === (string) $k->id_kelas)>{{ $k->nama_kelas }}</option>@endforeach
            </select></div>
        <div><label class="label" for="id_guru">Wali Kelas</label>
            <select class="input" id="id_guru" name="id_guru" required>
                @foreach ($guru as $g)<option value="{{ $g->id_guru }}" @selected((string) old('id_guru') === (string) $g->id_guru)>{{ $g->nama_guru }} ({{ $g->nama_role === 'wali_kelas' ? 'Wali Kelas' : 'Guru' }})</option>@endforeach
            </select></div>
        <button class="btn btn-flame w-full" @disabled($kelas->isEmpty() || $guru->isEmpty())>Tetapkan</button>
    </form>
</div>

{{-- Modal edit kelas --}}
<dialog id="dlgKelas" class="m-auto w-[min(94vw,34rem)] rounded-2xl border border-line bg-card p-0 text-ink backdrop:bg-black/40">
    <form id="formKelas" method="POST" action="{{ route('tu.kelas.wali') }}" class="p-5 space-y-4">
        @csrf
        <input type="hidden" name="aksi" value="kelas">
        <input type="hidden" name="id_kelas" id="idKelasModal" value="{{ old('id_kelas') }}">
        <input type="hidden" name="id_semester" value="{{ old('id_semester', $sem->id_semester) }}">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-navy" id="judulKelasModal">Ubah kelas</h3>
                <p class="text-xs text-mute mt-1" id="subKelasModal">Perbarui data kelas yang sudah tersedia.</p>
            </div>
            <button type="button" class="p-2 rounded-lg text-mute hover:bg-paper" onclick="tutupKelas()" aria-label="Tutup">
                <x-icon name="close" />
            </button>
        </div>
        <div>
            <label for="namaKelasModal" class="label">Nama kelas</label>
            <input class="input" id="namaKelasModal" name="nama_kelas" value="{{ old('nama_kelas') }}" maxlength="80" required placeholder="Contoh: X MIA 1">
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label for="tingkatKelasModal" class="label">Tingkat</label>
                <select class="input" id="tingkatKelasModal" name="id_tingkat" required>
                    <option value="">— Pilih tingkat —</option>
                    @foreach ($tingkat as $t)
                        <option value="{{ $t->id_tingkat }}" @selected((string) old('id_tingkat') === (string) $t->id_tingkat)>{{ $t->nama_tingkat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="jurusanKelasModal" class="label">Jurusan</label>
                <select class="input" id="jurusanKelasModal" name="id_jurusan" required>
                    <option value="">— Pilih jurusan —</option>
                    @foreach ($jurusan as $j)
                        <option value="{{ $j->id_jurusan }}" @selected((string) old('id_jurusan') === (string) $j->id_jurusan)>{{ $j->kode_jurusan }} — {{ $j->nama_jurusan }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label for="tahunKelasModal" class="label">Tahun ajaran</label>
                <select class="input" id="tahunKelasModal" name="id_tahun_ajaran" required>
                    @foreach ($tahunAjaran as $t)
                        <option value="{{ $t->id_tahun_ajaran }}" @selected((string) old('id_tahun_ajaran', $sem->id_tahun_ajaran) === (string) $t->id_tahun_ajaran)>{{ $t->nama_tahun_ajaran }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="guruKelasModal" class="label">Wali kelas</label>
                <select class="input" id="guruKelasModal" name="id_guru">
                    <option value="">— Belum ditetapkan —</option>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id_guru }}" @selected((string) old('id_guru') === (string) $g->id_guru)>{{ $g->nama_guru }} ({{ $g->nama_role === 'wali_kelas' ? 'Wali Kelas' : 'Guru' }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label for="kapasitasKelasModal" class="label">Kapasitas</label>
            <input class="input" id="kapasitasKelasModal" name="kapasitas" type="number" min="1" max="100" value="{{ old('kapasitas') }}" placeholder="Contoh: 32">
        </div>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
            <button type="button" class="btn btn-ghost" onclick="tutupKelas()">Batal</button>
            <button type="submit" class="btn btn-flame">Simpan kelas</button>
        </div>
    </form>
</dialog>

{{-- Dialog konfirmasi hapus --}}
<dialog id="dlgHapusKelas" class="m-auto w-[min(94vw,28rem)] rounded-2xl border border-line bg-card p-0 text-ink backdrop:bg-black/40">
    <form method="POST" id="formHapusKelas" action="" class="p-5 space-y-4">
        @csrf @method('DELETE')
        <input type="hidden" name="jenis" value="kelas">
        <h3 class="text-lg font-bold text-navy">Hapus kelas?</h3>
        <p class="text-sm text-mute">Kelas <b class="text-ink" id="hapusNamaKelas"></b> akan dihapus. Kelas yang sudah memiliki siswa atau jadwal tidak dapat dihapus.</p>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('dlgHapusKelas').close()">Batal</button>
            <button type="submit" class="btn !bg-bad hover:!bg-bad/90">Ya, hapus</button>
        </div>
    </form>
</dialog>

<script>
(function () {
    var dlg = document.getElementById('dlgKelas');
    var judul = document.getElementById('judulKelasModal');
    var sub = document.getElementById('subKelasModal');
    var id = document.getElementById('idKelasModal');
    var nama = document.getElementById('namaKelasModal');
    var tahun = document.getElementById('tahunKelasModal');
    var tingkat = document.getElementById('tingkatKelasModal');
    var jurusan = document.getElementById('jurusanKelasModal');
    var kapasitas = document.getElementById('kapasitasKelasModal');
    var guru = document.getElementById('guruKelasModal');
    var hapus = document.getElementById('dlgHapusKelas');
    var formHapus = document.getElementById('formHapusKelas');
    var hapusNama = document.getElementById('hapusNamaKelas');
    var baseHapus = @json(url('tu/kelas/wali'));

    window.bukaKelasUbah = function (tombol) {
        var tr = tombol.closest('tr');
        id.value = tr.dataset.id || '';
        nama.value = tr.dataset.nama || '';
        tahun.value = tr.dataset.tahun || '{{ $sem->id_tahun_ajaran }}';
        tingkat.value = tr.dataset.tingkat || '';
        jurusan.value = tr.dataset.jurusan || '';
        kapasitas.value = tr.dataset.kapasitas || '';
        guru.value = tr.dataset.guru || '';
        judul.textContent = 'Ubah kelas';
        sub.textContent = 'Perubahan disimpan setelah tombol simpan diklik.';
        dlg.showModal();
        nama.focus();
    };

    window.tutupKelas = function () {
        dlg.close();
    };

    window.bukaHapusKelas = function (tombol) {
        var tr = tombol.closest('tr');
        hapusNama.textContent = tr.dataset.nama || 'ini';
        formHapus.action = baseHapus + '/' + (tr.dataset.id || '');
        hapus.showModal();
    };

    dlg.addEventListener('click', function (e) {
        if (e.target === dlg) dlg.close();
    });
    hapus.addEventListener('click', function (e) {
        if (e.target === hapus) hapus.close();
    });
    @if (old('id_kelas'))
        dlg.showModal();
    @endif
})();
</script>
@endif
@endsection
