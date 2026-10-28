@extends('layouts.app')
@section('title', 'Aturan Field')
@section('heading', 'Aturan Perubahan Data')
@section('sub', 'Tentukan data mana yang boleh diubah siswa dan mana yang wajib disertai bukti.')
@section('content')
@if (session('success'))
    <div class="card mb-4 text-sm text-ok font-semibold">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="card mb-4 text-sm text-bad">{{ $errors->first() }}</div>
@endif
<form method="POST" action="{{ route('admin.aturan.simpan') }}" class="card">
    @csrf
    @method('PUT')
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[620px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line"><th class="p-2">Data</th><th class="p-2">Kolom</th><th class="p-2 text-center">Boleh diubah siswa</th><th class="p-2 text-center">Wajib bukti</th><th class="p-2">Jenis bukti</th></tr></thead>
            <tbody>
            @foreach ($field as $f)
                @php $k = 'aturan.'.$f->id_field; @endphp
                <tr class="border-b border-line">
                    <td class="p-2.5 font-medium">
                        <input type="hidden" name="aturan[{{ $f->id_field }}][ada]" value="1">
                        {{ $f->label_field }}
                    </td>
                    <td class="p-2.5 text-mute">{{ $f->nama_tabel }}.{{ $f->nama_kolom }}</td>
                    <td class="p-2.5 text-center"><input type="checkbox" class="size-4 accent-flame" name="aturan[{{ $f->id_field }}][boleh]" value="1" @checked(old($k.'.boleh', $f->boleh_diubah_siswa))></td>
                    <td class="p-2.5 text-center"><input type="checkbox" class="size-4 accent-flame" name="aturan[{{ $f->id_field }}][bukti]" value="1" @checked(old($k.'.bukti', $f->perlu_bukti))></td>
                    <td class="p-2.5">
                        <select class="input !py-1.5" name="aturan[{{ $f->id_field }}][jenis]">
                            <option value="">-</option>
                            @foreach ($jenis as $idJ => $namaJ)
                                <option value="{{ $idJ }}" @selected((string) old($k.'.jenis', $f->id_jenis_dokumen_bukti) === (string) $idJ)>{{ $namaJ }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <button class="btn btn-flame mt-4">Simpan aturan</button>
</form>
@endsection
