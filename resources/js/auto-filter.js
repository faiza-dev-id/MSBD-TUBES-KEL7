// Auto-filter khusus form pencarian/penyaringan GET yang ditandai data-auto-filter.
// Form aksi (POST/PUT/PATCH/DELETE) TIDAK pernah disentuh di sini.
//
// - select / radio / checkbox / tanggal -> langsung kirim saat change
// - teks / search                       -> debounce 550ms setelah berhenti mengetik
// - Enter pada kolom teks               -> kirim seketika (tetap memunculkan pesan validasi)
//
// Form dengan data-auto-filter-valid baru dikirim otomatis kalau isian wajib/formatnya
// sudah benar. Ini dipakai form lookup yang memerlukan dua kolom lengkap.

const JEDA = 550;
const FOKUS = 'auto-filter-fokus';
const LANGSUNG = [
    'select',
    'input[type="radio"]',
    'input[type="checkbox"]',
    'input[type="date"]',
].join(',');
const TEKS = [
    'input:not([type])',
    'input[type="text"]',
    'input[type="search"]',
].join(',');
const ATURAN = [
    '[data-wajib]', '[data-digit]', '[data-hp]', '[data-min]', '[data-sandi]',
    '[data-pola]', '[data-rentang]', '[data-cocok]', '[data-beda]', '[data-setelah]',
].join(',');

const penunda = new WeakMap();

function formDari(target) {
    if (!(target instanceof Element)) return null;
    return target.closest('form[data-auto-filter]');
}

function formAman(form) {
    if (!form.hasAttribute('data-auto-filter-valid')) return true;

    const cek = window.Validasi && window.Validasi.cek;
    if (typeof cek !== 'function') return true;

    return !Array.from(form.querySelectorAll(ATURAN)).some((kolom) => cek(kolom) !== '');
}

function bersihkan(form) {
    const lama = penunda.get(form);
    if (lama !== null && lama !== undefined) {
        window.clearTimeout(lama);
        penunda.delete(form);
    }
}

function kunciFokus(el) {
    if (!el) return '';
    return el.dataset.autoFilterFocus || el.name || el.id || '';
}

function simpanFokus(form, target) {
    let el = document.activeElement;
    if (!el || el === document.body || !form.contains(el)) {
        el = target && form.contains(target) ? target : null;
    }

    const kunci = kunciFokus(el);
    if (!kunci) return;

    try {
        sessionStorage.setItem(FOKUS, JSON.stringify({
            kunci,
            mulai: typeof el.selectionStart === 'number' ? el.selectionStart : null,
            akhir: typeof el.selectionEnd === 'number' ? el.selectionEnd : null,
        }));
    } catch (_) {
        // sessionStorage bisa diblokir; filter tetap berjalan tanpa pemulihan fokus.
    }
}

function pulihkanFokus() {
    let data;
    try {
        data = JSON.parse(sessionStorage.getItem(FOKUS) || 'null');
        sessionStorage.removeItem(FOKUS);
    } catch (_) {
        return;
    }
    if (!data || !data.kunci) return;

    const kunci = String(data.kunci);
    let target = Array.from(document.querySelectorAll('form[data-auto-filter] [data-auto-filter-focus]'))
        .find((el) => el.dataset.autoFilterFocus === kunci);
    if (!target) {
        target = Array.from(document.querySelectorAll('form[data-auto-filter] [name]'))
            .find((el) => el.name === kunci);
    }
    if (!target) target = document.getElementById(kunci);
    if (!target) return;

    try { target.focus({ preventScroll: true }); } catch (_) { target.focus(); }
    if (data.mulai !== null && data.akhir !== null && /^(text|search|url|tel|password)$/.test(target.type || '')) {
        try { target.setSelectionRange(data.mulai, data.akhir); } catch (_) { /* abaikan */ }
    }
}

function kirim(form, paksa = false, target = null) {
    if (!paksa && !formAman(form)) return;
    simpanFokus(form, target);
    if (typeof form.requestSubmit === 'function') form.requestSubmit();
    else form.submit();
}

document.addEventListener('change', (event) => {
    const form = formDari(event.target);
    if (!form || !event.target.matches(LANGSUNG)) return;

    bersihkan(form);
    kirim(form, false, event.target);
});

document.addEventListener('input', (event) => {
    const form = formDari(event.target);
    if (!form || !event.target.matches(TEKS)) return;
    if (event.target.readOnly || event.target.disabled) return;

    bersihkan(form);
    penunda.set(form, window.setTimeout(() => {
        penunda.delete(form);
        kirim(form, false, event.target);
    }, JEDA));
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;

    const form = formDari(event.target);
    if (!form || !event.target.matches(TEKS)) return;

    event.preventDefault();
    bersihkan(form);
    kirim(form, true, event.target);
});

window.addEventListener('pagehide', () => {
    penunda.forEach((timer, form) => {
        window.clearTimeout(timer);
        penunda.delete(form);
    });
});

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', pulihkanFokus);
else pulihkanFokus();
