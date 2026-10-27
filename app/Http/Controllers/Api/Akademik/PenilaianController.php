<?php

namespace App\Http\Controllers\Api\Akademik;

use App\Http\Controllers\Controller;
use App\Models\KomponenPenilaian;
use App\Models\SkalaPredikat;
use App\Services\MasterAkademikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** DFD 2.1.4 Kelola Komponen & Skala Nilai (D29) */
class PenilaianController extends Controller
{
    public function __construct(private MasterAkademikService $svc) {}

    /* ---- komponen_penilaian ---- */

    public function indexKomponen(): JsonResponse
    {
        return response()->json([
            'data'    => KomponenPenilaian::orderBy('id_komponen')->get(),
            'ringkas' => $this->svc->ringkasanBobot(), // total_bobot & lengkap (=100%)
        ]);
    }

    public function storeKomponen(Request $r): JsonResponse
    {
        return response()->json($this->svc->simpanKomponen($this->aturanKomponen($r)), 201);
    }

    public function updateKomponen(Request $r, KomponenPenilaian $komponen): JsonResponse
    {
        return response()->json($this->svc->simpanKomponen($this->aturanKomponen($r, $komponen), $komponen));
    }

    public function destroyKomponen(KomponenPenilaian $komponen): JsonResponse
    {
        if ($alasan = $this->svc->alasanTidakBisaDihapus('komponen', $komponen->id_komponen)) {
            return response()->json(['message' => $alasan], 409);
        }
        $komponen->delete();

        return response()->json(['message' => 'Komponen dihapus']);
    }

    /* ---- skala_predikat ---- */

    public function indexPredikat(): JsonResponse
    {
        return response()->json(SkalaPredikat::orderByDesc('nilai_min')->get());
    }

    public function storePredikat(Request $r): JsonResponse
    {
        return response()->json($this->svc->simpanPredikat($this->aturanPredikat($r)), 201);
    }

    public function updatePredikat(Request $r, SkalaPredikat $predikat): JsonResponse
    {
        return response()->json($this->svc->simpanPredikat($this->aturanPredikat($r, $predikat), $predikat));
    }

    public function destroyPredikat(SkalaPredikat $predikat): JsonResponse
    {
        if ($alasan = $this->svc->alasanTidakBisaDihapus('predikat', $predikat->id_predikat)) {
            return response()->json(['message' => $alasan], 409);
        }
        $predikat->delete();

        return response()->json(['message' => 'Predikat dihapus']);
    }

    private function aturanKomponen(Request $r, ?KomponenPenilaian $k = null): array
    {
        return $r->validate([
            'nama_komponen' => ['required', 'string', 'max:120',
                Rule::unique('komponen_penilaian', 'nama_komponen')->ignore($k?->id_komponen, 'id_komponen')],
            'bobot_persen' => 'required|numeric|min:0|max:100',
        ]);
    }

    private function aturanPredikat(Request $r, ?SkalaPredikat $p = null): array
    {
        return $r->validate([
            'huruf' => ['required', 'string', 'max:5',
                Rule::unique('skala_predikat', 'huruf')->ignore($p?->id_predikat, 'id_predikat')],
            'nilai_min'  => 'required|numeric|min:0|max:100',
            'nilai_maks' => 'required|numeric|min:0|max:100|gte:nilai_min',
            'deskripsi'  => 'nullable|string|max:255',
        ]);
    }
}
