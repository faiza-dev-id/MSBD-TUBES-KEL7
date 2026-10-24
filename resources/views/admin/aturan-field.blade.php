@extends('layouts.app')
@section('title', 'Aturan Field')
@section('heading', 'Aturan Perubahan Data')
@section('sub', 'Tentukan data mana yang boleh diubah siswa dan mana yang wajib disertai bukti.')
@section('content')
<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[620px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Data</th><th class="p-2">Kolom</th><th class="p-2 text-center">Boleh diubah siswa</th><th class="p-2 text-center">Wajib bukti</th><th class="p-2">Jenis bukti</th></tr></thead>
            <tbody>
            @foreach ($field as [$label, $kolom, $boleh, $bukti, $jenis])
                <tr class="border-b border-line">
                    <td class="p-2.5 font-medium">{{ $label }}</td><td class="p-2.5 text-mute">{{ $kolom }}</td>
                    <td class="p-2.5 text-center"><input type="checkbox" class="size-4 accent-flame" {{ $boleh ? 'checked' : '' }}></td>
                    <td class="p-2.5 text-center"><input type="checkbox" class="size-4 accent-flame" {{ $bukti ? 'checked' : '' }}></td>
                    <td class="p-2.5">{{ $jenis }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <button class="btn btn-flame mt-4">Simpan aturan</button>
</div>
@endsection