@extends('layouts.app')
@section('title', 'Jejak Audit')
@section('heading', 'Jejak Audit')
@section('sub', 'Setiap perubahan data tercatat otomatis: siapa, kapan, dan nilai sebelum dan sesudahnya.')
@section('content')
<div class="space-y-3">
    @foreach ($log as $l)
        <div class="card">
            <div class="flex items-center justify-between gap-3 flex-wrap text-sm">
                <div><b>{{ $l['user'] }}</b> <span class="text-mute">· {{ $l['waktu'] }}</span></div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-navy/10 text-navy">{{ $l['aksi'] }} · {{ $l['tabel'] }} {{ $l['rec'] }}</span>
            </div>
            <div class="grid sm:grid-cols-2 gap-3 mt-3 text-sm">
                <div class="bg-bad/10 rounded-md p-3"><div class="text-xs text-mute mb-1">Sebelum</div>{{ $l['lama'] }}</div>
                <div class="bg-ok/10 rounded-md p-3"><div class="text-xs text-mute mb-1">Sesudah</div>{{ $l['baru'] }}</div>
            </div>
        </div>
    @endforeach
</div>
@endsection