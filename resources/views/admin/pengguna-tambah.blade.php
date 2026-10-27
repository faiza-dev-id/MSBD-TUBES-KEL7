```blade
@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('heading', 'Tambah Pengguna')
@section('sub', 'Akun siswa dibuat otomatis saat pendaftaran disahkan. Form ini untuk guru, Tata Usaha, dan Kepala Sekolah.')

@section('content')
<form class="card max-w-xl space-y-4"
      method="POST"
      action="{{ route('admin.pengguna.simpan') }}">
    @csrf

    <x-field label="Nama lengkap" name="nama" />
    <x-field label="Username" name="username" />

    <div>
        <label class="label" for="id_role">Peran</label>
        <select class="input" id="id_role" name="id_role" required>
            @foreach ($roles as $role)
                @if ($role->nama_role !== 'siswa')
                    <option value="{{ $role->id_role }}"
                        @selected(old('id_role') == $role->id_role)>
                        {{ $role->nama_role === 'tu'
                            ? 'Tata Usaha'
                            : ($role->nama_role === 'kepsek'
                                ? 'Kepala Sekolah'
                                : ucfirst($role->nama_role)) }}
                    </option>
                @endif
            @endforeach
        </select>

        @error('id_role')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="NIP (khusus guru)" name="nip" />
        <x-field label="No. HP" name="no_hp" />
    </div>

    <x-field label="Kata sandi awal" name="password"
        type="password" hint="Minimal 8 karakter." />

    @if ($errors->any())
        <div class="text-sm text-red-600">
            <p>Periksa kembali data berikut:</p>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex gap-2">
        <button type="submit" class="btn btn-flame">Simpan</button>
        <a href="{{ route('admin.pengguna') }}" class="btn btn-ghost">
            Batal
        </a>
    </div>
</form>
@endsection
```