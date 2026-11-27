@php
    $daftarHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];
    $rowHari = (int) ($row['hari'] ?? 1);
    $rowMulai = $row['jam_mulai'] ?? '';
    $rowSelesai = $row['jam_selesai'] ?? '';
    $rowMapel = (string) ($row['id_mata_pelajaran'] ?? '');
    $rowGuruNama = (string) ($row['nama_guru_mapel'] ?? $row['nama_guru'] ?? '');
    $rowId = $row['id_jadwal'] ?? '';
    $prefix = 'jadwal[' . $index . ']';
@endphp
<tr class="border-b border-line" data-jadwal-row>
    <td class="p-2 align-top">
        <input type="hidden" name="{{ $prefix }}[id_jadwal]" value="{{ $rowId }}">
        <select class="input !py-1.5 !text-xs" name="{{ $prefix }}[hari]" aria-label="Hari">
            @foreach ($daftarHari as $nilai => $label)
                <option value="{{ $nilai }}" @selected($rowHari === $nilai)>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td class="p-2 align-top">
        <input type="time" class="input !py-1.5 !text-xs" name="{{ $prefix }}[jam_mulai]" value="{{ $rowMulai }}" aria-label="Jam mulai">
    </td>
    <td class="p-2 align-top">
        <input type="time" class="input !py-1.5 !text-xs" name="{{ $prefix }}[jam_selesai]" value="{{ $rowSelesai }}" aria-label="Jam selesai">
    </td>
    <td class="p-2 align-top">
        <select class="input !py-1.5 !text-xs min-w-[10rem]" name="{{ $prefix }}[id_mata_pelajaran]" aria-label="Mata pelajaran">
            <option value="">— mata pelajaran —</option>
            @foreach ($mapel as $m)
                <option value="{{ $m['id'] }}" @selected($rowMapel === (string) $m['id'])>{{ $m['nama'] }}</option>
            @endforeach
        </select>
    </td>
    <td class="p-2 align-top">
        <input type="text" class="input !py-1.5 !text-xs min-w-[10rem]" name="{{ $prefix }}[nama_guru_mapel]" value="{{ $rowGuruNama }}" placeholder="Nama guru mata pelajaran" aria-label="Guru Mata Pelajaran">
    </td>
    <td class="p-2 align-top text-right">
        <button type="button" class="btn btn-ghost !py-1.5 !text-bad js-hapus-baris">Hapus</button>
    </td>
</tr>
