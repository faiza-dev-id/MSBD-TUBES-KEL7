<?php

namespace App\Http\Controllers\Api\Akademik;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Services\MasterAkademikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** DFD 2.1.2 Kelola Kelas */
class KelasController extends Controller
{
    public function __construct(private MasterAkademikService $svc) {}

    public function index(Request $r): JsonResponse
    {
        $q = Kelas::with('tingkat')
            ->when($r->filled('id_tahun_ajaran'), fn ($q) => $q->where('id_tahun_ajaran', (int) $r->id_tahun_ajaran))
            ->when($r->filled('id_tingkat'), fn ($q) => $q->where('id_tingkat', (int) $r->id_tingkat))
            ->orderBy('id_tingkat')->orderBy('nama_kelas');

        return response()->json($q->paginate(20));
    }

    public function store(Request $r): JsonResponse
    {
        return response()->json(Kelas::create($this->aturan($r)), 201);
    }

    public function update(Request $r, Kelas $kelas): JsonResponse
    {
        $kelas->update($this->aturan($r, $kelas));

        return response()->json($kelas->fresh());
    }

    public function destroy(Kelas $kelas): JsonResponse
    {
        if ($alasan = $this->svc->alasanTidakBisaDihapus('kelas', $kelas->id_kelas)) {
            return response()->json(['message' => $alasan], 409);
        }
        $kelas->delete();

        return response()->json(['message' => 'Kelas dihapus']);
    }

    private function aturan(Request $r, ?Kelas $k = null): array
    {
        return $r->validate([
            'id_tingkat'      => 'required|integer|exists:tingkat,id_tingkat',
            'id_tahun_ajaran' => 'nullable|integer|exists:tahun_ajaran,id_tahun_ajaran',
            'nama_kelas'      => [
                'required', 'string', 'max:80',
                // DB: UNIQUE (id_tingkat, nama_kelas)
                Rule::unique('kelas', 'nama_kelas')
                    ->where('id_tingkat', $r->input('id_tingkat'))
                    ->ignore($k?->id_kelas, 'id_kelas'),
            ],
            'kapasitas' => 'nullable|integer|min:1|max:100',
            'is_aktif'  => 'sometimes|boolean',
        ]);
    }
}
