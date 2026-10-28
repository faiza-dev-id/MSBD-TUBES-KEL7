<?php

namespace App\Http\Controllers\Tu;

use App\Http\Controllers\Controller;
use App\Support\AuditCatat;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DFD 2.3 Penugasan Wali Kelas (Tata Usaha) dan daftar siswa per kelas.
 * Aturan database: satu wali per kelas per semester (UNIQUE id_kelas, id_semester).
 * Seorang guru boleh menjadi wali di beberapa kelas; halaman hanya memberi peringatan.
 */
class KelasController extends Controller
{
    public function index(Request $r)
    {
        $semesters = $this->daftarSemester();
        $sem = $this->pilihSemester($r, $semesters);
        $kelas = $sem ? $this->daftarKelas($sem) : collect();
        $guru = DB::table('guru')->where('is_aktif', true)->orderBy('nama_guru')->get(['id_guru', 'nama_guru']);

        return view('tu.kelas', compact('semesters', 'sem', 'kelas', 'guru'));
    }

    public function tetapkan(Request $r)
    {
        $d = $r->validate([
            'id_kelas'    => 'required|integer|exists:kelas,id_kelas',
            'id_guru'     => 'required|integer|exists:guru,id_guru',
            'id_semester' => 'required|integer|exists:semester,id_semester',
        ]);

        $kelas = DB::table('kelas')->where('id_kelas', $d['id_kelas'])->first();
        $guru = DB::table('guru')->where('id_guru', $d['id_guru'])->first();
        $sem = DB::table('semester')->where('id_semester', $d['id_semester'])->first();

        $galat = match (true) {
            !$kelas->is_aktif => 'Kelas ini sudah nonaktif.',
            !$guru->is_aktif  => 'Guru ini sudah nonaktif.',
            $kelas->id_tahun_ajaran !== null && (int) $kelas->id_tahun_ajaran !== (int) $sem->id_tahun_ajaran
                              => 'Kelas ini milik tahun ajaran lain, bukan tahun ajaran semester yang dipilih.',
            default           => null,
        };
        if ($galat) return back()->withInput()->withErrors(['id_kelas' => $galat]);

        try {
            $pesan = DB::transaction(function () use ($d, $kelas, $guru) {
                $ada = DB::table('penugasan_wali_kelas')
                    ->where('id_kelas', $d['id_kelas'])->where('id_semester', $d['id_semester'])
                    ->lockForUpdate()->first();

                if ($ada && (int) $ada->id_guru === (int) $d['id_guru']) {
                    return 'Guru tersebut sudah menjadi wali kelas ini. Tidak ada perubahan.';
                }

                if ($ada) {
                    $lamaNama = DB::table('guru')->where('id_guru', $ada->id_guru)->value('nama_guru');
                    DB::table('penugasan_wali_kelas')->where('id_penugasan_wali_kelas', $ada->id_penugasan_wali_kelas)
                        ->update(['id_guru' => $d['id_guru'], 'ditugaskan_pada' => now()]);
                    AuditCatat::tulis('penugasan_wali_kelas', 'UPDATE', $ada->id_penugasan_wali_kelas,
                        ['kelas' => $kelas->nama_kelas, 'wali' => $lamaNama],
                        ['kelas' => $kelas->nama_kelas, 'wali' => $guru->nama_guru]);

                    return "Wali kelas {$kelas->nama_kelas} diganti menjadi {$guru->nama_guru}.";
                }

                $id = DB::table('penugasan_wali_kelas')->insertGetId([
                    'id_kelas' => $d['id_kelas'], 'id_guru' => $d['id_guru'],
                    'id_semester' => $d['id_semester'], 'ditugaskan_pada' => now(),
                ], 'id_penugasan_wali_kelas');
                AuditCatat::tulis('penugasan_wali_kelas', 'INSERT', $id, null,
                    ['kelas' => $kelas->nama_kelas, 'wali' => $guru->nama_guru, 'id_semester' => (int) $d['id_semester']]);

                return "{$guru->nama_guru} ditetapkan sebagai wali kelas {$kelas->nama_kelas}.";
            });
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->withErrors(['id_kelas' => 'Kelas ini baru saja diberi wali oleh pengguna lain. Muat ulang halaman.']);
        }

        return redirect()->route('tu.kelas', ['semester' => $d['id_semester']])->with('success', $pesan);
    }

    public function cabut(int $id)
    {
        $baris = DB::transaction(function () use ($id) {
            $p = DB::table('penugasan_wali_kelas as pw')
                ->join('kelas as k', 'k.id_kelas', '=', 'pw.id_kelas')
                ->join('guru as g', 'g.id_guru', '=', 'pw.id_guru')
                ->where('pw.id_penugasan_wali_kelas', $id)->lockForUpdate()
                ->first(['pw.id_semester', 'k.nama_kelas', 'g.nama_guru']);
            if (!$p) return null;

            DB::table('penugasan_wali_kelas')->where('id_penugasan_wali_kelas', $id)->delete();
            AuditCatat::tulis('penugasan_wali_kelas', 'DELETE', $id, ['kelas' => $p->nama_kelas, 'wali' => $p->nama_guru], null);

            return $p;
        });
        abort_unless($baris, 404);

        return redirect()->route('tu.kelas', ['semester' => $baris->id_semester])
            ->with('success', "Wali kelas {$baris->nama_kelas} dicabut.");
    }

    public function detail(Request $r, int $id)
    {
        [$kelas, $semesters, $sem, $wali, $siswa, $cari] = $this->dataDetail($r, $id);

        return view('tu.kelas-detail', compact('kelas', 'semesters', 'sem', 'wali', 'siswa', 'cari'));
    }

    public function unduh(Request $r, int $id)
    {
        [$kelas, , $sem, , $siswa] = $this->dataDetail($r, $id, true);
        abort_unless($sem, 404);

        $aman = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v;   // cegah formula Excel
        $nama = 'kelas-'.preg_replace('/[^A-Za-z0-9]+/', '-', $kelas->nama_kelas).'.csv';

        return response()->streamDownload(function () use ($siswa, $aman) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No', 'Nama', 'NIS', 'NISN']);
            foreach ($siswa as $i => $s) {
                fputcsv($out, [$i + 1, $aman($s->nama_lengkap), $aman($s->nis), $aman($s->nisn)]);
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ---------------- helper ---------------- */

    private function daftarSemester()
    {
        return DB::table('semester as s')
            ->join('tahun_ajaran as t', 't.id_tahun_ajaran', '=', 's.id_tahun_ajaran')
            ->orderByDesc('t.tanggal_mulai')->orderByDesc('s.tanggal_mulai')
            ->get(['s.id_semester', 's.nama_semester', 's.is_aktif', 't.id_tahun_ajaran', 't.nama_tahun_ajaran']);
    }

    /** Semester dari ?semester=, jika tidak ada pakai yang aktif, lalu yang terbaru. */
    private function pilihSemester(Request $r, $semesters)
    {
        $dipilih = (int) $r->input('semester', 0);

        return $semesters->firstWhere('id_semester', $dipilih)
            ?? $semesters->firstWhere('is_aktif', true)
            ?? $semesters->first();
    }

    private function daftarKelas($sem)
    {
        return DB::table('kelas as k')
            ->leftJoin('tingkat as t', 't.id_tingkat', '=', 'k.id_tingkat')
            ->leftJoin('penugasan_wali_kelas as pw', function ($j) use ($sem) {
                $j->on('pw.id_kelas', '=', 'k.id_kelas')->where('pw.id_semester', '=', $sem->id_semester);
            })
            ->leftJoin('guru as g', 'g.id_guru', '=', 'pw.id_guru')
            ->where('k.is_aktif', true)
            ->where(fn ($q) => $q->where('k.id_tahun_ajaran', $sem->id_tahun_ajaran)->orWhereNull('k.id_tahun_ajaran'))
            ->selectRaw("k.id_kelas, k.nama_kelas, k.kapasitas, t.nama_tingkat, pw.id_penugasan_wali_kelas, g.id_guru, g.nama_guru,
                (select count(*) from keanggotaan_kelas kk where kk.id_kelas = k.id_kelas and kk.id_semester = ? and kk.status = 'aktif') as jumlah",
                [$sem->id_semester])
            ->orderBy('t.urutan')->orderBy('k.nama_kelas')
            ->get();
    }

    private function dataDetail(Request $r, int $id, bool $semuaSiswa = false): array
    {
        $kelas = DB::table('kelas as k')->leftJoin('tingkat as t', 't.id_tingkat', '=', 'k.id_tingkat')
            ->where('k.id_kelas', $id)->first(['k.id_kelas', 'k.nama_kelas', 'k.kapasitas', 't.nama_tingkat']);
        abort_unless($kelas, 404);

        $semesters = $this->daftarSemester();
        $sem = $this->pilihSemester($r, $semesters);
        $cari = $semuaSiswa ? '' : trim((string) $r->query('cari', ''));
        $like = '%'.addcslashes($cari, '%_\\').'%';

        $wali = $sem ? DB::table('penugasan_wali_kelas as pw')->join('guru as g', 'g.id_guru', '=', 'pw.id_guru')
            ->where('pw.id_kelas', $id)->where('pw.id_semester', $sem->id_semester)->value('g.nama_guru') : null;

        $siswa = $sem ? DB::table('keanggotaan_kelas as kk')->join('siswa as s', 's.id_siswa', '=', 'kk.id_siswa')
            ->where('kk.id_kelas', $id)->where('kk.id_semester', $sem->id_semester)->where('kk.status', 'aktif')
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w->where('s.nama_lengkap', 'ilike', $like)->orWhere('s.nisn', 'ilike', $like)->orWhere('s.nis', 'ilike', $like)))
            ->orderBy('s.nama_lengkap')
            ->get(['s.nama_lengkap', 's.nis', 's.nisn']) : collect();

        return [$kelas, $semesters, $sem, $wali, $siswa, $cari];
    }
}
