@extends('layouts.app')
@section('title', 'Peran & Akses')
@section('heading', 'Peran dan Hak Akses')
@section('sub', 'Centang hak akses yang dimiliki tiap peran.')
@section('content')
<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Hak akses</th>@foreach ($roles as $r)<th class="p-2 text-center">{{ $r }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach ($hak as $nama => $baris)
                <tr class="border-b border-line">
                    <td class="p-2.5">{{ $nama }}</td>
                    @foreach ($baris as $v)<td class="p-2.5 text-center"><input type="checkbox" class="size-4 accent-[#d9822b]" {{ $v ? 'checked' : '' }}></td>@endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <button class="btn btn-flame mt-4">Simpan perubahan</button>
</div>
@endsection