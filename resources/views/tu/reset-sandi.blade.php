@extends('layouts.app')
@section('title', 'Reset Sandi Akun')
@section('heading', 'Reset Sandi Akun')
@section('sub', 'Hanya Tata Usaha yang dapat mereset sandi akun lain. Sandi sementara tampil satu kali, serahkan langsung ke pemilik akun.')
@section('content')
<div id="hasil" class="hidden mb-4 rounded-xl border border-ok/40 bg-ok/10 px-4 py-3 text-sm text-ok"></div>

<div class="card">
    <div class="mb-3">
        <input id="cari" class="input !w-64" placeholder="Cari nama / username">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
            <thead>
                <tr class="text-left text-xs text-mute border-b-2 border-line">
                    <th class="p-2">Nama</th><th class="p-2">Username</th><th class="p-2">Peran</th><th class="p-2">Status</th><th></th>
                </tr>
            </thead>
            <tbody id="baris">
            @foreach ($pengguna as $u)
                <tr class="border-b border-line" data-cari="{{ strtolower($u['nama'].' '.$u['username']) }}">
                    <td class="p-2.5 font-medium">{{ $u['nama'] }}</td>
                    <td class="p-2.5">{{ $u['username'] }}</td>
                    <td class="p-2.5">{{ $u['role'] }}</td>
                    <td class="p-2.5"><span class="text-xs font-semibold {{ $u['aktif'] ? 'text-ok' : 'text-mute' }}">{{ $u['aktif'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="p-2.5 text-right">
                        @if ($u['aktif'])
                            <button type="button" class="btn btn-ghost !py-1.5 js-reset" data-nama="{{ $u['nama'] }}">Reset sandi</button>
                        @else
                            <span class="text-xs text-mute">Akun nonaktif</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var hasil = document.getElementById('hasil');
    document.querySelectorAll('.js-reset').forEach(function (b) {
        b.addEventListener('click', function () {
            var nama = b.dataset.nama;
            if (!confirm('Reset sandi untuk ' + nama + '?')) return;
            // CONTOH TAMPILAN SAJA: sandi sementara yang asli dibuat backend di server.
            var s = Math.random().toString(36).slice(2, 10);
            hasil.textContent = 'Sandi sementara untuk ' + nama + ': ' + s + ' (contoh tampilan, serahkan langsung ke pemilik akun).';
            hasil.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
    document.getElementById('cari').addEventListener('input', function (e) {
        var q = e.target.value.toLowerCase();
        document.querySelectorAll('#baris tr').forEach(function (tr) {
            tr.style.display = tr.dataset.cari.indexOf(q) > -1 ? '' : 'none';
        });
    });
})();
</script>
@endsection