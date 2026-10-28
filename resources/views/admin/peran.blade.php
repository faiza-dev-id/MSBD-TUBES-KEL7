@extends('layouts.app')
@section('title', 'Peran & Akses')
@section('heading', 'Peran dan Hak Akses')
@section('sub', 'Centang hak akses yang dimiliki tiap peran. Perubahan tercatat di jejak audit.')
@section('content')
@if (session('success'))
    <div class="card mb-4 text-sm text-ok font-semibold">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="card mb-4 text-sm text-bad">{{ $errors->first() }}</div>
@endif
@php
    $label = ['siswa' => 'Siswa', 'guru' => 'Guru', 'tu' => 'Tata Usaha', 'kepsek' => 'Kepala Sekolah'];
@endphp
<form method="POST" action="{{ route('admin.peran.simpan') }}" class="card">
    @csrf
    @method('PUT')
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            <thead>
                <tr class="text-left text-xs text-mute border-b-2 border-line">
                    <th class="p-2">Hak akses</th>
                    @foreach ($roles as $r)
                        <th class="p-2 text-center">{{ $label[$r->nama_role] ?? $r->nama_role }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach ($hak as $h)
                <tr class="border-b border-line">
                    <td class="p-2.5">
                        {{ $h->nama_hak_akses }}
                        <div class="text-[11px] text-mute">{{ $h->kode_hak_akses }}</div>
                    </td>
                    @foreach ($roles as $r)
                        @php
                            $centang = in_array((int) $h->id_hak_akses, $punya[$r->id_role] ?? [], true);
                            $kunci = in_array($h->kode_hak_akses, $terkunci[$r->nama_role] ?? [], true);
                        @endphp
                        <td class="p-2.5 text-center">
                            @if ($kunci)
                                {{-- Tidak bisa dicabut: tetap dikirim lewat input tersembunyi --}}
                                <input type="checkbox" class="size-4 accent-flame" checked disabled title="Hak ini wajib dimiliki peran ini">
                                <input type="hidden" name="akses[{{ $r->id_role }}][]" value="{{ $h->id_hak_akses }}">
                            @else
                                <input type="checkbox" class="size-4 accent-flame"
                                       name="akses[{{ $r->id_role }}][]" value="{{ $h->id_hak_akses }}" @checked($centang)>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-[11px] text-mute mt-3">Kepala Sekolah selalu memiliki hak mengelola pengguna agar tidak terkunci dari sistem.</p>
    <button class="btn btn-flame mt-4">Simpan perubahan</button>
</form>
@endsection
