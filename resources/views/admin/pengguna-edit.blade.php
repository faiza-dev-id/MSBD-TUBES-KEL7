```blade
@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('heading', 'Edit Pengguna')
@section('sub', 'Ubah informasi akun pengguna.')

@section('content')
<form class="card max-w-xl space-y-4"
      method="POST"
      action="{{ route('admin.pengguna.update', $user['id_pengguna']) }}">
    @csrf
    @method('PUT')

    <x-field label="Nama lengkap" name="nama" :value="old('nama', $user['nama'])" />

    <x-field label="Username" name="username" :value="old('username', $user['username'])" />

    <div>
        <label class="label" for="id_role">Peran</label>
        <select class="input" id="id_role" name="id_role" required>
            @foreach ($roles as $role)
                @if ($role->nama_role !== 'siswa')
                    <option value="{{ $role->id_role }}"
                        @selected(old('id_role', $user['id_role']) == $role->id_role)>
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
        <x-field label="NIP (khusus guru)" name="nip" :value="old('nip', $user['nip'])" />
        <x-field label="No. HP" name="no_hp" :value="old('no_hp', $user['no_hp'])" />
    </div>

    @if ($errors->any())
        <div class="text-sm text-red-600">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="flex gap-2">
        <button type="submit" class="btn btn-flame">Simpan Perubahan</button>
        <a href="{{ route('admin.pengguna') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
```