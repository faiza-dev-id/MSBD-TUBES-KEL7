@extends('layouts.app')
@section('title', 'Jadwal Mengajar')
@section('heading', 'Jadwal Mengajar')
@section('sub', 'Jadwal mengajarmu minggu ini.')
@section('content')
<div class="card">
    @foreach ($jadwal as $j)
        <div class="flex items-center justify-between gap-3 py-3 border-b border-line last:border-0 text-sm">
            <div><b>{{ $j['hari'] }}</b> <span class="text-mute">· {{ $j['jam'] }}</span></div>
            <div class="text-right"><b>{{ $j['mapel'] }}</b><div class="text-xs text-mute">{{ $j['kelas'] }}</div></div>
        </div>
    @endforeach
</div>
@endsection