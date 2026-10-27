@extends('layouts.app')
@section('title', 'Data Siswa')
@section('heading', 'Data Induk Siswa')
@section('sub', 'Seluruh data siswa yang tercatat. Klik nama untuk melihat data dan dokumen.')
@section('content')
<div class="card">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
        <form method="GET" action="{{ route('tu.siswa.index') }}" class="flex items-center gap-2"><input name="cari" value="{{ request('cari') }}" class="input !w-64" placeholder="Cari nama / NISN"><button class="btn btn-ghost" type="submit">Cari</button></form>
        <select class="input !w-44"><option>Semua status</option><option>Aktif</option><option>Mutasi keluar</option></select>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Nama</th><th class="p-2">NISN</th><th class="p-2">Kelas</th><th class="p-2">Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($daftar as $r)
                <tr class="border-b border-line">
                    <td class="p-2.5 font-medium">{{ $r['nama'] }}</td><td class="p-2.5">{{ $r['nisn'] }}</td><td class="p-2.5">{{ $r['kelas'] }}</td>
                    <td class="p-2.5">{{ $r['status'] }}</td>
                    <td class="p-2.5 text-right"><a class="btn btn-ghost !py-1.5" href="{{ route('tu.siswa.show', $r['id']) }}">Buka</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $daftar->links() }}</div>
    </div>
</div>
@endsection