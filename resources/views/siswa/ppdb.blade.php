@extends('layouts.publik')
@section('title', 'Formulir PPDB')
@section('heading', 'Formulir Data Calon Siswa')
@section('sub', 'Isi sesuai dokumen asli (KK, akta, ijazah). Data akan diperiksa Tata Usaha sebelum disahkan.')
@section('content')
<div class="mb-5">
    <div class="flex gap-1.5" id="stepper">
        @foreach (range(0, 3) as $i)
            <div data-ind="{{ $i }}" class="flex-1 h-1.5 rounded-full bg-line"></div>
        @endforeach
    </div>
    <p id="stepLabel" class="mt-2 text-sm font-semibold text-navy"></p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad" role="alert">{{ $errors->first() }}</div>
@endif
<div id="ringkas" class="hidden mb-4 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad" role="alert"></div>

<form class="space-y-4" method="POST" action="{{ route('ppdb.store') }}" enctype="multipart/form-data" id="ppdb" novalidate>
    @csrf
    <div data-step="0" class="space-y-4">
        <div class="card">
            <h2 class="sec-title">A. Data pribadi</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <x-field label="Nama lengkap" name="nama_lengkap" wajib="1" hint="Sesuai akta kelahiran" />
                <x-field label="NISN" name="nisn" wajib="1" digit="10" hint="10 digit. Coba ketik 0012345678 untuk melihat peringatan ganda" />
                <x-field label="NIK" name="nik" wajib="1" digit="16" hint="16 digit" />
                <div>
                    <label class="label" for="jenis_kelamin">Jenis kelamin</label>
                    <select class="input" id="jenis_kelamin" name="jenis_kelamin" data-wajib>
                        <option value="">Pilih jenis kelamin</option><option>Laki-laki</option><option>Perempuan</option>
                    </select>
                </div>
                <x-field label="Tempat lahir" name="tempat_lahir" wajib="1" />
                <x-field label="Tanggal lahir" name="tanggal_lahir" type="date" wajib="1" />
                <div>
                    <label class="label" for="agama">Agama</label>
                    <select class="input" id="agama" name="agama" data-wajib>
                        <option value="">Pilih agama</option>
                        @foreach (\App\Support\DummyData::daftarAgama() as $a)<option>{{ $a }}</option>@endforeach
                    </select>
                </div>
                <x-field label="Asal SMP/MTs" name="sekolah_asal" wajib="1" />
                <x-field label="Nomor Kartu Keluarga" name="no_kk" wajib="1" digit="16" hint="16 digit" />
                <x-field label="No. HP / WhatsApp" name="no_hp" wajib="1" hp="1" hint="Kabar status pendaftaran dikirim ke nomor ini" />
                <div><label class="label" for="jenjang_tujuan">Jenjang tujuan</label><select class="input" id="jenjang_tujuan" name="jenjang_tujuan"><option>SMA</option></select></div>
            </div>
        </div>
        <div class="card">
            <h2 class="sec-title">B. Alamat</h2>
            <x-wilayah wajib="1" />
            <div class="grid grid-cols-2 sm:grid-cols-[1fr_90px_90px] gap-4 mt-4">
                <div class="col-span-2 sm:col-span-1"><x-field label="Jalan" name="jalan" wajib="1" /></div>
                <x-field label="RT" name="rt" wajib="1" />
                <x-field label="RW" name="rw" wajib="1" />
            </div>
        </div>
    </div>

    <div data-step="1" class="space-y-4 hidden">
        @foreach (['Ayah', 'Ibu', 'Wali (jika ada)'] as $o)
            <div class="card">
                <h2 class="sec-title">{{ $o }}</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-field label="Nama" name="ortu_nama_{{ $loop->index }}" :wajib="$loop->index < 2" />
                    <x-field label="NIK" name="ortu_nik_{{ $loop->index }}" digit="16" />
                    <div>
                        <label class="label" for="ortu_kerja_{{ $loop->index }}">Pekerjaan</label>
                        <select class="input" id="ortu_kerja_{{ $loop->index }}" name="ortu_kerja_{{ $loop->index }}" @if ($loop->index < 2) data-wajib @endif>
                            <option value="">Pilih pekerjaan</option>
                            @foreach (\App\Support\DummyData::daftarPekerjaan() as $k)<option>{{ $k }}</option>@endforeach
                        </select>
                    </div>
                    <x-field label="No. HP" name="ortu_hp_{{ $loop->index }}" hp="1" />
                </div>
            </div>
        @endforeach
    </div>

    <div data-step="2" class="hidden">
        <div class="card">
            <h2 class="sec-title">Unggah berkas</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach (['Kartu Keluarga', 'Akta Kelahiran', 'Ijazah / SKL', 'Pas Foto 3x4'] as $d)
                    <div>
                        <label class="flex items-center justify-between gap-3 border-2 border-dashed border-line rounded-xl p-3.5 text-sm cursor-pointer hover:border-flame">
                            <span class="min-w-0">
                                <span class="block font-medium">{{ $d }} <span class="text-bad">*</span></span>
                                <span class="nama-file block text-xs text-mute truncate">Belum ada file</span>
                            </span>
                            <span class="btn btn-ghost !py-1.5 shrink-0">Pilih file</span>
                            <input type="file" class="hidden" name="berkas_{{ $loop->index }}" accept=".pdf,.jpg,.jpeg,.png" data-wajib data-nama="{{ $d }}">
                        </label>
                    </div>
                @endforeach
            </div>
            <p class="text-[11px] text-mute mt-2">Bisa langsung foto dari HP. Format PDF, JPG, atau PNG, maksimal 2 MB per file.</p>
        </div>
    </div>

    <div data-step="3" class="hidden">
        <div class="card">
            <h2 class="sec-title">Periksa sebelum mengirim</h2>
            <p class="text-sm text-mute">Pastikan data dan berkas sudah benar. Setelah dikirim, data diperiksa Tata Usaha dan tidak bisa diubah sampai ada hasil pemeriksaan.</p>
            <p class="text-sm text-mute mt-2">Setelah terkirim kamu mendapat <b>kode pendaftaran</b>. Simpan kode itu untuk mengecek status di halaman <a href="{{ route('ppdb.status') }}" class="font-semibold text-flame">Cek Status</a>.</p>
            <label class="flex items-start gap-3 mt-4 text-sm cursor-pointer">
                <input type="checkbox" name="pernyataan" class="size-4 mt-0.5 accent-flame" data-wajib> Saya menyatakan data yang saya isi sesuai dengan dokumen asli.
            </label>
        </div>
    </div>

    {{-- tombol navigasi: menempel di bawah layar pada HP --}}
    <div class="sticky bottom-0 z-20 -mx-4 px-4 py-3 bg-paper/90 backdrop-blur border-t border-line sm:static sm:mx-0 sm:px-0 sm:py-0 sm:bg-transparent sm:backdrop-blur-none sm:border-0">
        <div class="flex items-center gap-2">
            <button type="button" id="prev" class="btn btn-ghost">← Kembali</button>
            <button type="button" class="btn btn-ghost ml-auto">
                <span class="sm:hidden">Draf</span><span class="hidden sm:inline">Simpan draf</span>
            </button>
            <button type="button" id="next" class="btn btn-flame">Lanjut →</button>
        </div>
    </div>
</form>

<script>
(function () {
    var cur = 0, steps = document.querySelectorAll('[data-step]'), inds = document.querySelectorAll('[data-ind]');
    var prev = document.getElementById('prev'), next = document.getElementById('next');
    var judul = ['Data diri', 'Orang tua', 'Berkas', 'Periksa & kirim'];
    var ringkas = document.getElementById('ringkas');
    var ATURAN = '[data-wajib],[data-digit],[data-hp],input[type=file]';

    // ---------- Peringatan pendaftaran ganda (data contoh; pengecekan sungguhan di backend) ----------
    var dup = @json(\App\Support\DummyData::pendaftarTerdaftar());
    var peringatan = [];
    function pantau(id, jenis) {
        var el = document.getElementById(id), box = document.createElement('p');
        box.className = 'text-xs text-bad mt-1 hidden';
        el.parentNode.appendChild(box);
        el.addEventListener('input', function () {
            var nama = dup[jenis][el.value.trim()];
            box.dataset.dup = nama ? '1' : '';
            box.textContent = nama ? jenis.toUpperCase() + ' ini sudah terdaftar atas nama ' + nama + '. Cek lewat halaman Cek Status atau hubungi Tata Usaha.' : '';
            box.classList.toggle('hidden', !nama);
            el.classList.toggle('!border-bad', !!nama);
        });
        peringatan.push(box);
    }
    pantau('nisn', 'nisn'); pantau('nik', 'nik');

    // ---------- Validasi isian ----------
    // Tanda * merah di label kolom wajib
    document.querySelectorAll('[data-wajib]').forEach(function (el) {
        if (el.type === 'file' || el.type === 'checkbox') return;
        var lb = el.parentNode.querySelector('label');
        if (!lb) return;
        el.dataset.nama = el.dataset.nama || lb.textContent.trim();
        lb.insertAdjacentHTML('beforeend', ' <span class="text-bad">*</span>');
    });

    function nm(el) {
        if (el.dataset.nama) return el.dataset.nama;
        var lb = el.parentNode.querySelector('label');
        return lb ? lb.textContent.trim() : el.name;
    }
    function boxErr(el) {
        var host = (el.type === 'file' || el.type === 'checkbox') ? el.closest('label').parentNode : el.parentNode;
        var p = host.querySelector(':scope > .err');
        if (!p) {
            p = document.createElement('p');
            p.className = 'err text-xs text-bad mt-1 hidden';
            p.setAttribute('role', 'alert');
            host.appendChild(p);
        }
        return p;
    }
    function cek(el) {
        if (el.disabled) return '';
        var nama = nm(el), wajib = el.hasAttribute('data-wajib');
        if (el.type === 'checkbox') return (wajib && !el.checked) ? 'Centang pernyataan ini untuk melanjutkan.' : '';
        if (el.type === 'file') {
            var f = el.files[0];
            if (!f) return wajib ? nama + ' wajib diunggah.' : '';
            if (f.size > 2 * 1024 * 1024) return 'Ukuran ' + nama + ' maksimal 2 MB.';
            if (!/\.(pdf|jpe?g|png)$/i.test(f.name)) return nama + ' harus berformat PDF, JPG, atau PNG.';
            return '';
        }
        var v = el.value.trim();
        if (!v) return wajib ? nama + (el.tagName === 'SELECT' ? ' wajib dipilih.' : ' wajib diisi.') : '';
        if (el.dataset.digit && !new RegExp('^\\d{' + el.dataset.digit + '}$').test(v)) return nama + ' harus ' + el.dataset.digit + ' digit angka.';
        if (el.hasAttribute('data-hp') && !/^(\+62|62|0)8\d{8,12}$/.test(v.replace(/[\s-]/g, ''))) return nama + ' tidak valid. Contoh: 081234567890.';
        return '';
    }
    function tampil(el) {
        var msg = cek(el), p = boxErr(el);
        p.textContent = msg;
        p.classList.toggle('hidden', !msg);
        if (el.type !== 'checkbox') (el.type === 'file' ? el.closest('label') : (el._dd || el)).classList.toggle('!border-bad', !!msg);
        return msg;
    }
    function periksa(n) {
        var salah = [];
        steps[n].querySelectorAll(ATURAN).forEach(function (el) { if (tampil(el)) salah.push(el); });
        return salah;
    }
    function validasiStep(n) {
        var salah = periksa(n);
        if (salah.length) {
            ringkas.textContent = 'Masih ada ' + salah.length + ' isian yang belum lengkap atau belum sesuai. Periksa kolom yang bertanda merah.';
            ringkas.classList.remove('hidden');
            salah[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (salah[0].type !== 'file' && salah[0].focus) salah[0].focus({ preventScroll: true });
        } else {
            ringkas.classList.add('hidden');
        }
        return salah.length === 0;
    }

    // Pesan muncul saat keluar dari kolom, dan hilang otomatis begitu isian diperbaiki
    document.querySelectorAll(ATURAN).forEach(function (el) {
        var langsung = el.type === 'file' || el.type === 'checkbox' || el.tagName === 'SELECT';
        el.addEventListener(langsung ? 'change' : 'blur', function () { tampil(el); });
        el.addEventListener('input', function () { if (!boxErr(el).classList.contains('hidden')) tampil(el); });
        if (el.type === 'file') {
            el.addEventListener('change', function () {
                var f = el.files[0];
                el.closest('label').querySelector('.nama-file').textContent = f ? f.name : 'Belum ada file';
            });
        }
    });

    // ---------- Langkah ----------
    function show(n) {
        cur = n;
        steps.forEach(function (s, i) { s.classList.toggle('hidden', i !== n); });
        inds.forEach(function (d, i) { d.className = 'flex-1 h-1.5 rounded-full ' + (i <= n ? 'bg-flame' : 'bg-line'); });
        document.getElementById('stepLabel').textContent = 'Langkah ' + (n + 1) + ' dari ' + steps.length + ' · ' + judul[n];
        prev.style.visibility = n === 0 ? 'hidden' : 'visible';
        next.textContent = n === steps.length - 1 ? 'Kirim pendaftaran' : 'Lanjut →';
        window.scrollTo(0, 0);
    }
    prev.onclick = function () { if (cur > 0) { ringkas.classList.add('hidden'); show(cur - 1); } };
    next.onclick = function () {
        if (!validasiStep(cur)) return;
        if (cur === 0 && peringatan.some(function (b) { return b.dataset.dup; })) {
            alert('NISN atau NIK ini sudah terdaftar. Periksa kembali data yang kamu isi.');
            return;
        }
        if (cur < steps.length - 1) { show(cur + 1); return; }

        // Kirim: cek ulang semua langkah, lompat ke langkah pertama yang masih bermasalah
        for (var i = 0; i < steps.length; i++) {
            if (periksa(i).length) { show(i); validasiStep(i); return; }
        }
        document.getElementById('ppdb').requestSubmit();
    };
    show(0);
})();
</script>
@endsection