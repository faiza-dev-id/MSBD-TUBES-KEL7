@extends('layouts.app')
@section('title', 'Reset Sandi Akun')
@section('heading', 'Reset Sandi Akun')
@section('sub', 'Hanya untuk akun siswa dan guru. Sandi sementara tampil satu kali, serahkan langsung ke pemilik akun.')
@section('content')
@if (isset($hasil))
    <div class="mb-4 rounded-xl border border-ok/40 bg-ok/10 px-4 py-3 text-sm text-ok">
        Sandi sementara untuk <b>{{ $hasil['nama'] }}</b> ({{ $hasil['username'] }}):
        <span class="font-mono text-base font-bold select-all">{{ $hasil['sandi'] }}</span><br>
        <span class="text-xs">Tampil sekali saja dan tidak disimpan. Minta pemiliknya segera menggantinya lewat menu Ganti Sandi.</span>
    </div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad">{{ $errors->first() }}</div>
@endif

<div class="card">
    <form method="GET" action="{{ route('tu.reset') }}" class="mb-3 flex flex-wrap gap-2">
        <input name="cari" value="{{ $cari }}" class="input !w-64" placeholder="Cari nama / username">
        <button class="btn btn-flame">Cari</button>
        @if ($cari !== '') <a href="{{ route('tu.reset') }}" class="btn btn-ghost">Reset</a> @endif
    </form>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead>
                <tr class="text-left text-xs text-mute border-b-2 border-line">
                    <th class="p-2">Nama</th><th class="p-2">Username</th><th class="p-2">Peran</th><th class="p-2">Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($pengguna as $u)
                <tr class="border-b border-line">
                    <td class="p-2.5 font-medium">{{ $u->nama }}</td>
                    <td class="p-2.5">{{ $u->username }}</td>
                    <td class="p-2.5">{{ $u->nama_role === 'guru' ? 'Guru' : 'Siswa' }}</td>
                    <td class="p-2.5"><span class="text-xs font-semibold {{ $u->is_aktif ? 'text-ok' : 'text-mute' }}">{{ $u->is_aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="p-2.5 text-right">
                        @if ($u->is_aktif)
                            <form method="POST" action="{{ route('tu.reset.proses', $u->id_pengguna) }}"
                                  onsubmit="return confirm('Reset sandi untuk {{ e($u->nama) }}?')">
                                @csrf
                                <button class="btn btn-ghost !py-1.5">Reset sandi</button>
                            </form>
                        @else
                            <span class="text-xs text-mute">Akun nonaktif</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-mute">Tidak ada akun yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pengguna->links() }}</div>
</div>
@endsection
