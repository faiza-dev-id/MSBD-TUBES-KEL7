@props(['label', 'name', 'type' => 'text', 'hint' => null, 'placeholder' => '',
        'wajib' => false, 'digit' => null, 'hp' => false, 'min' => null, 'sandi' => false,
        'cocok' => null, 'beda' => null, 'setelah' => null, 'pola' => null, 'pesan' => null,
        'rentang' => null, 'autocomplete' => null])
@php $pw = $type === 'password'; @endphp
<div>
    <label class="label" for="{{ $name }}">{{ $label }}</label>
    <div class="{{ $pw ? 'relative' : '' }}">
        <input class="input {{ $pw ? 'pr-12' : '' }} @error($name) !border-bad @enderror" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ old($name) }}"
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($wajib) data-wajib @endif
            @if ($digit) data-digit="{{ $digit }}" inputmode="numeric" maxlength="{{ $digit }}" @endif
            @if ($hp) data-hp inputmode="tel" @endif
            @if ($min) data-min="{{ $min }}" @endif
            @if ($sandi) data-sandi @endif
            @if ($cocok) data-cocok="{{ $cocok }}" @endif
            @if ($beda) data-beda="{{ $beda }}" @endif
            @if ($setelah) data-setelah="{{ $setelah }}" @endif
            @if ($pola) data-pola="{{ $pola }}" @endif
            @if ($pesan) data-pesan="{{ $pesan }}" @endif
            @if ($rentang) data-rentang="{{ $rentang }}" inputmode="decimal" @endif>
        @if ($pw)
            <button type="button" aria-label="Tampilkan kata sandi"
                    class="absolute top-0 right-0 grid place-items-center w-12 h-[2.875rem] sm:h-[2.625rem] text-mute hover:text-flame cursor-pointer"
                    onclick="var i=this.parentNode.querySelector('input'),t=i.type==='password';i.type=t?'text':'password';this.setAttribute('aria-label',t?'Sembunyikan kata sandi':'Tampilkan kata sandi');this.querySelectorAll('svg').forEach(function(s){s.classList.toggle('hidden')})">
                {{-- mata terbuka: klik untuk menampilkan --}}
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                {{-- mata dicoret: klik untuk menyembunyikan --}}
                <svg class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        @endif
    </div>
    @if ($hint) <p class="text-[11px] text-mute mt-1">{{ $hint }}</p> @endif
</div>