<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Support\AuditCatat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DFD 2.5 / 4.x: aturan data mana yang boleh diubah siswa dan mana yang wajib bukti (ref_field_data).
 * Daftar field tetap (terikat ke kolom di kode); yang bisa diatur hanya tiga hal per field.
 */
class AturanFieldController extends Controller
{
    public function index()
    {
        $field = DB::table('ref_field_data')->orderBy('nama_tabel')->orderBy('id_field')->get();
        $jenis = DB::table('jenis_dokumen')->orderBy('nama_dokumen')->pluck('nama_dokumen', 'id_jenis_dokumen');

        return view('admin.aturan-field', compact('field', 'jenis'));
    }

    public function update(Request $r)
    {
        $r->validate([
            'aturan'         => 'required|array',
            'aturan.*.jenis' => 'nullable|integer|exists:jenis_dokumen,id_jenis_dokumen',
        ]);

        $rows = DB::table('ref_field_data')->get()->keyBy('id_field');
        $perubahan = [];
        $galat = [];

        foreach ($r->input('aturan') as $id => $in) {
            $lama = $rows[$id] ?? null;
            if (!$lama || empty($in['ada'])) continue;

            $boleh = !empty($in['boleh']);
            $bukti = !empty($in['bukti']);
            $jenis = $bukti && !empty($in['jenis']) ? (int) $in['jenis'] : null;

            if ($bukti && $jenis === null) {
                $galat[] = 'Pilih jenis bukti untuk "'.$lama->label_field.'".';
                continue;
            }

            $baru = ['boleh_diubah_siswa' => $boleh, 'perlu_bukti' => $bukti, 'id_jenis_dokumen_bukti' => $jenis];
            $semula = [
                'boleh_diubah_siswa'     => (bool) $lama->boleh_diubah_siswa,
                'perlu_bukti'            => (bool) $lama->perlu_bukti,
                'id_jenis_dokumen_bukti' => $lama->id_jenis_dokumen_bukti !== null ? (int) $lama->id_jenis_dokumen_bukti : null,
            ];
            if ($baru !== $semula) $perubahan[(int) $id] = [$semula, $baru, $lama->label_field];
        }

        if ($galat) return back()->withInput()->withErrors(['umum' => implode(' ', $galat)]);

        DB::transaction(function () use ($perubahan) {
            foreach ($perubahan as $id => [$semula, $baru, $label]) {
                DB::table('ref_field_data')->where('id_field', $id)->update($baru);
                AuditCatat::tulis('ref_field_data', 'UPDATE', $id, ['label' => $label] + $semula, ['label' => $label] + $baru);
            }
        });

        return redirect()->route('admin.aturan')->with(
            'success',
            $perubahan ? count($perubahan).' aturan diperbarui.' : 'Tidak ada perubahan.'
        );
    }
}
