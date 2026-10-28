{{-- Satu input form referensi. Variabel: $f (definisi field), $nilai, $opsi, $edit (bool) --}}
@if ($f['type'] === 'bool')
    <label class="flex items-center gap-2 text-sm pb-2">
        <input type="hidden" name="{{ $f['name'] }}" value="0">
        <input type="checkbox" name="{{ $f['name'] }}" value="1" class="size-4 accent-flame" @checked($nilai)>
        {{ $f['label'] }}
    </label>
@else
    <div>
        <label class="label">{{ $f['label'] }}</label>
        @if ($edit && !empty($f['kunci']))
            <input class="input" value="{{ $nilai }}" disabled>
        @elseif ($f['type'] === 'select')
            <select class="input" name="{{ $f['name'] }}">
                <option value="">-</option>
                @foreach ($opsi[$f['name']] ?? [] as $v => $l)
                    <option value="{{ $v }}" @selected((string) $nilai === (string) $v)>{{ $l }}</option>
                @endforeach
            </select>
        @elseif ($f['type'] === 'textarea')
            <textarea class="input" rows="2" name="{{ $f['name'] }}" maxlength="1000">{{ $nilai }}</textarea>
        @elseif ($f['type'] === 'int')
            <input class="input" type="number" min="1" name="{{ $f['name'] }}" value="{{ $nilai }}">
        @else
            <input class="input" name="{{ $f['name'] }}" value="{{ $nilai }}" maxlength="{{ $f['max'] ?? 255 }}">
        @endif
    </div>
@endif
