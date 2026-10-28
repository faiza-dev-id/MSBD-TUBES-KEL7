{{-- Satu input form konfigurasi. Variabel: $f (definisi field), $nilai, $opsi, $edit (bool) --}}
@if ($f['type'] === 'bool')
    <label class="flex items-center gap-2 text-sm pb-2">
        <input type="hidden" name="{{ $f['name'] }}" value="0">
        <input type="checkbox" name="{{ $f['name'] }}" value="1" class="size-4 accent-flame" @checked($nilai)>
        {{ $f['label'] }}
    </label>
@else
    <div>
        <label class="label">{{ $f['label'] }}</label>
        @if ($f['type'] === 'select')
            @if ($edit && !empty($f['kunci']))
                <input class="input" value="{{ $opsi[$f['name']][$nilai] ?? '-' }}" disabled>
            @else
                <select class="input" name="{{ $f['name'] }}">
                    <option value="">-</option>
                    @foreach ($opsi[$f['name']] ?? [] as $v => $l)
                        <option value="{{ $v }}" @selected((string) $nilai === (string) $v)>{{ $l }}</option>
                    @endforeach
                </select>
            @endif
        @elseif ($f['type'] === 'int')
            <input class="input" type="number" min="{{ $f['min'] ?? 1 }}" max="{{ $f['maks'] ?? 1000 }}" name="{{ $f['name'] }}" value="{{ $nilai }}">
        @elseif ($f['type'] === 'num')
            <input class="input" type="number" step="0.01" min="0" max="100" name="{{ $f['name'] }}" value="{{ $nilai }}">
        @elseif ($f['type'] === 'date')
            <input class="input" type="date" name="{{ $f['name'] }}" value="{{ $nilai }}">
        @else
            <input class="input" name="{{ $f['name'] }}" value="{{ $nilai }}" maxlength="{{ $f['max'] ?? 255 }}">
        @endif
    </div>
@endif
