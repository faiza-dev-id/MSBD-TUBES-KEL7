@extends('layouts.app')
@section('title', 'Kenaikan Kelas')
@section('heading', 'Kenaikan Kelas Massal')
@section('sub', 'Naikkan seluruh kelas sekaligus. Kelas tujuan dipetakan otomatis berdasarkan tingkat, jurusan, dan rombel pada semester ganjil tahun ajaran berikutnya.')
@section('content')
<form method="GET" action="{{ route('tu.kenaikan') }}" class="card mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="label" for="tahun_ajaran">Tahun ajaran asal</label>
        <select class="input" id="tahun_ajaran" name="tahun_ajaran">
            @foreach ($tahunAjaran as $t)
                <option value="{{ $t->id_tahun_ajaran }}" @selected((int) $idTahunAsal === (int) $t->id_tahun_ajaran)>{{ $t->nama_tahun_ajaran }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="filter_tingkat">Tingkat kelas</label>
        <select class="input" id="filter_tingkat" name="tingkat">
            <option value="">Semua tingkat</option>
            @foreach (['X', 'XI', 'XII'] as $tingkat)
                <option value="{{ $tingkat }}" @selected($tingkatTerpilih === $tingkat)>{{ $tingkat }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="filter_program">Program / jurusan</label>
        <select class="input" id="filter_program" name="program">
            <option value="">Semua program</option>
            @foreach (['MIA', 'IIS'] as $program)
                <option value="{{ $program }}" @selected($programTerpilih === $program)>{{ $program }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-flame" type="submit">Terapkan</button>
    <a class="btn btn-ghost" href="{{ route('tu.kenaikan') }}">Reset</a>
</form>

@if (!$rencana)
    <div class="card text-sm text-mute">Belum ada tahun ajaran. Tambahkan tahun ajaran dan semester di
        <a class="text-flame-dark font-semibold" href="{{ route('akademik.konfigurasi', 'tahun-ajaran') }}">Konfigurasi Akademik</a>.</div>
@else
    @php
        $asal = $rencana['tahun_asal'];
        $tujuan = $rencana['tahun_tujuan'];
        $semesterTujuan = $rencana['semester_tujuan'];
        $kelasBelumDipetakan = collect($rencana['baris'])->contains(fn ($baris) => ! $baris['kelas_tujuan_tinggal']
            || (! $baris['tingkat_akhir'] && ! $baris['kelas_tujuan']));
        $siap = $tujuan && $semesterTujuan && ! $kelasBelumDipetakan;
    @endphp

    @if (!empty($rencana['peringatan']))
        <div class="card mb-4 bg-white !border-sun/50 text-sm text-sun-dark" role="status">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($rencana['peringatan'] as $p)
                    <li>{{ $p }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (!$tujuan)
        <div class="card mb-4 text-sm text-mute">
            Tambahkan tahun ajaran berikutnya dan semester ganjil melalui
            <a class="text-flame-dark font-semibold" href="{{ route('akademik.konfigurasi', 'tahun-ajaran') }}">Konfigurasi Akademik</a>.
        </div>
    @elseif (!$semesterTujuan)
        <div class="card mb-4 text-sm text-mute">
            Buat semester ganjil untuk {{ $tujuan->nama_tahun_ajaran }} di
            <a class="text-flame-dark font-semibold" href="{{ route('akademik.konfigurasi', 'semester') }}">Konfigurasi Akademik &gt; Semester</a>.
        </div>
    @elseif ($kelasBelumDipetakan)
        <div class="card mb-4 text-sm text-mute">
            Sebelum memproses, siapkan kelas tujuan untuk siswa naik dan kelas yang sama tingkatnya untuk siswa yang tidak naik.
            <a class="text-flame-dark font-semibold" href="{{ route('tu.kelas', ['semester' => $semesterTujuan->id_semester]) }}">Buka Kelas &amp; Wali Kelas {{ $tujuan->nama_tahun_ajaran }}</a>.
        </div>
    @endif

    <div class="card mb-4">
        <div class="grid sm:grid-cols-3 gap-3 text-sm">
            <div><div class="text-xs uppercase tracking-wide text-mute">Tahun ajaran asal</div><div class="font-semibold text-ink">{{ $asal->nama_tahun_ajaran }}</div></div>
            <div><div class="text-xs uppercase tracking-wide text-mute">Tahun ajaran tujuan</div><div class="font-semibold text-ink">{{ $tujuan->nama_tahun_ajaran ?? '—' }}</div></div>
            <div><div class="text-xs uppercase tracking-wide text-mute">Semester tujuan</div><div class="font-semibold text-ink">{{ $semesterTujuan->nama_semester ?? '—' }}</div></div>
        </div>
        <p class="text-xs text-mute mt-3">Total {{ $rencana['jumlah_siswa'] }} siswa pada {{ count($rencana['baris']) }} kelas. Semua siswa tercentang secara bawaan; hilangkan centang untuk siswa yang tidak naik kelas.</p>
    </div>

    <form method="POST" action="{{ route('tu.kenaikan.simpan') }}" onsubmit="return confirm('Proses kenaikan kelas untuk semua siswa yang dicentang? Tindakan ini tercatat di jejak audit.')">
        @csrf
        <input type="hidden" name="id_tahun_ajaran" value="{{ $asal->id_tahun_ajaran }}">
        <input type="hidden" name="tingkat" value="{{ $tingkatTerpilih }}">
        <input type="hidden" name="program" value="{{ $programTerpilih }}">

        <div class="space-y-4">
        @forelse ($rencana['baris'] as $baris)
            @php
                $ka = $baris['kelas_asal'];
                $kt = $baris['kelas_tujuan'];
                $jml = count($baris['siswa']);
            @endphp
            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-2 pb-3 mb-2 border-b border-line">
                    <div>
                        <div class="font-semibold text-ink">{{ $ka['nama'] }}</div>
                        <div class="text-xs text-mute">
                            @if ($baris['tingkat_akhir'])
                                Tingkat akhir — siswa yang dicentang akan ditandai <b>lulus</b>.
                            @elseif ($kt)
                                Tujuan otomatis: <b class="text-ink">{{ $kt['nama'] }}</b>
                            @else
                                <span class="text-bad">Kelas tujuan belum tersedia pada tahun ajaran tujuan.</span>
                            @endif
                            @if (!$baris['kelas_tujuan_tinggal'])
                                <span class="text-bad">Kelas tingkat yang sama untuk siswa yang tidak naik belum tersedia.</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-mute">{{ $jml }} siswa</span>
                        @if ($jml > 0)
                            <label class="flex items-center gap-2 text-xs font-semibold text-mute cursor-pointer">
                                <input type="checkbox" class="size-4 accent-flame" data-centang-kelas checked> Centang kelas ini
                            </label>
                        @endif
                    </div>
                </div>

                @forelse ($baris['siswa'] as $s)
                    <label class="flex items-center gap-3 py-2 text-sm border-b border-line last:border-0 cursor-pointer">
                        <input type="checkbox" name="id_siswa[]" value="{{ $s->id_siswa }}" class="size-4 accent-flame" checked>
                        <b class="text-ink">{{ $s->nama_lengkap }}</b>
                        <span class="text-mute">{{ $s->nisn ?? '-' }}</span>
                    </label>
                @empty
                    <p class="py-3 text-sm text-mute">Tidak ada siswa aktif di kelas ini.</p>
                @endforelse
            </div>
        @empty
            <div class="card text-sm text-mute">Belum ada kelas pada tahun ajaran ini. Tambahkan kelas terlebih dahulu di menu <b>Kelas dan Wali Kelas</b>.</div>
        @endforelse
        </div>

        <div class="card mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-mute">Tercentang: <b id="jumlahNaik">0</b> siswa akan diproses.</div>
            <button class="btn btn-flame" @disabled(!$siap || $rencana['jumlah_siswa'] === 0)>Proses kenaikan kelas</button>
        </div>
    </form>
@endif

<script>
(function () {
    function semua() { return Array.prototype.slice.call(document.querySelectorAll('input[name="id_siswa[]"]')); }
    var label = document.getElementById('jumlahNaik');

    function sync() {
        var list = semua();
        if (label) label.textContent = list.filter(function (b) { return b.checked; }).length;
    }

    document.querySelectorAll('[data-centang-kelas]').forEach(function (master) {
        var card = master.closest('.card');
        var grup = card ? Array.prototype.slice.call(card.querySelectorAll('input[name="id_siswa[]"]')) : [];

        master.addEventListener('change', function () {
            grup.forEach(function (b) { b.checked = master.checked; });
            sync();
        });

        grup.forEach(function (b) {
            b.addEventListener('change', function () {
                master.checked = grup.length > 0 && grup.every(function (x) { return x.checked; });
                sync();
            });
        });
    });

    semua().forEach(function (b) { b.addEventListener('change', sync); });
    sync();
})();
</script>
@endsection
