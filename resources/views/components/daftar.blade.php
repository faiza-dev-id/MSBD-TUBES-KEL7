@props(['judul', 'kolom', 'baris', 'tambah' => null])
@php $uid = 'daftar'.uniqid(); @endphp
<div class="card" id="{{ $uid }}" data-kolom="{{ json_encode($kolom) }}">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
        <h2 class="font-serif text-lg font-bold">{{ $judul }}</h2>
        <div class="flex gap-2 flex-wrap">
            <input class="input !w-48" placeholder="Cari...">
            @if ($tambah) <button type="button" data-aksi="tambah" class="btn btn-flame">+ {{ $tambah }}</button> @endif
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[480px]">
            <thead><tr class="text-left text-xs text-mute border-b-2 border-line">
                @foreach ($kolom as $k)<th class="p-2">{{ $k }}</th>@endforeach<th></th>
            </tr></thead>
            <tbody>
            @foreach ($baris as $row)
                <tr class="border-b border-line" data-row="{{ json_encode(array_values($row)) }}">
                    @foreach ($row as $i => $cell)<td class="p-2.5 {{ $i === 0 ? 'font-medium' : '' }}">{{ $cell }}</td>@endforeach
                    <td class="p-2.5 text-right whitespace-nowrap">
                        <button type="button" data-aksi="ubah" class="btn btn-ghost !py-1.5">Ubah</button>
                        <button type="button" data-aksi="hapus" class="btn btn-ghost !py-1.5 !text-bad">Hapus</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <dialog data-dlg="edit" class="m-auto w-[min(92vw,28rem)] rounded-xl border border-line bg-card p-0 text-ink backdrop:bg-black/40">
        <div class="p-5 space-y-4">
            <h3 class="font-serif text-lg font-bold text-navy" data-title>Ubah data</h3>
            <div class="space-y-3" data-fields></div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Batal</button>
                <button type="button" class="btn btn-flame" data-simpan>Simpan</button>
            </div>
        </div>
    </dialog>

    <dialog data-dlg="hapus" class="m-auto w-[min(92vw,26rem)] rounded-xl border border-line bg-card p-0 text-ink backdrop:bg-black/40">
        <div class="p-5 space-y-4">
            <h3 class="font-serif text-lg font-bold text-navy">Hapus data?</h3>
            <p class="text-sm text-mute">Data <b class="text-ink" data-nama></b> akan dihapus. Perubahan ini tercatat di jejak audit.</p>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Batal</button>
                <button type="button" class="btn !bg-bad hover:!bg-bad/90" data-simpan>Ya, hapus</button>
            </div>
        </div>
    </dialog>
</div>
<script>
(function () {
    var root = document.getElementById('{{ $uid }}');
    var kolom = JSON.parse(root.dataset.kolom);
    var dlg = root.querySelector('[data-dlg="edit"]'), del = root.querySelector('[data-dlg="hapus"]');
    var fields = dlg.querySelector('[data-fields]'), title = dlg.querySelector('[data-title]');

    function buka(nilai, judul) {
        title.textContent = judul;
        fields.innerHTML = '';
        kolom.forEach(function (k, i) {
            var w = document.createElement('div'), l = document.createElement('label'), inp = document.createElement('input');
            l.className = 'label'; l.textContent = k;
            inp.className = 'input'; inp.value = nilai ? nilai[i] : '';
            w.appendChild(l); w.appendChild(inp); fields.appendChild(w);
        });
        dlg.showModal();
    }
    root.addEventListener('click', function (e) {
        var b = e.target.closest('[data-aksi]');
        if (!b) return;
        var tr = b.closest('tr'), row = tr ? JSON.parse(tr.dataset.row) : null;
        if (b.dataset.aksi === 'tambah') buka(null, 'Tambah data');
        else if (b.dataset.aksi === 'ubah') buka(row, 'Ubah data');
        else { del.querySelector('[data-nama]').textContent = row[0]; del.showModal(); }
    });
    root.querySelectorAll('[data-simpan]').forEach(function (b) {
        b.addEventListener('click', function () {
            b.closest('dialog').close();
            alert('Tampilan rancangan: perubahan belum tersimpan ke database.');
        });
    });
})();
</script>