@extends('layouts.app')
@section('title', 'Log Login')
@section('heading', 'Log Login')
@section('sub', 'Riwayat percobaan masuk, berhasil maupun gagal. Gagal beruntun akan mengunci akun sementara.')
@section('content')
<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Waktu</th><th class="p-2">Username</th><th class="p-2">Hasil</th><th class="p-2">IP</th><th class="p-2">Perangkat</th></tr></thead>
            <tbody>
            @foreach ($log as $l)
                <tr class="border-b border-line">
                    <td class="p-2.5">{{ $l['waktu'] }}</td><td class="p-2.5 font-medium">{{ $l['user'] }}</td>
                    <td class="p-2.5"><span class="text-xs font-semibold {{ $l['ok'] ? 'text-ok' : 'text-bad' }}">{{ $l['ok'] ? 'Berhasil' : 'Gagal' }}</span></td>
                    <td class="p-2.5 text-mute">{{ $l['ip'] }}</td><td class="p-2.5 text-mute">{{ $l['perangkat'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection