@props(['label', 'name', 'type' => 'text', 'hint' => null, 'placeholder' => '',
        'wajib' => false, 'digit' => null, 'hp' => false, 'min' => null, 'sandi' => false,
        'cocok' => null, 'beda' => null, 'setelah' => null, 'pola' => null, 'pesan' => null,
        'rentang' => null, 'autocomplete' => null])
<div>
    <label class="label" for="{{ $name }}">{{ $label }}</label>
    <input class="input" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" placeholder="{{ $placeholder }}" value="{{ old($name) }}"
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
    @if ($hint) <p class="text-[11px] text-mute mt-1">{{ $hint }}</p> @endif
</div>