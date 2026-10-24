@extends('layouts.app')
@section('title', 'Checklist & Alasan')
@section('heading', 'Checklist Verifikasi dan Alasan Pengembalian')
@section('sub', 'Daftar yang muncul di halaman verifikasi berkas milik Tata Usaha.')
@section('content')
<div class="grid lg:grid-cols-2 gap-4 items-start">
    <div class="space-y-4">
        @foreach ($checklist as $dokumen => $items)
            <div class="card">
                <div class="flex items-center justify-between gap-3 mb-2"><h2 class="font-serif text-lg font-bold">{{ $dokumen }}</h2><button class="btn btn-ghost !py-1.5">+ Item</button></div>
                @foreach ($items as $item)
                    <div class="flex items-center justify-between gap-3 py-2 text-sm border-b border-line last:border-0">
                        <span>{{ $item }}</span><span class="whitespace-nowrap"><button class="text-flame-dark font-semibold">Ubah</button> · <button class="text-bad font-semibold">Hapus</button></span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
    <div class="card">
        <div class="flex items-center justify-between gap-3 mb-2"><h2 class="font-serif text-lg font-bold">Alasan pengembalian</h2><button class="btn btn-ghost !py-1.5">+ Alasan</button></div>
        @foreach ($alasan as $a)
            <div class="flex items-center justify-between gap-3 py-2 text-sm border-b border-line last:border-0">
                <span>{{ $a }}</span><span class="whitespace-nowrap"><button class="text-flame-dark font-semibold">Ubah</button> · <button class="text-bad font-semibold">Hapus</button></span>
            </div>
        @endforeach
    </div>
</div>
@endsection