<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use App\Services\PengaturanService;
use Illuminate\Http\Request;

/** DFD 1.4 Atur Pengaturan Sistem (Kepala Sekolah). Akses: role:kepsek di routes/web.php. */
class PengaturanController extends Controller
{
    public function __construct(private PengaturanService $svc) {}

    public function index()
    {
        return view('admin.pengaturan', [
            'set'         => $this->svc->untukForm(),
            'tahunAjaran' => TahunAjaran::orderByDesc('tanggal_mulai')->pluck('nama_tahun_ajaran'),
        ]);
    }

    public function update(Request $r)
    {
        $d = $r->validate([
            'nama_sekolah'       => 'required|string|max:120',
            'max_login_gagal'    => 'required|integer|between:3,10',
            'durasi_kunci'       => 'required|integer|between:1,1440',
            'ukuran_berkas_mb'   => 'required|integer|between:1,20',
            'format_berkas'      => ['required', 'string', 'max:100', function ($attr, $v, $fail) {
                $daftar = collect(explode(',', strtolower($v)))->map(fn ($x) => trim($x))->filter();
                if ($daftar->isEmpty() || $daftar->diff(PengaturanService::FORMAT_BOLEH)->isNotEmpty()) {
                    $fail('Format hanya boleh: '.implode(', ', PengaturanService::FORMAT_BOLEH).'.');
                }
            }],
            'tahun_ajaran_aktif' => 'required|exists:tahun_ajaran,nama_tahun_ajaran',
        ]);

        $this->svc->simpan($d, $r->session()->get('id_pengguna'));

        return redirect()->route('admin.pengaturan')->with('success', 'Pengaturan disimpan.');
    }
}
