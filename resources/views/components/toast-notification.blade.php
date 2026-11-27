@php
    $initialToasts = [];

    foreach (['success', 'sukses'] as $key) {
        if (session()->has($key) && is_scalar(session($key)) && trim((string) session($key)) !== '') {
            $initialToasts[] = [
                'type' => 'success',
                'message' => trim((string) session($key)),
            ];
        }
    }

    foreach (['error', 'galat'] as $key) {
        if (session()->has($key) && is_scalar(session($key)) && trim((string) session($key)) !== '') {
            $initialToasts[] = [
                'type' => 'error',
                'message' => trim((string) session($key)),
            ];
        }
    }

    foreach (['warning', 'peringatan'] as $key) {
        if (session()->has($key) && is_scalar(session($key)) && trim((string) session($key)) !== '') {
            $initialToasts[] = [
                'type' => 'warning',
                'message' => trim((string) session($key)),
            ];
        }
    }

    foreach (['info', 'status'] as $key) {
        if (session()->has($key) && is_scalar(session($key)) && trim((string) session($key)) !== '') {
            $initialToasts[] = [
                'type' => 'info',
                'message' => trim((string) session($key)),
            ];
        }
    }

    if (isset($errors) && $errors->any()) {
        $initialToasts[] = [
            'type' => 'warning',
            'message' => 'Mohon periksa kembali isian formulir yang ditandai merah.',
        ];
    }
@endphp

@once
    <style>
        @keyframes siakad-toast-progress {
            from {
                transform: scaleX(1);
            }
            to {
                transform: scaleX(0);
            }
        }
    </style>

    <script>
        window.siakadToastNotification = function () {
            const DEFAULT_DURATION = 5000;
            const CONFIG = {
                success: {
                    title: 'Berhasil',
                    cardBorder: 'border-l-emerald-500',
                    iconWrap: 'bg-emerald-50 text-emerald-600',
                    progressBar: 'bg-emerald-500',
                },
                error: {
                    title: 'Terjadi Kesalahan',
                    cardBorder: 'border-l-rose-500',
                    iconWrap: 'bg-rose-50 text-rose-600',
                    progressBar: 'bg-rose-500',
                },
                warning: {
                    title: 'Perhatian',
                    cardBorder: 'border-l-amber-500',
                    iconWrap: 'bg-amber-50 text-amber-600',
                    progressBar: 'bg-amber-500',
                },
                info: {
                    title: 'Informasi',
                    cardBorder: 'border-l-blue-500',
                    iconWrap: 'bg-blue-50 text-blue-600',
                    progressBar: 'bg-blue-500',
                },
            };

            const normalizeType = (raw) => {
                const value = String(raw || 'info').toLowerCase().trim();
                if (['success', 'sukses', 'ok'].includes(value)) return 'success';
                if (['error', 'galat', 'bad', 'danger'].includes(value)) return 'error';
                if (['warning', 'peringatan', 'warn'].includes(value)) return 'warning';
                return 'info';
            };

            return {
                toasts: [],
                nextId: 1,

                init() {
                    let initial = [];
                    try {
                        const raw = this.$refs.initialToasts ? this.$refs.initialToasts.textContent : '[]';
                        initial = JSON.parse(raw || '[]');
                    } catch (e) {
                        initial = [];
                    }

                    if (Array.isArray(initial)) {
                        initial.forEach((item, index) => {
                            window.setTimeout(() => {
                                this.push(item);
                            }, index * 90);
                        });
                    }
                },

                handleEvent(detail) {
                    if (!detail) return;
                    if (typeof detail === 'string') {
                        this.push({ type: 'info', message: detail });
                        return;
                    }
                    if (Array.isArray(detail)) {
                        detail.forEach((item) => this.handleEvent(item));
                        return;
                    }
                    this.push(detail);
                },

                push(payload) {
                    if (!payload) return;
                    const message = String(payload.message ?? payload.pesan ?? '').trim();
                    if (!message) return;

                    const type = normalizeType(payload.type ?? payload.jenis);
                    const meta = CONFIG[type] || CONFIG.info;
                    const duration = Number(payload.duration) > 0 ? Number(payload.duration) : DEFAULT_DURATION;
                    const id = this.nextId++;

                    this.toasts.push({
                        id,
                        type,
                        title: String(payload.title ?? payload.judul ?? meta.title),
                        message,
                        duration,
                        remaining: duration,
                        startedAt: 0,
                        paused: false,
                        visible: false,
                        timer: null,
                    });

                    this.$nextTick(() => {
                        const current = this.toasts.find((item) => item.id === id);
                        if (!current) return;
                        current.visible = true;
                        this.startTimer(current);
                    });
                },

                startTimer(toast) {
                    if (!toast) return;
                    if (toast.timer) {
                        window.clearTimeout(toast.timer);
                    }
                    toast.startedAt = performance.now();
                    toast.timer = window.setTimeout(() => {
                        this.dismiss(toast.id);
                    }, toast.remaining);
                },

                pause(id) {
                    const toast = this.toasts.find((item) => item.id === id);
                    if (!toast || toast.paused || !toast.visible) return;

                    if (toast.timer) {
                        window.clearTimeout(toast.timer);
                        toast.timer = null;
                    }

                    const elapsed = performance.now() - toast.startedAt;
                    toast.remaining = Math.max(0, toast.remaining - elapsed);
                    toast.paused = true;
                },

                resume(id) {
                    const toast = this.toasts.find((item) => item.id === id);
                    if (!toast || !toast.paused || !toast.visible) return;

                    toast.paused = false;
                    if (toast.remaining <= 0) {
                        this.dismiss(toast.id);
                        return;
                    }
                    this.startTimer(toast);
                },

                dismiss(id) {
                    const toast = this.toasts.find((item) => item.id === id);
                    if (!toast) return;

                    if (toast.timer) {
                        window.clearTimeout(toast.timer);
                        toast.timer = null;
                    }

                    toast.visible = false;

                    window.setTimeout(() => {
                        this.toasts = this.toasts.filter((item) => item.id !== id);
                    }, 220);
                },

                cardBorderClass(type) {
                    return (CONFIG[type] || CONFIG.info).cardBorder;
                },

                iconWrapClass(type) {
                    return (CONFIG[type] || CONFIG.info).iconWrap;
                },

                progressBarClass(type) {
                    return (CONFIG[type] || CONFIG.info).progressBar;
                },
            };
        };
    </script>
@endonce

<div
    x-data="window.siakadToastNotification()"
    x-cloak
    @toast.window="handleEvent($event.detail)"
    aria-live="polite"
    aria-atomic="false"
    class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 w-[calc(100vw-2.5rem)] sm:w-full max-w-sm pointer-events-none"
>
    <script type="application/json" x-ref="initialToasts">{!! json_encode($initialToasts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>

    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-6 -translate-y-1 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-6 scale-95"
            @mouseenter="pause(toast.id)"
            @mouseleave="resume(toast.id)"
            :role="toast.type === 'error' ? 'alert' : 'status'"
            :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
            :class="cardBorderClass(toast.type)"
            class="pointer-events-auto relative bg-white/95 backdrop-blur-sm border border-slate-200/80 border-l-4 shadow-lg rounded-xl overflow-hidden"
        >
            <div class="p-4 flex items-start gap-3">
                <div
                    :class="iconWrapClass(toast.type)"
                    class="shrink-0 flex items-center justify-center w-9 h-9 rounded-full"
                    aria-hidden="true"
                >
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                    </template>

                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9" />
                            <path d="m15 9-6 6" />
                            <path d="m9 9 6 6" />
                        </svg>
                    </template>

                    <template x-if="toast.type === 'warning'">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                            <path d="M12 9v4" />
                            <path d="M12 17h.01" />
                        </svg>
                    </template>

                    <template x-if="toast.type === 'info'">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 16v-4" />
                            <path d="M12 8h.01" />
                        </svg>
                    </template>
                </div>

                <div class="flex-1 min-w-0 pt-0.5">
                    <p class="text-sm font-semibold text-slate-900 leading-snug" x-text="toast.title"></p>
                    <p class="mt-0.5 text-sm text-slate-600 leading-relaxed break-words" x-text="toast.message"></p>
                </div>

                <button
                    type="button"
                    @click="dismiss(toast.id)"
                    aria-label="Tutup notifikasi"
                    class="shrink-0 -mt-1 -mr-1 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <div class="h-1 w-full bg-slate-100/90 overflow-hidden" aria-hidden="true">
                <div
                    :class="progressBarClass(toast.type)"
                    class="h-full w-full origin-left"
                    :style="{
                        animation: 'siakad-toast-progress ' + toast.duration + 'ms linear forwards',
                        animationPlayState: toast.paused ? 'paused' : 'running'
                    }"
                ></div>
            </div>
        </div>
    </template>
</div>
