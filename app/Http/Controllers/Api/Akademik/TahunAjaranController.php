<?php

namespace App\Http\Controllers\Api\Akademik;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\TahunAjaran;
use App\Services\MasterAkademikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** DFD 2.1.1 Kelola Tahun Ajaran & Semester */
class TahunAjaranController extends Controller
{
    public function __construct(private MasterAkademikService $svc) {}

    public function index(): JsonResponse
    {
        return response()->json(
            TahunAjaran::with('semester')->orderByDesc('tanggal_mulai')->get()
        );
    }

    public function store(Request $r): JsonResponse
    {
        return response()->json($this->svc->simpanTahunAjaran($this->aturanTa($r)), 201);
    }

    public function update(Request $r, TahunAjaran $tahunAjaran): JsonResponse
    {
        return response()->json($this->svc->simpanTahunAjaran($this->aturanTa($r, $tahunAjaran), $tahunAjaran));
    }

    public function destroy(TahunAjaran $tahunAjaran): JsonResponse
    {
        if ($alasan = $this->svc->alasanTidakBisaDihapus('tahun_ajaran', $tahunAjaran->id_tahun_ajaran)) {
            return response()->json(['message' => $alasan], 409);
        }
        $tahunAjaran->delete();

        return response()->json(['message' => 'Tahun ajaran dihapus']);
    }

    /* ---- semester (nested) ---- */

    public function storeSemester(Request $r, TahunAjaran $tahunAjaran): JsonResponse
    {
        return response()->json($this->svc->simpanSemester($tahunAjaran, $this->aturanSemester($r, $tahunAjaran)), 201);
    }

    public function updateSemester(Request $r, TahunAjaran $tahunAjaran, Semester $semester): JsonResponse
    {
        abort_unless($semester->id_tahun_ajaran === $tahunAjaran->id_tahun_ajaran, 404);

        return response()->json($this->svc->simpanSemester($tahunAjaran, $this->aturanSemester($r, $tahunAjaran, $semester), $semester));
    }

    public function destroySemester(TahunAjaran $tahunAjaran, Semester $semester): JsonResponse
    {
        abort_unless($semester->id_tahun_ajaran === $tahunAjaran->id_tahun_ajaran, 404);
        if ($alasan = $this->svc->alasanTidakBisaDihapus('semester', $semester->id_semester)) {
            return response()->json(['message' => $alasan], 409);
        }
        $semester->delete();

        return response()->json(['message' => 'Semester dihapus']);
    }

    private function aturanTa(Request $r, ?TahunAjaran $ta = null): array
    {
        return $r->validate([
            'nama_tahun_ajaran' => [
                'required', 'regex:/^\d{4}\/\d{4}$/', 'max:20',
                Rule::unique('tahun_ajaran', 'nama_tahun_ajaran')->ignore($ta?->id_tahun_ajaran, 'id_tahun_ajaran'),
                function ($attr, $v, $fail) {
                    [$a, $b] = array_map('intval', explode('/', $v));
                    if ($b !== $a + 1) $fail('Format harus tahun berurutan, mis. 2026/2027.');
                },
            ],
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'is_aktif'        => 'sometimes|boolean',
        ]);
    }

    private function aturanSemester(Request $r, TahunAjaran $ta, ?Semester $s = null): array
    {
        return $r->validate([
            'nama_semester' => [
                'required', 'string', 'max:30',
                Rule::unique('semester', 'nama_semester')
                    ->where('id_tahun_ajaran', $ta->id_tahun_ajaran)
                    ->ignore($s?->id_semester, 'id_semester'),
            ],
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after:tanggal_mulai',
            'is_aktif'        => 'sometimes|boolean',
        ]);
    }
}
