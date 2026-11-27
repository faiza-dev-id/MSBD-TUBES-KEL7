<div
    x-data='{
        buka: false,
        url: "",
        unduhUrl: "",
        nama: "",
        jenis: "",
        tipe: "pdf",
        tersedia: true,
        bukaBerkas(data) {
            this.url = data.url || "";
            this.unduhUrl = data.unduhUrl || "";
            this.nama = data.nama || "Berkas";
            this.jenis = data.jenis || "Dokumen";
            this.tipe = data.tipe || (/\.pdf($|\?)/i.test(this.nama) ? "pdf" : "gambar");
            this.tersedia = data.tersedia !== false;
            this.buka = true;
        },
        tutup() {
            this.buka = false;
        }
    }'
    @preview-berkas.window="bukaBerkas($event.detail)"
    @tutup-preview-berkas.window="tutup()"
    @berkas-tidak-tersedia.window="tersedia = false"
    @keydown.escape.window="if (buka) tutup()"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', buka)"
    x-cloak
>
    <div x-show="buka"
         x-transition.opacity.duration.150ms
         @click.self="tutup()"
         data-lenis-prevent
         class="fixed inset-0 z-[90] flex items-center justify-center bg-navy/60 p-3 backdrop-blur-sm sm:p-6">
        <div role="dialog"
             aria-modal="true"
             aria-label="Pratinjau berkas"
             data-lenis-prevent
             class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
                <div class="min-w-0">
                    <h2 class="font-serif text-base font-bold text-navy sm:text-lg">Pratinjau berkas</h2>
                    <p class="mt-0.5 truncate text-xs text-mute">
                        <span x-text="jenis"></span> · <span x-text="nama"></span>
                    </p>
                </div>
                <button type="button" @click="tutup()" aria-label="Tutup pratinjau"
                        class="rounded-lg p-1.5 text-mute hover:bg-tint hover:text-navy cursor-pointer">
                    <x-icon name="close" size="w-5 h-5" />
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-auto bg-paper p-3 sm:p-4" data-lenis-prevent>
                <template x-if="tipe === 'pdf'">
                    <iframe :src="buka ? url : 'about:blank'"
                            class="h-[60vh] min-h-[320px] w-full rounded-lg border border-line bg-white"
                            title="Pratinjau berkas PDF"></iframe>
                </template>
                <template x-if="tipe !== 'pdf'">
                    <div class="flex min-h-[320px] items-center justify-center">
                        <img :src="buka ? url : 'about:blank'"
                             :alt="'Pratinjau ' + nama"
                             class="max-h-[60vh] max-w-full rounded-lg object-contain shadow">
                    </div>
                </template>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-line px-4 py-3 sm:px-5">
                <button type="button" @click="tutup()" class="btn btn-ghost">Tutup</button>
                <a :href="unduhUrl" x-show="tersedia" class="btn btn-flame">Unduh Berkas</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('message', function (event) {
    if (event.origin !== window.location.origin) return;
    if (!event.data || typeof event.data !== 'object') return;

    if (event.data.type === 'tutup-preview-berkas') {
        window.dispatchEvent(new CustomEvent('tutup-preview-berkas'));
    } else if (event.data.type === 'berkas-tidak-tersedia') {
        window.dispatchEvent(new CustomEvent('berkas-tidak-tersedia'));
    }
});
</script>
