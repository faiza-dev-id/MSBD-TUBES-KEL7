// Validasi form di sisi tampilan. Aktif otomatis untuk <form data-validasi>.
// Aturan dibaca dari atribut kolom: data-wajib, data-digit, data-hp, data-min, data-sandi,
// data-cocok, data-beda, data-setelah, data-rentang, data-pola (+ data-pesan).
// Catatan: ini hanya untuk kenyamanan pengguna. Validasi yang sah tetap di server (backend).

const MB = 1024 * 1024;
const ATURAN = [
    '[data-wajib]', '[data-digit]', '[data-hp]', '[data-min]', '[data-sandi]',
    '[data-cocok]', '[data-beda]', '[data-setelah]', '[data-rentang]', '[data-pola]',
    'input[type=file]',
].join(',');

const adaFile = (el) => el.type === 'file';
const adaCek = (el) => el.type === 'checkbox';

function nama(el) {
    if (!el.dataset.nama) {
        const lb = el.parentNode.querySelector('label');
        el.dataset.nama = lb ? lb.textContent.replace('*', '').trim() : (el.name || 'Isian');
    }
    return el.dataset.nama;
}

function induk(el) {
    return (adaFile(el) || adaCek(el)) ? el.closest('label').parentNode : el.parentNode;
}

function kotakPesan(host) {
    let p = host.querySelector(':scope > .err');
    if (!p) {
        p = document.createElement('p');
        p.className = 'err text-xs text-bad mt-1 hidden';
        p.setAttribute('role', 'alert');
        host.appendChild(p);
    }
    return p;
}

// Mengembalikan teks pesan kesalahan, atau '' kalau isian sudah benar.
export function cek(el) {
    if (el.disabled) return '';
    const n = nama(el), d = el.dataset, wajib = el.hasAttribute('data-wajib');

    if (adaCek(el)) return (wajib && !el.checked) ? (d.pesan || n + ' harus dicentang.') : '';

    if (adaFile(el)) {
        const f = el.files && el.files[0];
        if (!f) return wajib ? n + ' wajib diunggah.' : '';
        const maks = Number(d.maksMb || 2);
        if (f.size > maks * MB) return 'Ukuran ' + n + ' maksimal ' + maks + ' MB.';
        if (!/\.(pdf|jpe?g|png)$/i.test(f.name)) return n + ' harus berformat PDF, JPG, atau PNG.';
        return '';
    }

    const v = el.value.trim();
    if (!v) return wajib ? n + (el.tagName === 'SELECT' ? ' wajib dipilih.' : ' wajib diisi.') : '';

    if (d.digit && !new RegExp('^\\d{' + d.digit + '}$').test(v)) return n + ' harus ' + d.digit + ' digit angka.';
    if (el.hasAttribute('data-hp') && !/^(\+62|62|0)8\d{8,12}$/.test(v.replace(/[\s-]/g, ''))) return n + ' tidak valid. Contoh: 081234567890.';
    if (d.min && v.length < Number(d.min)) return n + ' minimal ' + d.min + ' karakter.';
    if (el.hasAttribute('data-sandi') && (v.length < 8 || !/[A-Za-z]/.test(v) || !/\d/.test(v))) return 'Sandi minimal 8 karakter dan harus mengandung huruf dan angka.';
    if (d.pola && !new RegExp(d.pola, 'i').test(v)) return d.pesan || n + ' tidak sesuai format.';
    if (d.rentang) {
        const [a, b] = d.rentang.split(',').map(Number), x = Number(v.replace(',', '.'));
        if (Number.isNaN(x) || x < a || x > b) return n + ' harus berupa angka ' + a + ' sampai ' + b + '.';
    }
    const o = (sel) => (sel && el.form ? el.form.querySelector(sel) : null);
    if (d.cocok && o(d.cocok) && o(d.cocok).value !== el.value) return d.pesan || n + ' tidak sama.';
    if (d.beda && o(d.beda) && o(d.beda).value && o(d.beda).value === el.value) return d.pesan || n + ' tidak boleh sama dengan ' + nama(o(d.beda)) + '.';
    if (d.setelah && o(d.setelah) && o(d.setelah).value && el.value < o(d.setelah).value) return d.pesan || n + ' tidak boleh sebelum ' + nama(o(d.setelah)) + '.';
    return '';
}

export function tampil(el) {
    const msg = cek(el), p = kotakPesan(induk(el));
    p.textContent = msg;
    p.classList.toggle('hidden', !msg);
    if (!adaCek(el)) (adaFile(el) ? el.closest('label') : (el._dd || el)).classList.toggle('!border-bad', !!msg);
    return msg;
}

// Grup centang: minimal sekian item harus dicentang. <div data-grup data-minimal="1" data-pesan="...">
function tampilGrup(g) {
    const min = Number(g.dataset.minimal || 1);
    const msg = g.querySelectorAll('input[type=checkbox]:checked').length >= min ? '' : (g.dataset.pesan || 'Pilih minimal ' + min + ' item.');
    const p = kotakPesan(g);
    p.textContent = msg;
    p.classList.toggle('hidden', !msg);
    return msg;
}

// Periksa semua kolom dalam satu wadah (form atau satu langkah). Mengembalikan daftar kolom yang salah.
export function periksa(root) {
    const salah = [];
    root.querySelectorAll(ATURAN).forEach((el) => { if (tampil(el)) salah.push(el); });
    root.querySelectorAll('[data-grup]').forEach((g) => { if (tampilGrup(g)) salah.push(g); });
    return salah.sort((a, b) => (a.compareDocumentPosition(b) & 4 ? -1 : 1));
}

export function fokus(el) {
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    if (el.focus && !adaFile(el) && !el.hasAttribute('data-grup')) el.focus({ preventScroll: true });
}

let kotak, timer;
export function toast(pesan, jenis = 'bad') {
    if (!kotak) {
        kotak = document.createElement('div');
        kotak.setAttribute('role', 'status');
        document.body.appendChild(kotak);
    }
    kotak.className = 'fixed top-20 left-1/2 -translate-x-1/2 z-[60] max-w-[92vw] rounded-xl px-4 py-3 text-sm font-medium shadow-lg text-white '
        + (jenis === 'ok' ? 'bg-ok' : 'bg-bad');
    kotak.textContent = pesan;
    clearTimeout(timer);
    timer = setTimeout(() => kotak.classList.add('hidden'), 4500);
}

function pasang(form) {
    form.setAttribute('novalidate', '');

    // tanda * merah pada label kolom wajib
    form.querySelectorAll('[data-wajib]').forEach((el) => {
        if (adaFile(el) || adaCek(el)) return;
        const lb = el.parentNode.querySelector('label');
        if (!lb || lb.querySelector('.bintang')) return;
        nama(el);
        lb.insertAdjacentHTML('beforeend', ' <span class="bintang text-bad">*</span>');
    });

    form.querySelectorAll(ATURAN).forEach((el) => {
        const langsung = adaFile(el) || adaCek(el) || el.tagName === 'SELECT';
        el.addEventListener(langsung ? 'change' : 'blur', () => tampil(el));
        el.addEventListener('input', () => { if (!kotakPesan(induk(el)).classList.contains('hidden')) tampil(el); });
        if (adaFile(el)) {
            el.addEventListener('change', () => {
                const f = el.files[0], s = el.closest('label').querySelector('.nama-file');
                if (s) s.textContent = f ? f.name : 'Belum ada file';
            });
        }
    });
    form.querySelectorAll('[data-grup]').forEach((g) => {
        g.addEventListener('change', () => { if (!kotakPesan(g).classList.contains('hidden')) tampilGrup(g); });
    });

    form.addEventListener('submit', (e) => {
        const salah = periksa(form);
        if (salah.length) {
            e.preventDefault();
            toast('Masih ada ' + salah.length + ' isian yang belum lengkap atau belum sesuai.');
            fokus(salah[0]);
            return;
        }
        // aksi penting (mis. kenaikan kelas massal) minta konfirmasi dulu: <form data-konfirmasi="Yakin?">
        if (form.dataset.konfirmasi && !window.confirm(form.dataset.konfirmasi)) {
            e.preventDefault();
            return;
        }
        if (!form.getAttribute('action')) {
            e.preventDefault(); // mode rancangan: belum ada tujuan kirim
            toast('Isian sudah lengkap. (Tampilan rancangan, belum tersambung ke database.)', 'ok');
        }
    });
}

if (typeof document !== 'undefined') {
    const mulai = () => document.querySelectorAll('form[data-validasi]').forEach(pasang);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mulai); else mulai();
}
window.Validasi = { cek, tampil, periksa, fokus, toast };