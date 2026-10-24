@extends('layouts.app')
@section('title', 'Formulir PPDB')
@section('heading', 'Formulir Data Calon Siswa')
@section('sub', 'Isi sesuai dokumen asli (KK, akta, ijazah). Data akan diperiksa Tata Usaha sebelum disahkan.')
@section('content')
<div class="flex gap-2 mb-5 text-sm flex-wrap" id="stepper">
    @foreach (['Data diri', 'Orang tua', 'Berkas', 'Periksa & kirim'] as $i => $t)
        <div data-ind="{{ $i }}" class="flex-1 min-w-24 pb-2 border-b-[3px] border-line text-mute"><span class="font-serif mr-1">{{ $i + 1 }}</span>{{ $t }}</div>
    @endforeach
</div>

<form class="space-y-4" onsubmit="return false" id="ppdb">
    <div data-step="0" class="space-y-4">
        <div class="card">
            <h2 class="sec-title">A. Data pribadi</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <x-field label="Nama lengkap" name="nama_lengkap" hint="Sesuai akta kelahiran" />
                <x-field label="NISN" name="nisn" hint="10 digit. Coba ketik 0012345678 untuk melihat peringatan ganda" />
                <x-field label="NIK" name="nik" hint="16 digit" />
                <div><label class="label">Jenis kelamin</label><select class="input"><option>Pilih</option><option>Laki-laki</option><option>Perempuan</option></select></div>
                <x-field label="Tempat lahir" name="tempat_lahir" />
                <x-field label="Tanggal lahir" name="tanggal_lahir" type="date" />
                <div><label class="label">Agama</label><select class="input"><option>Kristen Protestan</option><option>Katolik</option><option>Islam</option><option>Buddha</option><option>Hindu</option><option>Konghucu</option></select></div>
                <x-field label="Asal SMP/MTs" name="sekolah_asal" />
                <x-field label="Nomor Kartu Keluarga" name="no_kk" hint="16 digit" />
            </div>
        </div>
        <div class="card">
            <h2 class="sec-title">B. Alamat</h2>
            <x-wilayah />
            <div class="grid sm:grid-cols-[1fr_90px_90px] gap-4 mt-4">
                <x-field label="Jalan" name="jalan" /><x-field label="RT" name="rt" /><x-field label="RW" name="rw" />
            </div>
        </div>
    </div>

    <div data-step="1" class="space-y-4 hidden">
        @foreach (['Ayah', 'Ibu', 'Wali (jika ada)'] as $o)
            <div class="card">
                <h2 class="sec-title">{{ $o }}</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-field label="Nama" name="ortu_nama_{{ $loop->index }}" />
                    <x-field label="NIK" name="ortu_nik_{{ $loop->index }}" />
                    <div><label class="label">Pekerjaan</label><select class="input"><option>Pilih pekerjaan</option><option>PNS / ASN</option><option>Karyawan swasta</option><option>Wiraswasta</option><option>Lainnya</option></select></div>
                    <x-field label="No. HP" name="ortu_hp_{{ $loop->index }}" />
                </div>
            </div>
        @endforeach
    </div>

    <div data-step="2" class="hidden">
        <div class="card">
            <h2 class="sec-title">Unggah berkas</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach (['Kartu Keluarga', 'Akta Kelahiran', 'Ijazah / SKL', 'Pas Foto 3x4'] as $d)
                    <label class="flex items-center justify-between gap-3 border-2 border-dashed border-line rounded-lg p-3.5 text-sm cursor-pointer hover:border-flame">
                        <span>{{ $d }}</span><span class="btn btn-ghost !py-1.5">Pilih file</span><input type="file" class="hidden">
                    </label>
                @endforeach
            </div>
            <p class="text-[11px] text-mute mt-2">Bisa langsung foto dari HP. Maksimal 2 MB per file.</p>
        </div>
    </div>

    <div data-step="3" class="hidden">
        <div class="card">
            <h2 class="sec-title">Periksa sebelum mengirim</h2>
            <p class="text-sm text-mute">Pastikan data dan berkas sudah benar. Setelah dikirim, data diperiksa Tata Usaha dan tidak bisa diubah sampai ada hasil pemeriksaan.</p>
            <label class="flex items-start gap-3 mt-4 text-sm cursor-pointer"><input type="checkbox" class="size-4 mt-0.5 accent-[#d9822b]"> Saya menyatakan data yang saya isi sesuai dengan dokumen asli.</label>
        </div>
    </div>

    <div class="flex justify-between flex-wrap gap-2">
        <button type="button" id="prev" class="btn btn-ghost">← Kembali</button>
        <div class="flex gap-2">
            <button type="button" class="btn btn-ghost">Simpan draf</button>
            <button type="button" id="next" class="btn btn-flame">Lanjut →</button>
        </div>
    </div>
</form>

<script>
(function () {
    var cur = 0, steps = document.querySelectorAll('[data-step]'), inds = document.querySelectorAll('[data-ind]');
    var prev = document.getElementById('prev'), next = document.getElementById('next');

    // Peringatan pendaftaran ganda (data contoh; pengecekan sungguhan di backend)
    var dup = @json(\App\Support\DummyData::pendaftarTerdaftar());
    var peringatan = [];
    function pantau(id, jenis) {
        var el = document.getElementById(id), box = document.createElement('p');
        box.className = 'text-xs text-bad mt-1 hidden';
        el.parentNode.appendChild(box);
        el.addEventListener('input', function () {
            var nama = dup[jenis][el.value.trim()];
            box.dataset.dup = nama ? '1' : '';
            box.textContent = nama ? jenis.toUpperCase() + ' ini sudah terdaftar atas nama ' + nama + '. Cek menu Status Pendaftaran atau hubungi Tata Usaha.' : '';
            box.classList.toggle('hidden', !nama);
            el.classList.toggle('!border-bad', !!nama);
        });
        peringatan.push(box);
    }
    pantau('nisn', 'nisn'); pantau('nik', 'nik');

    function show(n) {
        cur = n;
        steps.forEach(function (s, i) { s.classList.toggle('hidden', i !== n); });
        inds.forEach(function (d, i) {
            d.className = 'flex-1 min-w-24 pb-2 border-b-[3px] ' + (i === n ? 'border-flame text-navy font-semibold' : (i < n ? 'border-navy text-navy' : 'border-line text-mute'));
        });
        prev.style.visibility = n === 0 ? 'hidden' : 'visible';
        next.textContent = n === steps.length - 1 ? 'Kirim pendaftaran' : 'Lanjut →';
        window.scrollTo(0, 0);
    }
    prev.onclick = function () { if (cur > 0) show(cur - 1); };
    next.onclick = function () {
        if (cur === 0 && peringatan.some(function (b) { return b.dataset.dup; })) {
            alert('NISN atau NIK ini sudah terdaftar. Periksa kembali data yang kamu isi.');
            return;
        }
        if (cur < steps.length - 1) show(cur + 1); else alert('Tampilan rancangan: belum tersambung ke database.');
    };
    show(0);
})();
</script>
@endsection