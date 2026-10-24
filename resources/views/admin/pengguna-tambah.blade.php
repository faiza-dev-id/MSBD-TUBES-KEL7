@extends('layouts.app')
@section('title', 'Tambah Pengguna')
@section('heading', 'Tambah Pengguna')
@section('sub', 'Akun siswa dibuat otomatis saat pendaftaran disahkan. Form ini untuk guru, staf, dan admin.')
@section('content')
<form class="card max-w-xl space-y-4" onsubmit="return false">
    <x-field label="Nama lengkap" name="nama" />
    <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="Username" name="username" />
        <div><label class="label">Peran</label>
            <select class="input">@foreach ($roles as $r)<option>{{ $r['label'] }}</option>@endforeach</select></div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="NIP (khusus guru)" name="nip" />
        <x-field label="No. HP" name="no_hp" />
    </div>
    <x-field label="Kata sandi awal" name="password" type="password" hint="Pengguna diminta menggantinya saat pertama masuk" />
    <div class="flex gap-2"><button class="btn btn-flame">Simpan</button><a href="{{ route('admin.pengguna') }}" class="btn btn-ghost">Batal</a></div>
</form>
@endsection