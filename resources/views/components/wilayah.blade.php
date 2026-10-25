@props(['wajib' => false])
@php
    $pohon = \App\Support\DummyData::wilayahPohon();
    $induk = ['kota' => 'Provinsi', 'kec' => 'Kota/Kabupaten', 'kel' => 'Kecamatan'];
@endphp
<div id="wilayah" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach (['prov' => 'Provinsi', 'kota' => 'Kota/Kabupaten', 'kec' => 'Kecamatan', 'kel' => 'Kelurahan'] as $id => $label)
        <div>
            <label class="label" for="w-{{ $id }}">{{ $label }}</label>
            <select class="input disabled:bg-paper disabled:text-mute/70 disabled:cursor-not-allowed"
                    id="w-{{ $id }}" name="{{ $id }}"
                    {{ $id !== 'prov' ? 'disabled' : '' }} {{ $wajib ? 'data-wajib' : '' }}>
                <option value="">{{ $id === 'prov' ? 'Pilih Provinsi' : 'Pilih ' . $induk[$id] . ' dulu' }}</option>
            </select>
        </div>
    @endforeach
</div>
<script>
(function () {
    var data = @json($pohon);
    var s = { prov: document.getElementById('w-prov'), kota: document.getElementById('w-kota'), kec: document.getElementById('w-kec'), kel: document.getElementById('w-kel') };
    var urut = ['prov', 'kota', 'kec', 'kel'], label = { prov: 'Provinsi', kota: 'Kota/Kabupaten', kec: 'Kecamatan', kel: 'Kelurahan' };

    function isi(el, daftar, nama, induk) {
        el.innerHTML = '<option value="">' + (daftar.length ? 'Pilih ' + nama : 'Pilih ' + induk + ' dulu') + '</option>';
        daftar.forEach(function (d) { var o = document.createElement('option'); o.value = d; o.textContent = d; el.appendChild(o); });
        el.disabled = daftar.length === 0;
    }
    function reset(dari) {
        for (var i = dari; i < urut.length; i++) { isi(s[urut[i]], [], label[urut[i]], label[urut[i - 1]]); }
    }
    function node(sampai) {
        var n = data;
        for (var i = 0; i < sampai; i++) { n = n[s[urut[i]].value]; }
        return n;
    }
    isi(s.prov, Object.keys(data), label.prov);

    [['prov', 1], ['kota', 2], ['kec', 3]].forEach(function (p) {
        s[p[0]].addEventListener('change', function () {
            reset(p[1]);
            if (!this.value) return;
            var anak = node(p[1]);
            isi(s[urut[p[1]]], Array.isArray(anak) ? anak : Object.keys(anak), label[urut[p[1]]], label[urut[p[1] - 1]]);
        });
    });
})();
</script>