@props(['wajib' => false, 'provinsi' => '', 'kota' => '', 'kecamatan' => '', 'kelurahan' => ''])
@php
    $provinsiTerpilih = old('prov', $provinsi);
    $kotaTerpilih = old('kota', $kota);
    $daftarProvinsi = \Illuminate\Support\Facades\Schema::hasTable('provinsi')
        ? \App\Models\Provinsi::query()
            ->orderBy('nama_provinsi')
            ->get(['id_provinsi', 'nama_provinsi'])
        : collect();
    $provinsiAwal = $daftarProvinsi->firstWhere('nama_provinsi', $provinsiTerpilih);
    $daftarKabupatenKota = $provinsiAwal && \Illuminate\Support\Facades\Schema::hasTable('kota')
        ? \App\Models\Kota::query()
            ->where('id_provinsi', $provinsiAwal->id_provinsi)
            ->where(fn ($query) => $query
                ->where('nama_kota', 'like', 'Kabupaten %')
                ->orWhere('nama_kota', 'like', 'Kota %'))
            ->orderBy('nama_kota')
            ->get(['id_kota', 'nama_kota'])
        : collect();
@endphp
<div id="wilayah" class="grid gap-4 sm:grid-cols-2">
    <div class="min-w-0">
        <label class="label" for="w-prov">Provinsi</label>
        <select class="input min-w-0 disabled:bg-paper disabled:text-mute/70 disabled:cursor-not-allowed"
                data-searchable
                id="w-prov" name="prov" @if ($wajib) data-wajib @endif>
            <option value="">Pilih Provinsi</option>
            @foreach ($daftarProvinsi as $provinsiItem)
                <option value="{{ $provinsiItem->nama_provinsi }}"
                        data-id="{{ $provinsiItem->id_provinsi }}"
                        @selected($provinsiTerpilih === $provinsiItem->nama_provinsi)>{{ \Illuminate\Support\Str::ucfirst($provinsiItem->nama_provinsi) }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-0">
        <label class="label" for="w-kota">Kabupaten/Kota</label>
        <select class="input min-w-0 disabled:bg-paper disabled:text-mute/70 disabled:cursor-not-allowed"
                data-searchable
                id="w-kota" name="kota" @disabled(! $provinsiAwal) @if ($wajib) data-wajib @endif>
            <option value="">{{ $provinsiAwal ? 'Pilih Kabupaten/Kota' : 'Pilih Provinsi dulu' }}</option>
            @foreach ($daftarKabupatenKota as $kabupatenKotaItem)
                <option value="{{ $kabupatenKotaItem->nama_kota }}" @selected($kotaTerpilih === $kabupatenKotaItem->nama_kota)>{{ \Illuminate\Support\Str::ucfirst($kabupatenKotaItem->nama_kota) }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-0">
        <label class="label" for="w-kec">Kecamatan</label>
        <input class="input min-w-0" id="w-kec" name="kec" type="text" value="{{ old('kec', $kecamatan) }}" @if ($wajib) data-wajib @endif>
    </div>
    <div class="min-w-0">
        <label class="label" for="w-kel">Kelurahan</label>
        <input class="input min-w-0" id="w-kel" name="kel" type="text" value="{{ old('kel', $kelurahan) }}" @if ($wajib) data-wajib @endif>
    </div>
</div>
<script>
(function () {
    var prov = document.getElementById('w-prov');
    var kota = document.getElementById('w-kota');
    var url = @json(route('wilayah.kabupaten', ['provinsi' => '__ID__']));

    function isiKabupatenKota(daftar) {
        kota.innerHTML = '';

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = daftar.length ? 'Pilih Kabupaten/Kota' : 'Kabupaten/Kota belum tersedia';
        kota.appendChild(placeholder);

        daftar.forEach(function (kabupatenKota) {
            var opsi = document.createElement('option');
            opsi.value = kabupatenKota.nama;
            opsi.textContent = kapitalAwal(kabupatenKota.nama);
            kota.appendChild(opsi);
        });

        kota.disabled = daftar.length === 0;
    }

    function kapitalAwal(teks) {
        return teks ? teks.charAt(0).toLocaleUpperCase('id-ID') + teks.slice(1) : teks;
    }

    function muatKabupatenKota() {
        var opsi = prov.options[prov.selectedIndex];
        var idProvinsi = opsi ? opsi.dataset.id : '';

        if (!idProvinsi) {
            isiKabupatenKota([]);
            return;
        }

        kota.disabled = true;
        fetch(url.replace('__ID__', idProvinsi), { headers: { Accept: 'application/json' } })
            .then(function (respons) {
                if (!respons.ok) throw new Error('Gagal memuat data kabupaten/kota.');
                return respons.json();
            })
            .then(function (data) { isiKabupatenKota(data); })
            .catch(function () {
                isiKabupatenKota([]);
                kota.options[0].textContent = 'Gagal memuat kabupaten/kota. Pilih ulang provinsi.';
            });
    }

    prov.addEventListener('change', muatKabupatenKota);
})();
</script>
