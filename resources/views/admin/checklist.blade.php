@extends('layouts.app')
@section('title', 'Checklist & Alasan')
@section('heading', 'Checklist Verifikasi dan Alasan Pengembalian')
@section('sub', 'Daftar yang muncul di halaman verifikasi berkas milik Tata Usaha.')
@section('content')
@if (session('success'))
    <div class="card mb-4 text-sm text-ok font-semibold">{{ session('success') }}</div>
@endif
@if (session('galat') || $errors->any())
    <div class="card mb-4 text-sm text-bad">{{ session('galat') ?? $errors->first() }}</div>
@endif
<div class="grid lg:grid-cols-2 gap-4 items-start">
    <div class="space-y-4">
        @foreach ($dokumen as $d)
            <div class="card">
                <h2 class="font-serif text-lg font-bold mb-2">{{ $d->nama_dokumen }}</h2>
                @foreach ($item[$d->id_jenis_dokumen] ?? [] as $it)
                    <div class="flex items-center gap-2 py-2 text-sm border-b border-line last:border-0 flex-wrap">
                        <form method="POST" action="{{ route('admin.checklist.item.ubah', $it->id_item) }}" class="flex items-center gap-2 flex-1 min-w-[16rem]">
                            @csrf @method('PUT')
                            <input class="input !py-1.5 flex-1" name="nama_item" value="{{ $it->nama_item }}" maxlength="180">
                            <label class="flex items-center gap-1 text-xs whitespace-nowrap">
                                <input type="hidden" name="wajib" value="0">
                                <input type="checkbox" name="wajib" value="1" class="size-4 accent-flame" @checked($it->wajib)> Wajib
                            </label>
                            <button class="btn btn-ghost !py-1.5">Simpan</button>
                        </form>
                        <form method="POST" action="{{ route('admin.checklist.item.hapus', $it->id_item) }}" onsubmit="return confirm('Hapus item ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost !py-1.5 !text-bad">Hapus</button>
                        </form>
                    </div>
                @endforeach
                <form method="POST" action="{{ route('admin.checklist.item.simpan') }}" class="flex items-center gap-2 mt-3 flex-wrap">
                    @csrf
                    <input type="hidden" name="id_jenis_dokumen" value="{{ $d->id_jenis_dokumen }}">
                    <input class="input !py-1.5 flex-1 min-w-[12rem]" name="nama_item" placeholder="Item baru" maxlength="180">
                    <label class="flex items-center gap-1 text-xs whitespace-nowrap">
                        <input type="hidden" name="wajib" value="0">
                        <input type="checkbox" name="wajib" value="1" class="size-4 accent-flame" checked> Wajib
                    </label>
                    <button class="btn btn-flame !py-1.5">+ Item</button>
                </form>
            </div>
        @endforeach
    </div>
    <div class="card">
        <h2 class="font-serif text-lg font-bold mb-2">Alasan pengembalian</h2>
        @foreach ($alasan as $a)
            <div class="flex items-center gap-2 py-2 text-sm border-b border-line last:border-0 flex-wrap">
                <form method="POST" action="{{ route('admin.checklist.alasan.ubah', $a->id_alasan) }}" class="flex items-center gap-2 flex-1 min-w-[14rem]">
                    @csrf @method('PUT')
                    <input class="input !py-1.5 flex-1" name="nama_alasan" value="{{ $a->nama_alasan }}" maxlength="180">
                    <button class="btn btn-ghost !py-1.5">Simpan</button>
                </form>
                <form method="POST" action="{{ route('admin.checklist.alasan.hapus', $a->id_alasan) }}" onsubmit="return confirm('Hapus alasan ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost !py-1.5 !text-bad">Hapus</button>
                </form>
            </div>
        @endforeach
        <form method="POST" action="{{ route('admin.checklist.alasan.simpan') }}" class="flex items-center gap-2 mt-3">
            @csrf
            <input class="input !py-1.5 flex-1" name="nama_alasan" placeholder="Alasan baru" maxlength="180">
            <button class="btn btn-flame !py-1.5">+ Alasan</button>
        </form>
    </div>
</div>
@endsection
