import Alpine from 'alpinejs';
import 'lenis/dist/lenis.css';
import Lenis from 'lenis';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { animate, inView, hover, stagger } from 'motion';
import './dropdown';
import './validasi';

window.Alpine = Alpine;
Alpine.start();

document.querySelectorAll('[data-form-pengajuan]').forEach((form) => {
    const field = form.querySelector('[data-pengajuan-field]');
    const proof = form.querySelector('[data-pengajuan-bukti]');
    const proofLabel = form.querySelector('[data-pengajuan-bukti-label]');
    const proofHint = form.querySelector('[data-pengajuan-bukti-hint]');

    if (!field || !proof || !proofLabel || !proofHint) return;

    const updateProofRequirement = () => {
        const required = field.selectedOptions[0]?.dataset.perluBukti === '1';

        proof.required = required;
        proofLabel.textContent = required
            ? 'Bukti pendukung (wajib)'
            : 'Bukti pendukung (opsional)';
        proofHint.textContent = required
            ? 'Perubahan data ini memerlukan bukti pendukung.'
            : 'Bukti pendukung tidak wajib untuk data yang dipilih.';
    };

    field.addEventListener('change', updateProofRequirement);
    updateProofRequirement();
});

document.querySelectorAll('[data-waktu-real-time]').forEach((clock) => {
    const tanggal = clock.querySelector('[data-jakarta-date]');
    const waktu = clock.querySelector('[data-jakarta-time]');
    if (!tanggal || !waktu) return;

    const formatTanggal = new Intl.DateTimeFormat('id-ID', {
        timeZone: 'Asia/Jakarta',
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
    const formatWaktu = new Intl.DateTimeFormat('id-ID', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    });

    const update = () => {
        const now = new Date();
        clock.dateTime = now.toISOString();
        const parts = formatWaktu.formatToParts(now);
        const jam = parts.find((part) => part.type === 'hour')?.value;
        const menit = parts.find((part) => part.type === 'minute')?.value;
        tanggal.textContent = formatTanggal.format(now);
        waktu.textContent = `${jam}.${menit}`;
    };

    update();
    window.setInterval(update, 30000);
});

const formatTanggalJakarta = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Jakarta',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
});
const formatLabelTanggalJadwal = new Intl.DateTimeFormat('id-ID', {
    timeZone: 'Asia/Jakarta',
    day: 'numeric',
    month: 'short',
});

document.querySelectorAll('[data-jadwal-picker]').forEach((picker) => {
    const tabs = Array.from(picker.querySelectorAll('[data-hari-tab]'));
    const panels = Array.from(picker.querySelectorAll('[data-hari-panel]'));
    if (tabs.length === 0) return;

    let pilihanManual = false;
    let hariTerakhir = null;

    const pilihHari = (nomorHari) => {
        const tabAktif = tabs.find((tab) => Number(tab.dataset.hariTab) === nomorHari) || tabs[0];
        const hariAktif = Number(tabAktif.dataset.hariTab);

        tabs.forEach((tab) => {
            const aktif = tab === tabAktif;
            const tanggal = tab.querySelector('[data-hari-tanggal]');

            tab.setAttribute('aria-selected', String(aktif));
            tab.tabIndex = aktif ? 0 : -1;
            tab.classList.toggle('border-flame', aktif);
            tab.classList.toggle('bg-flame', aktif);
            tab.classList.toggle('text-white', aktif);
            tab.classList.toggle('shadow-sm', aktif);
            tab.classList.toggle('border-line', !aktif);
            tab.classList.toggle('bg-white', !aktif);
            tab.classList.toggle('text-ink', !aktif);
            tab.classList.toggle('hover:bg-tint', !aktif);
            if (tanggal) {
                tanggal.classList.toggle('text-white/80', aktif);
                tanggal.classList.toggle('text-mute', !aktif);
            }
        });

        panels.forEach((panel) => {
            panel.hidden = Number(panel.dataset.hariPanel) !== hariAktif;
        });
    };

    const perbaruiTanggal = () => {
        const bagianTanggal = formatTanggalJakarta.formatToParts(new Date());
        const ambilBagian = (tipe) => Number(bagianTanggal.find((bagian) => bagian.type === tipe)?.value);
        const tanggalJakarta = new Date(Date.UTC(ambilBagian('year'), ambilBagian('month') - 1, ambilBagian('day'), 12));
        const hariIni = tanggalJakarta.getUTCDay() || 7;
        const seninMingguIni = new Date(tanggalJakarta);
        seninMingguIni.setUTCDate(tanggalJakarta.getUTCDate() - hariIni + 1);

        tabs.forEach((tab) => {
            const tanggalTab = new Date(seninMingguIni);
            tanggalTab.setUTCDate(seninMingguIni.getUTCDate() + Number(tab.dataset.hariTab) - 1);
            const waktu = tab.querySelector('[data-hari-tanggal]');

            if (waktu) {
                waktu.dateTime = tanggalTab.toISOString().slice(0, 10);
                waktu.textContent = formatLabelTanggalJadwal.format(tanggalTab);
            }
        });

        if (hariIni !== hariTerakhir) {
            hariTerakhir = hariIni;
            if (!pilihanManual) {
                const hariDefault = tabs.some((tab) => Number(tab.dataset.hariTab) === hariIni)
                    ? hariIni
                    : Number(picker.dataset.defaultDay) || Number(tabs[0].dataset.hariTab);
                pilihHari(hariDefault);
            }
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            pilihanManual = true;
            pilihHari(Number(tab.dataset.hariTab));
        });
    });

    perbaruiTanggal();
    window.setInterval(perbaruiTanggal, 60000);
});

document.querySelectorAll('[data-jadwal-mode-switch]').forEach((switcher) => {
    const tombolMode = Array.from(switcher.querySelectorAll('[data-jadwal-mode-button]'));
    const panelMode = Array.from(switcher.querySelectorAll('[data-jadwal-mode-panel]'));

    const pilihMode = (mode) => {
        tombolMode.forEach((tombol) => {
            const aktif = tombol.dataset.jadwalModeButton === mode;
            tombol.setAttribute('aria-pressed', String(aktif));
            tombol.classList.toggle('bg-flame', aktif);
            tombol.classList.toggle('text-white', aktif);
            tombol.classList.toggle('shadow-sm', aktif);
            tombol.classList.toggle('text-mute', !aktif);
            tombol.classList.toggle('hover:text-ink', !aktif);
        });

        panelMode.forEach((panel) => {
            panel.hidden = panel.dataset.jadwalModePanel !== mode;
        });
    };

    tombolMode.forEach((tombol) => {
        tombol.addEventListener('click', () => pilihMode(tombol.dataset.jadwalModeButton));
    });

    pilihMode('harian');
});

gsap.registerPlugin(ScrollTrigger);

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function markInternalScrollAreas() {
    document.querySelectorAll('[class*="overflow-"]').forEach((element) => {
        if (!(element instanceof HTMLElement)) return;

        const { overflowY } = window.getComputedStyle(element);
        const canScrollVertically = ['auto', 'scroll'].includes(overflowY)
            && element.scrollHeight > element.clientHeight;

        if (canScrollVertically) {
            element.setAttribute('data-lenis-prevent', '');
        }
    });

    document.querySelectorAll('dialog, [role="dialog"], [data-modal-body]').forEach((element) => {
        element.setAttribute('data-lenis-prevent', '');
    });
}

function initGsapTextAndScrollReveals() {
    document.querySelectorAll('[data-reveal-words]').forEach((heading) => {
        const walker = document.createTreeWalker(heading, NodeFilter.SHOW_TEXT);
        const textNodes = [];

        while (walker.nextNode()) {
            if (walker.currentNode.textContent.trim()) textNodes.push(walker.currentNode);
        }

        textNodes.forEach((textNode) => {
            const fragment = document.createDocumentFragment();

            textNode.textContent.split(/(\s+)/u).forEach((part) => {
                if (!part) return;
                if (/^\s+$/u.test(part)) {
                    fragment.append(document.createTextNode(part));
                    return;
                }

                const word = document.createElement('span');
                word.className = 'siakad-reveal-word';
                const content = document.createElement('span');
                content.textContent = part;
                word.append(content);
                fragment.append(word);
            });

            textNode.replaceWith(fragment);
        });

        const words = heading.querySelectorAll('.siakad-reveal-word > span');
        if (words.length === 0) return;

        gsap.fromTo(words, { y: 22, opacity: 0 }, {
            y: 0,
            opacity: 1,
            stagger: 0.045,
            duration: 0.6,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: heading,
                start: 'top 85%',
                once: true,
            },
        });
    });

    ScrollTrigger.batch('[data-reveal-up]', {
        start: 'top 88%',
        once: true,
        onEnter: (elements) => {
            gsap.fromTo(elements, { y: 24, opacity: 0 }, {
                y: 0,
                opacity: 1,
                stagger: 0.08,
                duration: 0.55,
                ease: 'power2.out',
            });
        },
    });
}

const initializedMotionCards = new WeakSet();

function initMotionCards() {
    const cards = Array.from(document.querySelectorAll('[data-motion-card]'));

    cards.forEach((card) => {
        if (initializedMotionCards.has(card)) return;
        initializedMotionCards.add(card);

        const siblingCards = Array.from(card.parentElement?.querySelectorAll('[data-motion-card]') ?? []);
        const cardIndex = siblingCards.indexOf(card);
        const delay = stagger(0.05)(cardIndex, siblingCards.length);

        inView(card, (element) => {
            animate(element, { opacity: [0, 1], y: [16, 0] }, {
                delay,
                duration: 0.4,
            });
        }, { once: true });

        hover(card, () => {
            animate(card, { y: -3 }, { duration: 0.2 });
            return () => animate(card, { y: 0 }, { duration: 0.2 });
        });
    });
}

window.refreshSiakadAnimations = () => {
    if (prefersReducedMotion) return;
    markInternalScrollAreas();
    initMotionCards();
    ScrollTrigger.refresh();
};

markInternalScrollAreas();

if (!prefersReducedMotion) {
    const lenis = new Lenis({ duration: 1.05, smoothWheel: true });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);

    initGsapTextAndScrollReveals();
    initMotionCards();
}