// Dropdown kustom: mengubah <select class="input"> jadi daftar pilihan yang rapi (desktop saja).
// HP/tablet tetap memakai pemilih bawaan perangkat, yang memang paling nyaman untuk layar sentuh.
// <select> aslinya tetap ada (tersembunyi) sehingga data form dan validasi tidak berubah.
// Lewati satu kotak dengan atribut data-native (mis. tabel jadwal yang berisi puluhan kotak).

const DESKTOP = '(hover: hover) and (pointer: fine)';
const PANAH = '<svg class="w-4 h-4 shrink-0 text-mute" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>';
const CEKLIS = '<svg class="w-4 h-4 shrink-0 text-flame" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';

let tutupYangTerbuka = null;

function pasang(sel) {
    if (sel._dd) return;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = sel.className
        + ' dd-btn flex items-center justify-between gap-2 text-left cursor-pointer'
        + ' disabled:bg-paper disabled:text-mute/70 disabled:cursor-not-allowed';
    btn.setAttribute('aria-haspopup', 'listbox');
    btn.setAttribute('aria-expanded', 'false');
    const teks = document.createElement('span');
    teks.className = 'truncate';
    btn.appendChild(teks);
    btn.insertAdjacentHTML('beforeend', PANAH);

    sel.classList.add('sr-only');
    sel.tabIndex = -1;
    sel.setAttribute('aria-hidden', 'true');
    sel.insertAdjacentElement('afterend', btn);
    sel._dd = btn; // dipakai validasi.js untuk memberi garis merah pada tombol

    function segarkan() {
        const o = sel.options[sel.selectedIndex];
        teks.textContent = o ? o.textContent : '';
        teks.classList.toggle('text-mute/70', !sel.value);
        btn.disabled = sel.disabled;
    }
    segarkan();
    new MutationObserver(segarkan).observe(sel, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] });
    sel.addEventListener('focus', () => btn.focus()); // klik label -> fokus ke tombol
    sel.addEventListener('change', segarkan);

    function buka() {
        if (btn.disabled) return;
        if (tutupYangTerbuka) tutupYangTerbuka();

        const opsi = Array.from(sel.options);
        const panel = document.createElement('div');
        panel.className = 'dd-panel fixed z-[70] rounded-xl border border-line bg-white shadow-xl shadow-navy/15 overflow-hidden';

        let cari = null;
        if (opsi.length > 8) {
            const w = document.createElement('div');
            w.className = 'p-2 border-b border-line';
            cari = document.createElement('input');
            cari.type = 'search';
            cari.placeholder = 'Cari…';
            cari.className = 'input !py-2 !text-sm';
            w.appendChild(cari);
            panel.appendChild(w);
        }
        const ul = document.createElement('ul');
        ul.className = 'max-h-60 overflow-y-auto p-1';
        ul.setAttribute('role', 'listbox');
        panel.appendChild(ul);

        let daftar = [], aktif = -1;

        function sorot(i) {
            const li = ul.children;
            if (aktif >= 0 && li[aktif]) li[aktif].classList.remove('bg-tint');
            aktif = i;
            if (li[aktif]) { li[aktif].classList.add('bg-tint'); li[aktif].scrollIntoView({ block: 'nearest' }); }
        }
        function pilih(o) {
            sel.value = o.value;
            sel.dispatchEvent(new Event('input', { bubbles: true }));
            sel.dispatchEvent(new Event('change', { bubbles: true }));
            tutup();
            btn.focus();
        }
        function gambar(kata) {
            ul.innerHTML = '';
            daftar = opsi.filter((o) => !kata || o.textContent.toLowerCase().includes(kata));
            if (!daftar.length) {
                ul.innerHTML = '<li class="px-3 py-2 text-sm text-mute">Tidak ditemukan</li>';
                aktif = -1;
                return;
            }
            daftar.forEach((o, i) => {
                const li = document.createElement('li');
                const terpilih = o === sel.options[sel.selectedIndex];
                li.setAttribute('role', 'option');
                li.className = 'flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-sm cursor-pointer '
                    + (!o.value ? 'text-mute ' : '') + (terpilih ? 'font-semibold text-flame ' : '');
                const s = document.createElement('span');
                s.className = 'truncate';
                s.textContent = o.textContent;
                li.appendChild(s);
                if (terpilih) li.insertAdjacentHTML('beforeend', CEKLIS);
                li.addEventListener('mousemove', () => { if (aktif !== i) sorot(i); });
                li.addEventListener('click', () => pilih(o));
                ul.appendChild(li);
            });
            const sekarang = daftar.indexOf(sel.options[sel.selectedIndex]);
            sorot(sekarang >= 0 ? sekarang : 0);
        }
        function posisi() {
            const r = btn.getBoundingClientRect();
            const bawah = window.innerHeight - r.bottom - 16, atas = r.top - 16;
            const kotakCari = cari ? 56 : 0;
            // Utamakan membuka ke bawah. Ke atas hanya kalau ruang bawah sempit sekali (< 160px).
            const keAtas = bawah < 160 && atas > bawah;
            ul.style.maxHeight = Math.min(240, Math.max(120, (keAtas ? atas : bawah) - kotakCari)) + 'px';
            panel.style.left = r.left + 'px';
            panel.style.width = Math.max(r.width, 180) + 'px';
            if (keAtas) {
                panel.style.top = '';
                panel.style.bottom = (window.innerHeight - r.top + 6) + 'px';
            } else {
                panel.style.bottom = '';
                panel.style.top = (r.bottom + 6) + 'px';
            }
        }
        function onKey(e) {
            if (e.key === 'Escape') { e.preventDefault(); tutup(); btn.focus(); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); if (daftar.length) sorot(Math.min(aktif + 1, daftar.length - 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); if (daftar.length) sorot(Math.max(aktif - 1, 0)); }
            else if (e.key === 'Home' && !cari) { e.preventDefault(); sorot(0); }
            else if (e.key === 'End' && !cari) { e.preventDefault(); sorot(daftar.length - 1); }
            else if (e.key === 'Enter') { e.preventDefault(); if (daftar[aktif]) pilih(daftar[aktif]); }
            else if (e.key === 'Tab') { tutup(); }
        }
        function luar(e) { if (!panel.contains(e.target) && !btn.contains(e.target)) tutup(); }
        function gulir(e) { if (!panel.contains(e.target)) tutup(); }

        function tutup() {
            panel.remove();
            document.removeEventListener('keydown', onKey, true);
            document.removeEventListener('mousedown', luar, true);
            window.removeEventListener('scroll', gulir, true);
            window.removeEventListener('resize', tutup);
            btn.setAttribute('aria-expanded', 'false');
            tutupYangTerbuka = null;
        }

        document.body.appendChild(panel);
        gambar('');
        posisi();
        btn.setAttribute('aria-expanded', 'true');
        document.addEventListener('keydown', onKey, true);
        document.addEventListener('mousedown', luar, true);
        window.addEventListener('scroll', gulir, true);
        window.addEventListener('resize', tutup);
        tutupYangTerbuka = tutup;
        if (cari) { cari.addEventListener('input', () => gambar(cari.value.trim().toLowerCase())); cari.focus(); }
    }

    btn.addEventListener('click', () => (tutupYangTerbuka && btn.getAttribute('aria-expanded') === 'true' ? tutupYangTerbuka() : buka()));
    btn.addEventListener('keydown', (e) => {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) { e.preventDefault(); buka(); }
    });
}

function mulai() {
    if (!(window.matchMedia && window.matchMedia(DESKTOP).matches)) return;
    document.querySelectorAll('select.input:not([data-native])').forEach(pasang);
}
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mulai); else mulai();
}