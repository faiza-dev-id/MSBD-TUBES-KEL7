@props(['action', 'filter'])
<form method="GET" action="{{ $action }}" class="mb-4 grid gap-3 sm:grid-cols-2" data-auto-filter>
    <div class="min-w-0">
        <label class="label" for="filter-tingkat">Tingkat</label>
        <select class="input" id="filter-tingkat" name="tingkat">
            @foreach ($filter['opsi_tingkat'] as $opsi)
                <option value="{{ $opsi }}" @selected($filter['tingkat'] === $opsi)>{{ $opsi }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-0">
        <label class="label" for="filter-jurusan">Jurusan</label>
        <select class="input" id="filter-jurusan" name="jurusan">
            <option value="">Semua jurusan</option>
            @foreach ($filter['opsi_jurusan'] as $opsi)
                <option value="{{ $opsi }}" @selected($filter['jurusan'] === $opsi)>{{ $opsi }}</option>
            @endforeach
        </select>
    </div>
</form>
