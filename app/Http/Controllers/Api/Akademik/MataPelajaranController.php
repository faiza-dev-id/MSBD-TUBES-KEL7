<?php

namespace App\Http\Controllers\Api\Akademik;

use App\Http\Controllers\Controller;
use App\Models\MataPelajaran;
use App\Services\MasterAkademikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** DFD 2.1.3 Kelola Mata Pelajaran ("Kode mapel (cek duplikat)") */
class MataPelajaranController extends Controller
{
    public function __construct(private MasterAkademikService $svc) {}

    public function index(Request $r): JsonResponse
    {
        $cari = trim((string) $r->query('cari', ''));

        return response()->json(
            MataPelajaran::query()
                ->when($cari !== '', fn ($q) => $q->where('nama_mapel', 'ilike', '%'.addcslashes($cari, '%_\\').'%'))
                ->orderBy('nama_mapel')->paginate(20)
        );
    }

    public function store(Request $r): JsonResponse
    {
        return response()->json(MataPelajaran::create($this->aturan($r)), 201);
    }

    public function update(Request $r, MataPelajaran $mataPelajaran): JsonResponse
    {
        $mataPelajaran->update($this->aturan($r, $mataPelajaran));

        return response()->json($mataPelajaran->fresh());
    }

    public function destroy(MataPelajaran $mataPelajaran): JsonResponse
    {
        if ($alasan = $this->svc->alasanTidakBisaDihapus('mapel', $mataPelajaran->id_mata_pelajaran)) {
            return response()->json(['message' => $alasan], 409);
        }
        $mataPelajaran->delete();

        return response()->json(['message' => 'Mata pelajaran dihapus']);
    }

    private function aturan(Request $r, ?MataPelajaran $m = null): array
    {
        $ignore = fn ($rule) => $rule->ignore($m?->id_mata_pelajaran, 'id_mata_pelajaran');

        return $r->validate([
            'kode_mapel' => ['nullable', 'string', 'max:30', 'alpha_dash', $ignore(Rule::unique('mata_pelajaran', 'kode_mapel'))],
            'nama_mapel' => ['required', 'string', 'max:150', $ignore(Rule::unique('mata_pelajaran', 'nama_mapel'))],
            'is_aktif'   => 'sometimes|boolean',
        ]);
    }
}
