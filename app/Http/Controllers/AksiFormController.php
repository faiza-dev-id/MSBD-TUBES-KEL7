<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Siswa;
use App\Rules\SafeUploadFile;
use App\Services\KenaikanKelasService;
use App\Services\PengajuanPerubahanService;
use App\Services\PengaturanService;
use App\Services\PpdbService;
use App\Support\AuditCatat;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pemroses form halaman web yang sebelumnya belum memiliki backend.
 * Semua aksi mengembalikan pesan yang jelas bila data master belum tersedia.
 */
class AksiFormController extends Controller
{
    /* ================= SISWA ================= */

    public function konfirmasiSiswa(Request $request)
    {
        $data = $request->validate([
            'id_periode' => ['required', 'integer', 'exists:periode_update,id_periode_update'],
            'konfirmasi' => ['accepted'],
        ], [
            'konfirmasi.accepted' => 'Centang dulu pernyataan bahwa datamu masih benar.',
            'id_periode.required' => 'Belum ada periode update yang bisa dikonfirmasi. Hubungi Tata Usaha.',
            'id_periode.exists' => 'Periode update tidak ditemukan atau sudah dihapus.',
        ]);

        $siswa = $this->siswaSesi();

        if (! $siswa) {
            return $this->gagal('Akunmu belum terhubung ke data siswa. Hubungi Tata Usaha.');
        }

        $periode = DB::table('periode_update')
            ->where('id_periode_update', $data['id_periode'])
            ->first();

        if (! $periode || $periode->status !== 'aktif') {
            return $this->gagal('Periode update sedang ditutup.');
        }

        DB::table('konfirmasi_data')->updateOrInsert(
            [
                'id_periode_update' => $periode->id_periode_update,
                'id_siswa' => $siswa->id_siswa,
            ],
            [
                'status' => 'dikonfirmasi',
                'dikonfirmasi_pada' => now(),
                'catatan' => null,
            ]
        );

        return redirect()
            ->route('siswa.konfirmasi')
            ->with('success', 'Konfirmasi data berhasil disimpan.');
    }

    public function pengajuanSiswa(Request $request, PengaturanService $pengaturan, PengajuanPerubahanService $pengajuanService)
    {
        $maksMb = max(1, (int) ($pengaturan->nilai('ukuran_berkas_mb') ?: 2));
        $maksKb = $maksMb * 1024;

        $data = $request->validate([
            'id_field' => ['required', 'integer'],
            'nilai_baru' => ['required', 'string', 'max:500'],
            'bukti' => ['nullable', 'file', new SafeUploadFile($pengaturan->formatBerkasDiizinkan()), 'max:'.$maksKb],
        ], [
            'id_field.required' => 'Pilih data yang ingin diubah.',
            'id_field.integer' => 'Data yang dipilih tidak valid.',
            'nilai_baru.required' => 'Isi nilai baru.',
            'bukti.mimes' => 'Bukti harus berupa PDF, JPG, atau PNG.',
            'bukti.max' => 'Ukuran bukti maksimal '.$maksMb.' MB.',
        ]);

        $siswa = $this->siswaSesi();

        if (! $siswa) {
            return $this->gagal('Akunmu belum terhubung ke data siswa. Hubungi Tata Usaha.');
        }

        $periode = $this->periodeAktif();

        if (! $periode) {
            return $this->gagal('Periode update data belum dibuka. Hubungi Tata Usaha.');
        }

        $field = DB::table('ref_field_data')
            ->where('id_field', $data['id_field'])
            ->first(['id_field', 'nama_tabel', 'nama_kolom', 'label_field', 'boleh_diubah_siswa', 'perlu_bukti']);

        if (! $field || ! $field->boleh_diubah_siswa) {
            return $this->gagal('Data yang dipilih tidak dapat diajukan untuk diubah.', 'id_field');
        }

        if ($field->nama_tabel === 'siswa' && $field->nama_kolom === 'jenis_kelamin') {
            return $this->gagal('Jenis kelamin tidak dapat diajukan untuk diubah.', 'id_field');
        }

        if (! in_array((string) $field->nama_tabel, ['siswa', 'alamat_siswa', 'orang_tua_wali'], true)) {
            return $this->gagal('Data yang dipilih tidak dapat diajukan untuk diubah.', 'id_field');
        }

        if ($field->perlu_bukti && ! $request->hasFile('bukti')) {
            return $this->gagal('Bukti pendukung wajib dilampirkan untuk perubahan data ini.', 'bukti');
        }

        $nilaiLama = $this->nilaiLamaPengajuan((int) $siswa->id_siswa, (string) $field->nama_tabel, (string) $field->nama_kolom);

        $path = $request->hasFile('bukti')
            ? $request->file('bukti')->store('bukti-pengajuan', 'private')
            : null;

        $pengajuanService->ajukan(
            (int) $siswa->id_siswa,
            (int) $periode->id_periode_update,
            'Perubahan '.$field->label_field,
            [[
                'nama_field' => (string) $field->nama_kolom,
                'nilai_lama' => $nilaiLama,
                'nilai_baru' => $data['nilai_baru'],
                'bukti_path' => $path,
            ]]
        );

        return redirect()
            ->route('siswa.pengajuan')
            ->with('success', 'Pengajuan perubahan berhasil dikirim dan menunggu validasi wali kelas.');
    }

    private function nilaiLamaPengajuan(int $idSiswa, string $tabel, string $kolom): mixed
    {
        if ($tabel === 'siswa') {
            $siswa = DB::table('siswa')->where('id_siswa', $idSiswa)->first();
            if (! $siswa) {
                return null;
            }

            return property_exists($siswa, $kolom) ? $siswa->{$kolom} : null;
        }

        if ($tabel === 'alamat_siswa') {
            $alamat = DB::table('alamat_siswa')->where('id_siswa', $idSiswa)->orderBy('id_alamat')->first();
            if (! $alamat) {
                return null;
            }

            return property_exists($alamat, $kolom) ? $alamat->{$kolom} : null;
        }

        if ($tabel === 'orang_tua_wali') {
            $pos = strpos($kolom, '_');
            if ($pos === false) {
                return null;
            }
            $hubungan = substr($kolom, 0, $pos);
            $kolomAsli = substr($kolom, $pos + 1);
            $row = DB::table('orang_tua_wali')
                ->where('id_siswa', $idSiswa)
                ->where('hubungan', $hubungan)
                ->first();
            if (! $row) {
                return null;
            }

            return property_exists($row, $kolomAsli) ? $row->{$kolomAsli} : null;
        }

        return null;
    }

    /* ================= GURU ================= */

    public function catatanSimpan(Request $request, PengajuanPerubahanService $pengajuanService)
    {
        $data = $request->validate([
            'id_siswa' => ['required', 'integer', 'exists:siswa,id_siswa'],
            'isi_catatan' => ['required', 'string', 'max:2000'],
        ], [
            'id_siswa.required' => 'Pilih siswa terlebih dahulu.',
            'id_siswa.exists' => 'Siswa yang dipilih tidak ditemukan.',
            'isi_catatan.required' => 'Isi catatan tidak boleh kosong.',
        ]);

        $idGuru = Guru::where('id_pengguna', session('id_pengguna'))->value('id_guru');

        if (! $idGuru) {
            return $this->gagal('Akunmu belum terhubung ke data wali kelas. Hubungi Tata Usaha.');
        }

        if (! $pengajuanService->waliKelasMenanganiSiswa((int) session('id_pengguna'), (int) $data['id_siswa'])) {
            return $this->gagal('Siswa yang dipilih bukan siswa di kelas yang kamu walikan pada semester aktif.', 'id_siswa');
        }

        $tahun = $this->tahunAjaranAktif();

        if (! $tahun) {
            return $this->gagal('Belum ada tahun ajaran. Tambahkan dulu di Konfigurasi Akademik.');
        }

        DB::transaction(function () use ($data, $idGuru, $tahun) {
            $idPenggunaSiswa = DB::table('siswa')
                ->where('id_siswa', $data['id_siswa'])
                ->value('id_pengguna');

            if (! $idPenggunaSiswa) {
                throw ValidationException::withMessages([
                    'id_siswa' => 'Akun siswa belum terhubung, sehingga notifikasi catatan tidak dapat dikirim. Hubungi Tata Usaha.',
                ]);
            }

            DB::table('catatan_wali_kelas')->insert([
                'id_siswa' => $data['id_siswa'],
                'id_guru' => $idGuru,
                'id_tahun_ajaran' => $tahun->id_tahun_ajaran,
                'isi_catatan' => $data['isi_catatan'],
                'tgl_dibuat' => now('Asia/Jakarta'),
            ]);

            DB::table('notifikasi')->insert([
                'id_pengguna' => $idPenggunaSiswa,
                'judul' => 'Catatan dari Wali Kelas',
                'pesan' => 'Wali kelas memberikan catatan: '.$data['isi_catatan'],
                'dibaca' => false,
                'dibuat_pada' => now('Asia/Jakarta'),
            ]);
        });

        return redirect()
            ->route('guru.catatan')
            ->with('success', 'Catatan wali kelas berhasil disimpan.');
    }

    public function absensiSimpan(Request $request)
    {
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'array'],
            'status.*' => ['required', 'in:H,I,S,A'],
            'keterangan' => ['nullable', 'array'],
            'keterangan.*' => ['nullable', 'string', 'max:255'],
        ], [
            'id_kelas.required' => 'Pilih kelas terlebih dahulu.',
            'id_kelas.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'tanggal.required' => 'Tanggal presensi wajib diisi.',
            'tanggal.date' => 'Tanggal presensi tidak valid.',
            'status.required' => 'Isi status kehadiran siswa.',
            'status.*.in' => 'Status kehadiran hanya boleh H (Hadir), I (Izin), S (Sakit), atau A (Alpha).',
        ]);

        $idGuru = Guru::where('id_pengguna', session('id_pengguna'))->value('id_guru');

        if (! $idGuru) {
            return $this->gagal('Akunmu belum terhubung ke data wali kelas. Hubungi Tata Usaha.');
        }

        $semester = $this->semesterAktif();

        if (! $semester) {
            return $this->gagal('Belum ada semester. Tambahkan dulu di Konfigurasi Akademik.');
        }

        $menjadiWali = DB::table('penugasan_wali_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->exists();

        if (! $menjadiWali) {
            return $this->gagal('Kamu bukan wali kelas ini pada semester aktif.');
        }

        $anggota = DB::table('keanggotaan_kelas')
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->where('status', 'aktif')
            ->pluck('id_siswa')
            ->all();

        if (! $anggota) {
            return $this->gagal('Belum ada siswa aktif di kelas ini.');
        }

        $statusIds = $this->statusKehadiranIds();

        DB::transaction(function () use ($data, $semester, $anggota, $statusIds, $idGuru) {
            $sesi = DB::table('sesi_absensi')
                ->where('id_kelas', $data['id_kelas'])
                ->where('id_semester', $semester->id_semester)
                ->where('tanggal', $data['tanggal'])
                ->lockForUpdate()
                ->first(['id_sesi']);

            if (! $sesi) {
                DB::table('sesi_absensi')->insertOrIgnore([
                    'id_jadwal' => null,
                    'id_kelas' => $data['id_kelas'],
                    'id_semester' => $semester->id_semester,
                    'id_guru_pencatat' => $idGuru,
                    'tanggal' => $data['tanggal'],
                    'topik_materi' => 'Presensi harian wali kelas',
                    'status_sesi' => 'dibuka',
                ]);

                $sesi = DB::table('sesi_absensi')
                    ->where('id_kelas', $data['id_kelas'])
                    ->where('id_semester', $semester->id_semester)
                    ->where('tanggal', $data['tanggal'])
                    ->lockForUpdate()
                    ->first(['id_sesi']);
            }

            if (! $sesi) {
                throw new \RuntimeException('Sesi absensi harian tidak dapat dibuat atau ditemukan.');
            }

            $idSesi = $sesi->id_sesi;

            foreach ($anggota as $idSiswa) {
                $kode = $data['status'][$idSiswa] ?? 'H';

                if (! isset($statusIds[$kode])) {
                    continue;
                }

                $keterangan = trim((string) ($data['keterangan'][$idSiswa] ?? ''));

                DB::table('detail_absensi')->updateOrInsert(
                    ['id_sesi' => $idSesi, 'id_siswa' => $idSiswa],
                    [
                        'id_status_hadir' => $statusIds[$kode],
                        'keterangan' => $keterangan !== '' ? $keterangan : null,
                        'waktu_dicatat' => now(),
                    ]
                );
            }
        });

        return redirect()
            ->route('guru.absensi', [
                'kelas' => $data['id_kelas'],
                'tanggal' => $data['tanggal'],
            ])
            ->with('success', 'Presensi kelas berhasil disimpan.');
    }

    public function raporSimpan(Request $request)
    {
        if ($request->has('nilai_mentah') || $request->has('catatan_siswa') || $request->has('status_siswa')) {
            return $this->raporSimpanBulk($request);
        }

        $data = $request->validate([
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'id_siswa' => ['required', 'integer', 'exists:siswa,id_siswa'],
            'nilai' => ['nullable', 'array'],
            'nilai.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'catatan_wali' => ['nullable', 'string', 'max:2000'],
            'status_rapor' => ['nullable', 'in:draft'],
        ], [
            'id_kelas.required' => 'Pilih kelas terlebih dahulu.',
            'id_kelas.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'id_siswa.required' => 'Pilih siswa terlebih dahulu.',
            'id_siswa.exists' => 'Siswa yang dipilih tidak ditemukan.',
            'nilai.*.numeric' => 'Nilai harus berupa angka.',
            'nilai.*.min' => 'Nilai minimal 0.',
            'nilai.*.max' => 'Nilai maksimal 100.',
            'status_rapor.in' => 'Status rapor tidak dikenali.',
        ]);

        $idGuru = Guru::where('id_pengguna', session('id_pengguna'))->value('id_guru');

        if (! $idGuru) {
            return $this->gagal('Akunmu belum terhubung ke data wali kelas. Hubungi Tata Usaha.');
        }

        $semester = $this->semesterAktif();

        if (! $semester) {
            return $this->gagal('Belum ada semester. Tambahkan dulu di Konfigurasi Akademik.');
        }

        $menjadiWali = DB::table('penugasan_wali_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->exists();

        if (! $menjadiWali) {
            return $this->gagal('Kamu bukan wali kelas ini pada semester aktif.');
        }

        $anggota = DB::table('keanggotaan_kelas')
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->where('id_siswa', $data['id_siswa'])
            ->first();

        if (! $anggota) {
            return $this->gagal('Siswa ini tidak terdaftar di kelas tersebut pada semester aktif.');
        }

        $semuaNilai = $data['nilai'] ?? [];
        $terisi = [];

        foreach ($semuaNilai as $idMapel => $nilai) {
            if ($nilai === null || $nilai === '') {
                continue;
            }

            $terisi[(int) $idMapel] = (float) $nilai;
        }

        if (! $terisi) {
            return $this->gagal('Isi minimal satu nilai mata pelajaran.');
        }

        $mapelValid = DB::table('mata_pelajaran')
            ->pluck('id_mata_pelajaran')
            ->map(fn ($id) => (int) $id)
            ->all();

        $terisi = array_filter($terisi, fn ($id) => in_array($id, $mapelValid, true), ARRAY_FILTER_USE_KEY);

        if (! $terisi) {
            return $this->gagal('Mata pelajaran yang dipilih tidak ditemukan.');
        }

        $rataRata = round(array_sum($terisi) / count($terisi), 2);

        $rekapRows = DB::table('detail_absensi as da')
            ->join('sesi_absensi as sa', 'sa.id_sesi', '=', 'da.id_sesi')
            ->join('status_kehadiran as sh', 'sh.id_status_hadir', '=', 'da.id_status_hadir')
            ->where('da.id_siswa', $data['id_siswa'])
            ->where('sa.id_kelas', $data['id_kelas'])
            ->where('sa.id_semester', $semester->id_semester)
            ->select('sh.kode', DB::raw('COUNT(*) AS jumlah'))
            ->groupBy('sh.kode')
            ->pluck('jumlah', 'kode')
            ->all();

        $rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];

        foreach ($rekapRows as $kode => $jumlah) {
            $kode = $this->kodeKehadiran($kode);

            if (isset($rekap[$kode])) {
                $rekap[$kode] += (int) $jumlah;
            }
        }

        $mapelForm = array_map('intval', array_keys($semuaNilai));

        DB::transaction(function () use ($data, $semester, $anggota, $terisi, $rataRata, $rekap, $mapelForm) {
            $rapor = DB::table('rapor')
                ->where('id_anggota', $anggota->id_keanggotaan)
                ->where('id_semester', $semester->id_semester)
                ->lockForUpdate()
                ->first(['id_rapor', 'status_rapor']);

            if ($rapor?->status_rapor === 'final') {
                throw ValidationException::withMessages([
                    'rapor' => 'Nilai tidak dapat diubah setelah rapor dikunci.',
                ]);
            }

            $isi = [
                'id_penyusun' => session('id_pengguna'),
                'rata_rata' => $rataRata,
                'jumlah_sakit' => $rekap['sakit'],
                'jumlah_izin' => $rekap['izin'],
                'jumlah_alpa' => $rekap['alpha'],
                'catatan_wali' => $data['catatan_wali'] ?? null,
                'status_rapor' => $data['status_rapor'] ?? 'draft',
            ];

            if ($rapor) {
                DB::table('rapor')->where('id_rapor', $rapor->id_rapor)->update($isi);
                $idRapor = $rapor->id_rapor;
            } else {
                $idRapor = DB::table('rapor')->insertGetId($isi + [
                    'id_anggota' => $anggota->id_keanggotaan,
                    'id_semester' => $semester->id_semester,
                    'tgl_dibuat' => now(),
                ], 'id_rapor');
            }

            foreach ($terisi as $idMapel => $nilai) {
                $idPredikat = DB::table('skala_predikat')
                    ->where('nilai_min', '<=', $nilai)
                    ->where('nilai_maks', '>=', $nilai)
                    ->orderByDesc('nilai_min')
                    ->value('id_predikat');

                DB::table('detail_rapor')->updateOrInsert(
                    ['id_rapor' => $idRapor, 'id_mapel' => $idMapel],
                    ['id_predikat' => $idPredikat, 'nilai_akhir' => round($nilai, 2)]
                );
            }

            $kosong = array_diff($mapelForm, array_keys($terisi));

            if ($kosong) {
                DB::table('detail_rapor')
                    ->where('id_rapor', $idRapor)
                    ->whereIn('id_mapel', $kosong)
                    ->delete();
            }
        });

        return redirect()
            ->route('guru.rapor', ['kelas' => $data['id_kelas'], 'siswa' => $data['id_siswa']])
            ->with('success', 'Rapor siswa berhasil disimpan.');
    }

    public function raporFinalisasi(Request $request)
    {
        $data = $request->validate([
            'id_rapor' => ['required', 'integer', 'min:1'],
        ]);
        $idGuru = Guru::where('id_pengguna', session('id_pengguna'))->value('id_guru');

        if (! $idGuru) {
            return $this->gagal('Akunmu belum terhubung ke data wali kelas. Hubungi Tata Usaha.');
        }

        DB::transaction(function () use ($data, $idGuru) {
            $rapor = DB::table('rapor as r')
                ->join('keanggotaan_kelas as kk', 'kk.id_keanggotaan', '=', 'r.id_anggota')
                ->where('r.id_rapor', $data['id_rapor'])
                ->lock('FOR UPDATE OF r')
                ->first([
                    'r.id_rapor',
                    'r.id_semester',
                    'r.status_rapor',
                    'kk.id_kelas',
                ]);

            abort_unless($rapor, 404);
            abort_unless(
                DB::table('penugasan_wali_kelas')
                    ->where('id_guru', $idGuru)
                    ->where('id_kelas', $rapor->id_kelas)
                    ->where('id_semester', $rapor->id_semester)
                    ->exists(),
                403
            );

            if ($rapor->status_rapor === 'final') {
                throw ValidationException::withMessages([
                    'id_rapor' => 'Rapor ini sudah dikunci.',
                ]);
            }

            if (! DB::table('detail_rapor')->where('id_rapor', $rapor->id_rapor)->exists()) {
                throw ValidationException::withMessages([
                    'id_rapor' => 'Isi nilai rapor sebelum menguncinya.',
                ]);
            }

            DB::statement(
                'CALL public.sp_finalisasi_rapor(?, ?)',
                [$rapor->id_rapor, 'final']
            );
        });

        $redirectParams = array_filter([
            'siswa' => $request->integer('id_siswa') ?: null,
            'mapel' => $request->integer('id_mapel') ?: null,
        ]);

        return redirect()
            ->route('guru.rapor', $redirectParams)
            ->with('success', 'Rapor siswa berhasil dikunci.');
    }

    /**
     * Simpan nilai mentah (tugas, keterampilan, ujian) untuk seluruh siswa
     * pada satu kelas dan satu mata pelajaran sekaligus.
     */
    private function raporSimpanBulk(Request $request)
    {
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'id_mapel' => ['required', 'integer', 'exists:mata_pelajaran,id_mata_pelajaran'],
            'id_siswa' => ['nullable', 'integer', 'exists:siswa,id_siswa'],
            'nilai_mentah' => ['nullable', 'array'],
            'nilai_mentah.*.tugas' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'nilai_mentah.*.keterampilan' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'nilai_mentah.*.ujian' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'catatan_siswa' => ['nullable', 'array'],
            'catatan_siswa.*' => ['nullable', 'string', 'max:2000'],
            'status_siswa' => ['nullable', 'array'],
            'status_siswa.*' => ['nullable', 'in:draft'],
        ], [
            'id_kelas.required' => 'Pilih kelas terlebih dahulu.',
            'id_kelas.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'id_mapel.required' => 'Pilih mata pelajaran terlebih dahulu.',
            'id_mapel.exists' => 'Mata pelajaran yang dipilih tidak ditemukan.',
            'nilai_mentah.*.tugas.numeric' => 'Nilai tugas harus berupa angka.',
            'nilai_mentah.*.tugas.min' => 'Nilai tugas minimal 1.',
            'nilai_mentah.*.tugas.max' => 'Nilai tugas maksimal 100.',
            'nilai_mentah.*.keterampilan.numeric' => 'Nilai keterampilan harus berupa angka.',
            'nilai_mentah.*.keterampilan.min' => 'Nilai keterampilan minimal 1.',
            'nilai_mentah.*.keterampilan.max' => 'Nilai keterampilan maksimal 100.',
            'nilai_mentah.*.ujian.numeric' => 'Nilai ujian harus berupa angka.',
            'nilai_mentah.*.ujian.min' => 'Nilai ujian minimal 1.',
            'nilai_mentah.*.ujian.max' => 'Nilai ujian maksimal 100.',
            'catatan_siswa.*.max' => 'Catatan wali kelas maksimal 2000 karakter.',
            'status_siswa.*.in' => 'Status rapor tidak dikenali.',
        ]);

        $idGuru = Guru::where('id_pengguna', session('id_pengguna'))->value('id_guru');

        if (! $idGuru) {
            return $this->gagal('Akunmu belum terhubung ke data wali kelas. Hubungi Tata Usaha.');
        }

        $semester = $this->semesterAktif();

        if (! $semester) {
            return $this->gagal('Belum ada semester. Tambahkan dulu di Konfigurasi Akademik.');
        }

        $menjadiWali = DB::table('penugasan_wali_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->exists();

        if (! $menjadiWali) {
            return $this->gagal('Kamu bukan wali kelas ini pada semester aktif.');
        }

        $mapel = DB::table('mata_pelajaran')
            ->where('id_mata_pelajaran', $data['id_mapel'])
            ->first();

        if (! $mapel) {
            return $this->gagal('Mata pelajaran yang dipilih tidak ditemukan.');
        }

        $komponen = $this->komponenRapor();

        if ($komponen['tugas'] === null || $komponen['keterampilan'] === null || $komponen['ujian'] === null) {
            return $this->gagal('Komponen penilaian Tugas, Keterampilan, dan Ujian belum lengkap di Konfigurasi Akademik.');
        }

        $bobotKomponen = $this->bobotKomponenRapor($komponen);

        if (array_sum($bobotKomponen) <= 0) {
            return $this->gagal('Bobot komponen penilaian belum diatur. Atur bobot di Konfigurasi Akademik.');
        }

        $anggota = DB::table('keanggotaan_kelas')
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->where('status', 'aktif')
            ->get(['id_keanggotaan', 'id_siswa'])
            ->keyBy('id_siswa');

        if ($anggota->isEmpty()) {
            return $this->gagal('Belum ada siswa aktif di kelas ini pada semester aktif.');
        }

        $nilaiRows = $data['nilai_mentah'] ?? [];
        $catatanRows = $data['catatan_siswa'] ?? [];
        $statusRows = $data['status_siswa'] ?? [];
        $diproses = 0;

        DB::transaction(function () use (
            $data,
            $semester,
            $mapel,
            $komponen,
            $bobotKomponen,
            $anggota,
            $nilaiRows,
            $catatanRows,
            $statusRows,
            &$diproses
        ) {
            $komponenIds = array_values(array_filter($komponen, fn ($id) => $id !== null));

            foreach ($nilaiRows as $idSiswa => $baris) {
                $idSiswa = (int) $idSiswa;
                $anggotaRow = $anggota->get($idSiswa);

                if (! $anggotaRow) {
                    continue;
                }

                $tugas = $this->angkaNilai($baris['tugas'] ?? null);
                $keterampilan = $this->angkaNilai($baris['keterampilan'] ?? null);
                $ujian = $this->angkaNilai($baris['ujian'] ?? null);

                $adaCatatan = array_key_exists($idSiswa, $catatanRows);
                $catatan = $adaCatatan ? trim((string) ($catatanRows[$idSiswa] ?? '')) : null;
                $catatan = $catatan === '' ? null : $catatan;
                $status = $statusRows[$idSiswa] ?? null;
                $status = $status === 'draft' ? 'draft' : null;

                $rapor = DB::table('rapor')
                    ->where('id_anggota', $anggotaRow->id_keanggotaan)
                    ->where('id_semester', $semester->id_semester)
                    ->lockForUpdate()
                    ->first(['id_rapor', 'status_rapor']);

                if ($rapor?->status_rapor === 'final') {
                    throw ValidationException::withMessages([
                        'nilai_mentah' => 'Nilai tidak dapat diubah setelah rapor dikunci.',
                    ]);
                }

                if ($tugas === null && $keterampilan === null && $ujian === null) {
                    DB::table('nilai_siswa')
                        ->where('id_anggota', $anggotaRow->id_keanggotaan)
                        ->where('id_mapel', $mapel->id_mata_pelajaran)
                        ->where('id_semester', $semester->id_semester)
                        ->whereIn('id_komponen', $komponenIds)
                        ->delete();

                    if ($rapor) {
                        $ubah = ['id_penyusun' => session('id_pengguna')];

                        if ($adaCatatan) {
                            $ubah['catatan_wali'] = $catatan;
                        }

                        if ($status !== null) {
                            $ubah['status_rapor'] = $status;
                        }

                        DB::table('detail_rapor')
                            ->where('id_rapor', $rapor->id_rapor)
                            ->where('id_mapel', $mapel->id_mata_pelajaran)
                            ->delete();

                        $rataRata = DB::table('detail_rapor')
                            ->where('id_rapor', $rapor->id_rapor)
                            ->avg('nilai_akhir');

                        $ubah['rata_rata'] = $rataRata !== null ? round((float) $rataRata, 2) : null;

                        DB::table('rapor')->where('id_rapor', $rapor->id_rapor)->update($ubah);
                    }

                    $diproses++;

                    continue;
                }

                foreach ([
                    'tugas' => $tugas,
                    'keterampilan' => $keterampilan,
                    'ujian' => $ujian,
                ] as $jenis => $nilai) {
                    $kunci = [
                        'id_anggota' => $anggotaRow->id_keanggotaan,
                        'id_mapel' => $mapel->id_mata_pelajaran,
                        'id_semester' => $semester->id_semester,
                        'id_komponen' => $komponen[$jenis],
                    ];

                    if ($nilai === null) {
                        DB::table('nilai_siswa')->where($kunci)->delete();

                        continue;
                    }

                    DB::table('nilai_siswa')->updateOrInsert($kunci, [
                        'id_penginput' => session('id_pengguna'),
                        'nilai' => round($nilai, 2),
                        'tgl_input' => now(),
                    ]);
                }

                $nilaiAkhir = $this->nilaiAkhirKomponen([
                    'tugas' => $tugas,
                    'keterampilan' => $keterampilan,
                    'ujian' => $ujian,
                ], $bobotKomponen);

                if ($nilaiAkhir === null) {
                    if ($rapor) {
                        $ubah = ['id_penyusun' => session('id_pengguna')];

                        if ($adaCatatan) {
                            $ubah['catatan_wali'] = $catatan;
                        }

                        if ($status !== null) {
                            $ubah['status_rapor'] = $status;
                        }

                        DB::table('detail_rapor')
                            ->where('id_rapor', $rapor->id_rapor)
                            ->where('id_mapel', $mapel->id_mata_pelajaran)
                            ->delete();

                        $rataRata = DB::table('detail_rapor')
                            ->where('id_rapor', $rapor->id_rapor)
                            ->avg('nilai_akhir');

                        $ubah['rata_rata'] = $rataRata !== null ? round((float) $rataRata, 2) : null;

                        DB::table('rapor')->where('id_rapor', $rapor->id_rapor)->update($ubah);
                    }

                    $diproses++;

                    continue;
                }

                $idPredikat = DB::table('skala_predikat')
                    ->where('nilai_min', '<=', $nilaiAkhir)
                    ->where('nilai_maks', '>=', $nilaiAkhir)
                    ->orderByDesc('nilai_min')
                    ->value('id_predikat');

                if ($rapor) {
                    $ubah = ['id_penyusun' => session('id_pengguna')];

                    if ($adaCatatan) {
                        $ubah['catatan_wali'] = $catatan;
                    }

                    if ($status !== null) {
                        $ubah['status_rapor'] = $status;
                    }

                    DB::table('rapor')->where('id_rapor', $rapor->id_rapor)->update($ubah);
                    $idRapor = $rapor->id_rapor;
                } else {
                    $rekap = $this->rekapKehadiran(
                        $idSiswa,
                        (int) $data['id_kelas'],
                        (int) $semester->id_semester
                    );

                    $idRapor = DB::table('rapor')->insertGetId([
                        'id_anggota' => $anggotaRow->id_keanggotaan,
                        'id_semester' => $semester->id_semester,
                        'id_penyusun' => session('id_pengguna'),
                        'rata_rata' => null,
                        'jumlah_sakit' => $rekap['sakit'],
                        'jumlah_izin' => $rekap['izin'],
                        'jumlah_alpa' => $rekap['alpha'],
                        'catatan_wali' => $catatan,
                        'status_rapor' => $status ?? 'draft',
                        'tgl_dibuat' => now(),
                    ], 'id_rapor');
                }

                DB::table('detail_rapor')->updateOrInsert(
                    ['id_rapor' => $idRapor, 'id_mapel' => $mapel->id_mata_pelajaran],
                    ['id_predikat' => $idPredikat, 'nilai_akhir' => $nilaiAkhir]
                );

                $rataRata = DB::table('detail_rapor')
                    ->where('id_rapor', $idRapor)
                    ->avg('nilai_akhir');

                DB::table('rapor')->where('id_rapor', $idRapor)->update([
                    'rata_rata' => $rataRata !== null ? round((float) $rataRata, 2) : null,
                ]);

                $diproses++;
            }
        });

        $idSiswaRedirect = $data['id_siswa'] ?? null;

        if (! $idSiswaRedirect) {
            $kandidatSiswa = array_values(array_unique(array_merge(
                array_map('intval', array_keys($nilaiRows)),
                array_map('intval', array_keys($catatanRows)),
            )));

            if (count($kandidatSiswa) === 1 && $kandidatSiswa[0] > 0) {
                $idSiswaRedirect = $kandidatSiswa[0];
            }
        }

        $redirectParams = array_filter([
            'kelas' => $data['id_kelas'],
            'mapel' => $data['id_mapel'],
            'siswa' => $idSiswaRedirect,
        ]);

        if ($diproses === 0) {
            return redirect()
                ->route('guru.rapor', $redirectParams)
                ->with('success', 'Tidak ada nilai baru yang perlu disimpan.');
        }

        return redirect()
            ->route('guru.rapor', $redirectParams)
            ->with('success', 'Nilai rapor berhasil disimpan untuk seluruh siswa.');
    }

    /* ================= TATA USAHA ================= */

    public function mutasiSimpan(Request $request, PengaturanService $pengaturan)
    {
        if (! session('id_pengguna')) {
            return $this->gagal('Sesi masuk tidak dikenali. Silakan masuk ulang.');
        }

        $data = $this->validasiDataMutasi($request, $pengaturan);
        $pathLampiran = $this->simpanLampiranMutasi($request);

        try {
            DB::transaction(function () use ($data, $pathLampiran, $request) {
                $idPengguna = (int) session('id_pengguna');
                $pindahMasuk = $data['jenis'] === 'pindah_masuk';
                $siswa = $pindahMasuk ? null : $this->siswaAktifUntukMutasi((int) $data['id_siswa']);
                $status = $pindahMasuk ? 'disetujui' : 'menunggu';
                $waktuKeputusan = $pindahMasuk ? now() : null;

                $idMutasi = DB::table('mutasi_siswa')->insertGetId([
                    'id_siswa' => $siswa?->id_siswa,
                    'id_pengaju' => $idPengguna,
                    'id_penyetuju' => $pindahMasuk ? $idPengguna : null,
                    'id_sekolah_tujuan' => null,
                    'nama_sekolah_tujuan' => $data['jenis'] === 'pindah_keluar'
                        ? ($data['sekolah_tujuan'] ?? null)
                        : null,
                    'id_sekolah_asal' => $data['id_sekolah_asal'] ?? null,
                    'id_kelas_tujuan' => $data['id_kelas_tujuan'] ?? null,
                    'nisn_baru' => $data['nisn_baru'] ?? null,
                    'nama_siswa_baru' => $data['nama_siswa_baru'] ?? null,
                    'jenis_kelamin_baru' => $data['jenis_kelamin_baru'] ?? null,
                    'tempat_lahir_baru' => $data['tempat_lahir_baru'] ?? null,
                    'tanggal_lahir_baru' => $data['tanggal_lahir_baru'] ?? null,
                    'email_baru' => $data['email_baru'] ?? null,
                    'no_telepon_baru' => $data['no_telepon_baru'] ?? null,
                    'tanggal_efektif' => $data['tanggal_efektif'],
                    'path_lampiran' => $pathLampiran,
                    'nama_lampiran' => $request->file('lampiran')?->getClientOriginalName(),
                    'jenis' => $data['jenis'],
                    'alasan' => $data['alasan'],
                    'status' => $status,
                    'tgl_pengajuan' => now(),
                    'tgl_keputusan' => $waktuKeputusan,
                ], 'id_mutasi');

                if ($pindahMasuk) {
                    $idSiswa = $this->daftarkanSiswaMutasiMasuk($data);
                    DB::table('mutasi_siswa')->where('id_mutasi', $idMutasi)->update([
                        'id_siswa' => $idSiswa,
                    ]);
                    $siswa = DB::table('siswa')->where('id_siswa', $idSiswa)->first();
                }

                AuditCatat::tulis('mutasi_siswa', 'INSERT', $idMutasi, null, [
                    'id_siswa' => $siswa?->id_siswa,
                    'nama_siswa' => $siswa?->nama_lengkap ?? $data['nama_siswa_baru'],
                    'nisn' => $siswa?->nisn ?? $data['nisn_baru'],
                    'jenis' => $data['jenis'],
                    'sekolah_tujuan' => $data['sekolah_tujuan'] ?? null,
                    'status' => $status,
                    'id_pengaju' => $idPengguna,
                    'id_penyetuju' => $pindahMasuk ? $idPengguna : null,
                    'tanggal_efektif' => $data['tanggal_efektif'],
                ]);

                if (! $pindahMasuk) {
                    $this->notifikasiPeran(
                        'kepsek',
                        'Pengajuan mutasi siswa',
                        session('nama', 'Tata Usaha').' mengajukan '.$this->labelJenisMutasi($data['jenis'])
                            .' untuk '.$siswa->nama_lengkap.'.'
                    );
                }
            });
        } catch (\Throwable $exception) {
            if ($pathLampiran) {
                Storage::disk('private')->delete($pathLampiran);
            }

            throw $exception;
        }

        return redirect()
            ->route($this->ruteMutasi())
            ->with('success', $data['jenis'] === 'pindah_masuk'
                ? 'Siswa pindahan berhasil didaftarkan dan langsung aktif di kelas tujuan.'
                : 'Pengajuan mutasi berhasil dikirim ke Kepala Sekolah.');
    }

    public function mutasiPutuskan(Request $request, int $id)
    {
        $data = $request->validate([
            'keputusan' => ['required', 'in:setujui,tolak'],
            'catatan_keputusan' => ['required_if:keputusan,tolak', 'nullable', 'string', 'max:2000'],
        ], [
            'keputusan.required' => 'Pilih keputusan terlebih dahulu.',
            'keputusan.in' => 'Keputusan tidak dikenali.',
            'catatan_keputusan.required_if' => 'Catatan wajib diisi saat pengajuan ditolak.',
        ]);

        if (session('role') !== 'kepsek') {
            abort(403);
        }

        try {
            $jenisMutasi = null;
            $status = DB::transaction(function () use ($data, $id, &$jenisMutasi) {
                $mutasi = DB::table('mutasi_siswa')
                    ->where('id_mutasi', $id)
                    ->lockForUpdate()
                    ->first([
                        'id_mutasi',
                        'id_siswa',
                        'id_pengaju',
                        'jenis',
                        'status',
                        'tanggal_efektif',
                    ]);

                if (! $mutasi) {
                    throw ValidationException::withMessages(['keputusan' => 'Pengajuan mutasi tidak ditemukan.']);
                }
                if ($mutasi->status !== 'menunggu') {
                    throw ValidationException::withMessages(['keputusan' => 'Pengajuan mutasi ini sudah diputuskan.']);
                }
                $jenisMutasi = $mutasi->jenis;
                if (! in_array($mutasi->jenis, ['pindah_keluar', 'mengundurkan_diri', 'dikeluarkan', 'putus_sekolah', 'meninggal_dunia'], true)) {
                    abort(403);
                }

                $status = $data['keputusan'] === 'setujui' ? 'disetujui' : 'ditolak';
                $mutasiUpdate = [
                    'status' => $status,
                    'id_penyetuju' => session('id_pengguna'),
                    'tgl_keputusan' => now(),
                    'catatan_keputusan' => $data['catatan_keputusan'] ?? null,
                ];

                DB::table('mutasi_siswa')->where('id_mutasi', $id)->update($mutasiUpdate);

                $siswa = DB::table('siswa')
                    ->where('id_siswa', $mutasi->id_siswa)
                    ->lockForUpdate()
                    ->first(['id_siswa', 'nama_lengkap']);
                if (! $siswa) {
                    throw ValidationException::withMessages(['keputusan' => 'Data siswa yang dimutasi tidak ditemukan.']);
                }

                if ($status === 'disetujui') {
                    $kodeStatus = $mutasi->jenis === 'pindah_keluar' ? 'mutasi' : $mutasi->jenis;
                    $idStatus = DB::table('status_siswa')->where('kode_status', $kodeStatus)->value('id_status_siswa');
                    if (! $idStatus) {
                        throw ValidationException::withMessages([
                            'keputusan' => 'Status siswa '.$kodeStatus.' belum tersedia. Jalankan migrasi mutasi terlebih dahulu.',
                        ]);
                    }

                    DB::table('siswa')->where('id_siswa', $mutasi->id_siswa)->update([
                        'id_status_siswa' => $idStatus,
                        'diubah_pada' => now(),
                    ]);
                    DB::table('keanggotaan_kelas')
                        ->where('id_siswa', $mutasi->id_siswa)
                        ->where('status', 'aktif')
                        ->update([
                            'status' => $this->statusKeanggotaanMutasi($mutasi->jenis),
                            'tanggal_selesai' => $mutasi->tanggal_efektif ?: now()->toDateString(),
                        ]);
                }

                AuditCatat::tulis('mutasi_siswa', 'UPDATE', $id, [
                    'status' => 'menunggu',
                    'jenis' => $mutasi->jenis,
                    'nama_siswa' => $siswa->nama_lengkap,
                ], [
                    'status' => $status,
                    'jenis' => $mutasi->jenis,
                    'nama_siswa' => $siswa->nama_lengkap,
                    'id_pengaju' => $mutasi->id_pengaju,
                    'id_penyetuju' => session('id_pengguna'),
                    'catatan_keputusan' => $data['catatan_keputusan'] ?? null,
                    'tanggal_efektif' => $mutasi->tanggal_efektif,
                ]);

                $pesan = 'Pengajuan '.$this->labelJenisMutasi($mutasi->jenis).' untuk '.$siswa->nama_lengkap
                    .' telah '.($status === 'disetujui' ? 'disetujui' : 'ditolak').'.';
                $this->notifikasiPengguna((int) $mutasi->id_pengaju, 'Keputusan mutasi siswa', $pesan);

                return $status;
            });
        } catch (QueryException $e) {
            $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
            Log::error('Keputusan mutasi gagal diproses.', [
                'mutasi_id' => $id,
                'jenis_mutasi' => $jenisMutasi,
                'sqlstate' => $sqlState,
            ]);

            $pesan = match (true) {
                str_starts_with($sqlState, '23') => 'Data mutasi bertentangan dengan aturan integritas. Periksa data siswa dan riwayat kelas.',
                $sqlState === '42501' => 'Hak akses database tidak mencukupi untuk memproses mutasi. Hubungi administrator.',
                default => 'Mutasi tidak dapat diproses saat ini. Silakan coba kembali atau hubungi Tata Usaha.',
            };

            return $this->gagal($pesan);
        }

        return redirect()
            ->route($this->ruteMutasi())
            ->with('success', $status === 'disetujui' ? 'Mutasi disetujui.' : 'Mutasi ditolak.');
    }

    private function statusKeanggotaanMutasi(string $jenis): string
    {
        return match ($jenis) {
            'pindah_keluar' => 'pindah',
            'mengundurkan_diri', 'dikeluarkan', 'putus_sekolah', 'meninggal_dunia' => 'keluar',
            default => throw new \LogicException('Jenis mutasi tidak memiliki pemetaan status keanggotaan.'),
        };
    }

    public function mutasiRevisi(Request $request, int $id, PengaturanService $pengaturan)
    {
        $mutasi = DB::table('mutasi_siswa')->where('id_mutasi', $id)->first();
        if (! $mutasi || $mutasi->status !== 'menunggu' || $mutasi->jenis === 'pindah_masuk') {
            return $this->gagal('Pengajuan ini tidak dapat direvisi.');
        }
        if ($request->input('jenis') === 'pindah_masuk') {
            return $this->gagal('Jenis mutasi Pindah Masuk tidak dapat dipilih saat merevisi pengajuan.');
        }

        $data = $this->validasiDataMutasi($request, $pengaturan);

        $pathBaru = $this->simpanLampiranMutasi($request);

        try {
            $pathLama = DB::transaction(function () use ($data, $id, $pathBaru, $request) {
                $mutasi = DB::table('mutasi_siswa')
                    ->where('id_mutasi', $id)
                    ->lockForUpdate()
                    ->first();
                if (! $mutasi || $mutasi->status !== 'menunggu' || $mutasi->jenis === 'pindah_masuk') {
                    throw ValidationException::withMessages(['jenis' => 'Pengajuan ini tidak lagi dapat direvisi.']);
                }

                $siswa = $this->siswaAktifUntukMutasi((int) $data['id_siswa']);
                $lama = [
                    'jenis' => $mutasi->jenis,
                    'id_siswa' => $mutasi->id_siswa,
                    'alasan' => $mutasi->alasan,
                ];
                $update = [
                    'id_siswa' => $siswa->id_siswa,
                    'id_sekolah_tujuan' => null,
                    'nama_sekolah_tujuan' => $data['jenis'] === 'pindah_keluar'
                        ? ($data['sekolah_tujuan'] ?? null)
                        : null,
                    'id_sekolah_asal' => null,
                    'jenis' => $data['jenis'],
                    'alasan' => $data['alasan'],
                    'tanggal_efektif' => $data['tanggal_efektif'],
                ];
                if ($pathBaru) {
                    $update['path_lampiran'] = $pathBaru;
                    $update['nama_lampiran'] = $request->file('lampiran')->getClientOriginalName();
                }

                DB::table('mutasi_siswa')->where('id_mutasi', $id)->update($update);
                AuditCatat::tulis('mutasi_siswa', 'UPDATE', $id, $lama, [
                    'jenis' => $data['jenis'],
                    'id_siswa' => $siswa->id_siswa,
                    'nama_siswa' => $siswa->nama_lengkap,
                    'sekolah_tujuan' => $data['sekolah_tujuan'] ?? null,
                    'status' => 'menunggu',
                ]);

                return $mutasi->path_lampiran;
            });
        } catch (\Throwable $exception) {
            if ($pathBaru) {
                Storage::disk('private')->delete($pathBaru);
            }

            throw $exception;
        }

        if ($pathBaru && $pathLama) {
            Storage::disk('private')->delete($pathLama);
        }

        return redirect()->route('tu.mutasi')->with('success', 'Pengajuan mutasi berhasil direvisi.');
    }

    public function mutasiBatalkan(int $id)
    {
        DB::transaction(function () use ($id) {
            $mutasi = DB::table('mutasi_siswa')
                ->where('id_mutasi', $id)
                ->lockForUpdate()
                ->first();
            if (! $mutasi || $mutasi->status !== 'menunggu' || $mutasi->jenis === 'pindah_masuk') {
                throw ValidationException::withMessages(['mutasi' => 'Pengajuan ini tidak lagi dapat dibatalkan.']);
            }

            DB::table('mutasi_siswa')->where('id_mutasi', $id)->update(['status' => 'dibatalkan']);
            AuditCatat::tulis('mutasi_siswa', 'UPDATE', $id, [
                'status' => 'menunggu',
                'jenis' => $mutasi->jenis,
            ], [
                'status' => 'dibatalkan',
                'jenis' => $mutasi->jenis,
                'id_siswa' => $mutasi->id_siswa,
                'nama_siswa' => DB::table('siswa')->where('id_siswa', $mutasi->id_siswa)->value('nama_lengkap'),
            ]);
        });

        return redirect()->route('tu.mutasi')->with('success', 'Pengajuan mutasi berhasil dibatalkan.');
    }

    public function mutasiLampiran(int $id)
    {
        $mutasi = DB::table('mutasi_siswa')->where('id_mutasi', $id)->first(['path_lampiran', 'nama_lampiran']);
        abort_unless($mutasi?->path_lampiran && Storage::disk('private')->exists($mutasi->path_lampiran), 404);

        return Storage::disk('private')->download($mutasi->path_lampiran, $mutasi->nama_lampiran ?: basename($mutasi->path_lampiran));
    }

    private function validasiDataMutasi(Request $request, PengaturanService $pengaturan): array
    {
        $jenis = (string) $request->input('jenis');
        $jenisValid = ['pindah_masuk', 'pindah_keluar', 'mengundurkan_diri', 'dikeluarkan', 'putus_sekolah', 'meninggal_dunia'];
        $maksMb = max(1, (int) ($pengaturan->nilai('ukuran_berkas_mb') ?: 2));
        $siswaDiperlukan = $jenis !== 'pindah_masuk';

        return $request->validate([
            'jenis' => ['required', Rule::in($jenisValid)],
            'id_siswa' => [$siswaDiperlukan ? 'required' : 'nullable', 'integer', 'exists:siswa,id_siswa'],
            'sekolah_tujuan' => ['nullable', 'string', 'max:255'],
            'id_sekolah_asal' => ['nullable', 'integer', 'exists:sekolah,id_sekolah'],
            'id_kelas_tujuan' => ['required_if:jenis,pindah_masuk', 'nullable', 'integer', 'exists:kelas,id_kelas'],
            'nisn_baru' => [$jenis === 'pindah_masuk' ? 'required' : 'nullable', 'digits:10', Rule::unique('siswa', 'nisn')],
            'nama_siswa_baru' => [$jenis === 'pindah_masuk' ? 'required' : 'nullable', 'string', 'max:150'],
            'jenis_kelamin_baru' => [$jenis === 'pindah_masuk' ? 'required' : 'nullable', Rule::in(['L', 'P'])],
            'tempat_lahir_baru' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir_baru' => ['nullable', 'date', 'before_or_equal:today'],
            'email_baru' => ['nullable', 'email', 'max:255'],
            'no_telepon_baru' => ['nullable', 'string', 'max:30'],
            'tanggal_efektif' => ['required', 'date'],
            'alasan' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.($maksMb * 1024)],
        ], [
            'id_siswa.required' => 'Pilih siswa aktif yang akan dimutasi.',
            'id_siswa.exists' => 'Siswa yang dipilih tidak ditemukan.',
            'nisn_baru.required' => 'NISN wajib diisi untuk siswa pindahan.',
            'nisn_baru.digits' => 'NISN siswa pindahan harus 10 digit.',
            'nisn_baru.unique' => 'NISN tersebut sudah terdaftar.',
            'nama_siswa_baru.required' => 'Nama lengkap siswa pindahan wajib diisi.',
            'jenis_kelamin_baru.required' => 'Pilih jenis kelamin siswa pindahan.',
            'id_kelas_tujuan.required_if' => 'Pilih kelas tujuan untuk siswa pindahan.',
            'tanggal_efektif.required' => 'Tanggal efektif mutasi wajib diisi.',
            'alasan.required' => 'Alasan mutasi wajib diisi.',
            'lampiran.mimes' => 'Lampiran harus berupa PDF, JPG, atau PNG.',
            'lampiran.max' => 'Ukuran lampiran maksimal '.$maksMb.' MB.',
        ]);
    }

    private function simpanLampiranMutasi(Request $request): ?string
    {
        if (! $request->hasFile('lampiran')) {
            return null;
        }

        $path = $request->file('lampiran')->store('mutasi', 'private');
        if (! $path) {
            throw new \RuntimeException('Lampiran mutasi gagal disimpan.');
        }

        return $path;
    }

    private function siswaAktifUntukMutasi(int $idSiswa): object
    {
        $siswa = DB::table('siswa as s')
            ->join('status_siswa as st', 'st.id_status_siswa', '=', 's.id_status_siswa')
            ->where('s.id_siswa', $idSiswa)
            ->where('st.kode_status', 'aktif')
            ->lockForUpdate()
            ->first(['s.id_siswa', 's.nama_lengkap', 's.nisn']);

        if (! $siswa) {
            throw ValidationException::withMessages(['id_siswa' => 'Hanya siswa aktif yang dapat diajukan untuk mutasi.']);
        }

        return $siswa;
    }

    private function daftarkanSiswaMutasiMasuk(array $data): int
    {
        $semester = $this->semesterAktif();
        if (! $semester) {
            throw ValidationException::withMessages(['id_kelas_tujuan' => 'Semester aktif belum tersedia. Atur semester terlebih dahulu.']);
        }

        $kelas = DB::table('kelas')
            ->where('id_kelas', $data['id_kelas_tujuan'])
            ->where('is_aktif', true)
            ->where('id_tahun_ajaran', $semester->id_tahun_ajaran)
            ->first(['id_kelas']);
        if (! $kelas) {
            throw ValidationException::withMessages(['id_kelas_tujuan' => 'Kelas tujuan harus aktif pada tahun ajaran berjalan.']);
        }

        $idRole = DB::table('role')->where('nama_role', 'siswa')->value('id_role');
        $idStatus = DB::table('status_siswa')->where('kode_status', 'aktif')->value('id_status_siswa');
        if (! $idRole || ! $idStatus) {
            throw ValidationException::withMessages(['nisn_baru' => 'Peran atau status siswa belum tersedia di data referensi.']);
        }

        $pengguna = DB::table('pengguna')->where('username', $data['nisn_baru'])->lockForUpdate()->first(['id_pengguna', 'id_role']);
        if ($pengguna && ((int) $pengguna->id_role !== (int) $idRole || DB::table('siswa')->where('id_pengguna', $pengguna->id_pengguna)->exists())) {
            throw ValidationException::withMessages(['nisn_baru' => 'NISN tersebut sudah digunakan oleh akun lain.']);
        }

        $idPengguna = $pengguna?->id_pengguna;
        if ($idPengguna) {
            DB::table('pengguna')->where('id_pengguna', $idPengguna)->update([
                'is_aktif' => true,
                'diubah_pada' => now(),
            ]);
        } else {
            $idPengguna = DB::table('pengguna')->insertGetId([
                'id_role' => $idRole,
                'username' => $data['nisn_baru'],
                'password_hash' => Hash::make($data['nisn_baru']),
                'is_aktif' => true,
                'dibuat_pada' => now(),
                'diubah_pada' => now(),
            ], 'id_pengguna');
        }

        $idSiswa = DB::table('siswa')->insertGetId([
            'id_pengguna' => $idPengguna,
            'id_status_siswa' => $idStatus,
            'id_sekolah_asal' => $data['id_sekolah_asal'] ?? null,
            'nisn' => $data['nisn_baru'],
            'nama_lengkap' => $data['nama_siswa_baru'],
            'jenis_kelamin' => $data['jenis_kelamin_baru'],
            'tempat_lahir' => $data['tempat_lahir_baru'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir_baru'] ?? null,
            'email' => $data['email_baru'] ?? null,
            'no_telepon' => $data['no_telepon_baru'] ?? null,
            'tanggal_masuk' => $data['tanggal_efektif'],
            'dibuat_pada' => now(),
            'diubah_pada' => now(),
        ], 'id_siswa');

        DB::table('keanggotaan_kelas')->insert([
            'id_siswa' => $idSiswa,
            'id_kelas' => $kelas->id_kelas,
            'id_semester' => $semester->id_semester,
            'tanggal_mulai' => $data['tanggal_efektif'],
            'tanggal_selesai' => null,
            'status' => 'aktif',
        ]);

        return (int) $idSiswa;
    }

    private function notifikasiPeran(string $namaPeran, string $judul, string $pesan): void
    {
        $idRole = DB::table('role')->where('nama_role', $namaPeran)->value('id_role');
        if (! $idRole) {
            return;
        }

        $penerima = DB::table('pengguna')
            ->where('id_role', $idRole)
            ->where('is_aktif', true)
            ->pluck('id_pengguna');

        foreach ($penerima as $idPengguna) {
            $this->notifikasiPengguna((int) $idPengguna, $judul, $pesan);
        }
    }

    private function notifikasiPengguna(int $idPengguna, string $judul, string $pesan): void
    {
        DB::table('notifikasi')->insert([
            'id_pengguna' => $idPengguna,
            'judul' => $judul,
            'pesan' => $pesan,
            'dibaca' => false,
            'dibuat_pada' => now(),
        ]);
    }

    private function labelJenisMutasi(string $jenis): string
    {
        return match ($jenis) {
            'pindah_masuk' => 'Pindah Masuk',
            'pindah_keluar' => 'Pindah Keluar',
            'mengundurkan_diri' => 'Mengundurkan Diri',
            'dikeluarkan' => 'Dikeluarkan',
            'putus_sekolah' => 'Putus Sekolah',
            'meninggal_dunia' => 'Meninggal Dunia',
            default => $jenis,
        };
    }

    public function pengingat(Request $request)
    {
        $periode = $this->periodeAktif();

        if (! $periode) {
            return $this->gagal('Tidak ada periode update data yang sedang berlaku. Pengingat tidak dapat dikirim.');
        }

        $judul = 'Pengingat konfirmasi data';
        $pesan = 'Periode update data "'.$periode->nama_periode.'" masih terbuka. Konfirmasi datamu sebelum '
            .Carbon::parse($periode->tanggal_selesai)->locale('id')->translatedFormat('d M Y').'.';

        // Hanya siswa berstatus aktif dengan akun pengguna aktif, dan jangan kirim
        // ulang pengingat identik untuk penerima/periode yang sama meskipun sudah dibaca.
        $belum = DB::table('siswa as s')
            ->join('pengguna as p', 'p.id_pengguna', '=', 's.id_pengguna')
            ->join('status_siswa as st', 'st.id_status_siswa', '=', 's.id_status_siswa')
            ->leftJoin('konfirmasi_data as kd', function ($join) use ($periode) {
                $join->on('kd.id_siswa', '=', 's.id_siswa')
                    ->where('kd.id_periode_update', '=', $periode->id_periode_update);
            })
            ->whereNull('kd.id_konfirmasi')
            ->where('st.kode_status', 'aktif')
            ->where('p.is_aktif', true)
            ->whereNotExists(function ($q) use ($judul, $pesan) {
                $q->select(DB::raw(1))
                    ->from('notifikasi as n')
                    ->whereColumn('n.id_pengguna', 'p.id_pengguna')
                    ->where('n.judul', $judul)
                    ->where('n.pesan', $pesan);
            })
            ->select('s.id_siswa', 'p.id_pengguna')
            ->get();

        if ($belum->isEmpty()) {
            return back()->with('success', 'Tidak ada siswa aktif yang perlu diingatkan. Semua sudah merespons atau sudah pernah diingatkan.');
        }

        foreach ($belum as $s) {
            DB::table('notifikasi')->insert([
                'id_pengguna' => $s->id_pengguna,
                'judul' => $judul,
                'pesan' => $pesan,
                'dibaca' => false,
                'dibuat_pada' => now('Asia/Jakarta'),
            ]);
        }

        return back()->with('success', 'Pengingat dikirim ke '.$belum->count().' siswa yang belum merespons.');
    }

    public function notifikasiBaca(Request $request, $id)
    {
        $idPengguna = session('id_pengguna');

        if (! $idPengguna) {
            abort(404);
        }

        DB::table('notifikasi')
            ->where('id_notifikasi', $id)
            ->where('id_pengguna', $idPengguna)
            ->update(['dibaca' => true]);

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function notifikasiBacaSemua()
    {
        $idPengguna = session('id_pengguna');

        if (! $idPengguna) {
            abort(404);
        }

        $jumlah = DB::table('notifikasi')
            ->where('id_pengguna', $idPengguna)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return back()->with(
            'success',
            $jumlah > 0 ? $jumlah.' notifikasi ditandai sudah dibaca.' : 'Semua notifikasi sudah dibaca.'
        );
    }

    public function periodeSimpan(Request $request)
    {
        $data = $request->validate([
            'id_tahun_ajaran' => ['required', 'integer', 'exists:tahun_ajaran,id_tahun_ajaran'],
            'tgl_buka' => ['required', 'date'],
            'tgl_tutup' => ['required', 'date', 'after_or_equal:tgl_buka'],
        ], [
            'id_tahun_ajaran.required' => 'Pilih tahun ajaran. Tambahkan dulu di Konfigurasi Akademik bila belum ada.',
            'id_tahun_ajaran.exists' => 'Tahun ajaran yang dipilih tidak ditemukan.',
            'tgl_buka.required' => 'Tanggal buka wajib diisi.',
            'tgl_tutup.required' => 'Tanggal tutup wajib diisi.',
            'tgl_tutup.after_or_equal' => 'Tanggal tutup tidak boleh sebelum tanggal buka.',
        ]);

        $tahun = DB::table('tahun_ajaran')
            ->where('id_tahun_ajaran', $data['id_tahun_ajaran'])
            ->first();

        if (! $tahun) {
            return $this->gagal('Tahun ajaran tidak ditemukan.', 'id_tahun_ajaran');
        }

        DB::transaction(function () use ($data, $tahun) {
            DB::table('periode_update')
                ->where('status', 'aktif')
                ->update(['status' => 'ditutup']);

            DB::table('periode_update')->insert([
                'id_tahun_ajaran' => $tahun->id_tahun_ajaran,
                'nama_periode' => 'Update Data '.$tahun->nama_tahun_ajaran,
                'tanggal_mulai' => $data['tgl_buka'],
                'tanggal_selesai' => $data['tgl_tutup'],
                'status' => 'aktif',
                'dibuat_oleh' => session('id_pengguna'),
            ]);
        });

        return redirect()
            ->route('tu.periode')
            ->with('success', 'Periode update berhasil dibuka.');
    }

    public function periodeTutup(int $id)
    {
        $periode = DB::table('periode_update')->where('id_periode_update', $id)->first();

        if (! $periode) {
            return $this->gagal('Periode update tidak ditemukan.');
        }

        DB::table('periode_update')
            ->where('id_periode_update', $id)
            ->update(['status' => 'ditutup']);

        return redirect()
            ->route('tu.periode')
            ->with('success', 'Periode update berhasil ditutup.');
    }

    public function verifikasiSimpan(Request $request, PpdbService $ppdb)
    {
        $data = $request->validate([
            'id_pendaftaran' => ['required', 'integer', 'exists:pendaftaran_ppdb,id_pendaftaran'],
            'hasil' => ['required', 'in:terima,kembalikan,sahkan'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'id_alasan' => [
                'required_if:hasil,kembalikan',
                'nullable',
                'string',
                'max:150',
                Rule::exists('ref_alasan_pengembalian', 'nama_alasan'),
            ],
            'id_dokumen_perbaikan' => ['nullable', 'integer'],
        ], [
            'id_pendaftaran.required' => 'Data pendaftaran tidak dikenali.',
            'id_pendaftaran.exists' => 'Data pendaftaran tidak ditemukan.',
            'hasil.required' => 'Pilih keputusan verifikasi.',
            'hasil.in' => 'Keputusan verifikasi tidak dikenali.',
            'id_alasan.required_if' => 'Pilih alasan pengembalian.',
        ]);

        $pendaftaran = DB::table('pendaftaran_ppdb')
            ->where('id_pendaftaran', $data['id_pendaftaran'])
            ->first();

        if (! $pendaftaran) {
            return $this->gagal('Data pendaftaran tidak ditemukan.', 'id_pendaftaran');
        }

        if ($data['hasil'] === 'sahkan' && ! $pendaftaran->nisn) {
            return $this->gagal('NISN pendaftar belum diisi, akun siswa tidak dapat dibuat.', 'nisn');
        }

        $status = match ($data['hasil']) {
            'terima' => 'diverifikasi',
            'kembalikan' => 'perlu_perbaikan',
            'sahkan' => 'diterima',
        };

        $boleh = [
            'menunggu' => ['diverifikasi', 'perlu_perbaikan'],
            'perlu_perbaikan' => ['diverifikasi'],
            'diverifikasi' => ['diterima', 'perlu_perbaikan'],
            'ditolak' => ['perlu_perbaikan'],
        ];

        if (! in_array($status, $boleh[$pendaftaran->status_pendaftaran] ?? [], true)) {
            return $this->gagal('Status pendaftaran saat ini tidak dapat diubah menjadi keputusan tersebut.', 'hasil');
        }

        if (in_array($status, ['diverifikasi', 'diterima'], true)
            && ! $ppdb->punyaSemuaBerkasId((int) $pendaftaran->id_pendaftaran)) {
            return $this->gagal('Berkas wajib belum lengkap. Semua berkas harus diunggah sebelum diterima atau disahkan.', 'hasil');
        }

        $idAlasan = null;

        if (! empty($data['id_alasan'])) {
            $idAlasan = DB::table('ref_alasan_pengembalian')
                ->where('nama_alasan', $data['id_alasan'])
                ->value('id_alasan');
        }

        $idDokumenPerbaikan = null;

        if ($data['hasil'] === 'kembalikan' && ! empty($data['id_dokumen_perbaikan'])) {
            $dokumenPerbaikan = DB::table('dokumen_siswa')
                ->where('id_dokumen', $data['id_dokumen_perbaikan'])
                ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
                ->first();

            if (! $dokumenPerbaikan) {
                return $this->gagal('Pilih dokumen dari pendaftaran ini untuk diperbaiki.', 'id_dokumen_perbaikan');
            }

            $idDokumenPerbaikan = (int) $dokumenPerbaikan->id_dokumen;
        }

        $catatanVerifikasi = $data['catatan'] ?? null;

        if ($data['hasil'] === 'kembalikan') {
            $catatanVerifikasi = 'Alasan: '.$data['id_alasan'];

            if (! empty($data['catatan'])) {
                $catatanVerifikasi .= ' Catatan: '.$data['catatan'];
            }
        }

        DB::transaction(function () use ($data, $pendaftaran, $status, $idAlasan, $idDokumenPerbaikan, $catatanVerifikasi) {
            DB::table('verifikasi_pendaftaran')->insert([
                'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                'id_petugas' => session('id_pengguna'),
                'id_alasan' => $idAlasan,
                'hasil' => $status,
                'catatan' => $data['catatan'] ?? null,
                'tgl_verifikasi' => now(),
            ]);

            DB::table('pendaftaran_ppdb')
                ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
                ->update([
                    'status_pendaftaran' => $status,
                    'catatan_verifikasi' => $catatanVerifikasi,
                    'id_pengguna_pemeriksa' => session('id_pengguna'),
                    'diperiksa_pada' => now(),
                ]);

            if ($data['hasil'] === 'kembalikan') {
                $catatanDokumen = $data['id_alasan'];

                if (! empty($data['catatan'])) {
                    $catatanDokumen .= ' — '.$data['catatan'];
                }

                if ($pendaftaran->status_pendaftaran === 'ditolak') {
                    DB::table('dokumen_siswa')
                        ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
                        ->where('status_verifikasi', 'ditolak')
                        ->update(['status_verifikasi' => 'menunggu']);
                }

                if ($idDokumenPerbaikan !== null) {
                    DB::table('dokumen_siswa')
                        ->where('id_dokumen', $idDokumenPerbaikan)
                        ->update([
                            'status_verifikasi' => 'perlu_perbaikan',
                            'catatan' => $catatanDokumen,
                        ]);
                }
            } elseif (in_array($data['hasil'], ['terima', 'sahkan'], true)) {
                DB::table('dokumen_siswa')
                    ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
                    ->update(['status_verifikasi' => 'valid']);
            }

            if ($data['hasil'] === 'sahkan') {
                $this->sahkanPendaftaran($pendaftaran);
            }
        });

        $pesan = match ($data['hasil']) {
            'terima' => 'Berkas diterima dan menunggu pengesahan.',
            'kembalikan' => 'Berkas dikembalikan untuk diperbaiki.',
            'sahkan' => 'Pendaftaran disahkan dan akun siswa berhasil dibuat.',
        };

        return redirect()
            ->route('tu.verifikasi', $pendaftaran->id_pendaftaran)
            ->with('success', $pesan);
    }

    public function persetujuanSimpan(Request $request, PengajuanPerubahanService $pengajuanService)
    {
        $data = $request->validate([
            'id_pengajuan' => ['required', 'integer', 'exists:pengajuan_perubahan,id_pengajuan'],
            'keputusan' => ['required', 'in:setujui,tolak'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], [
            'id_pengajuan.required' => 'Pengajuan tidak dikenali.',
            'id_pengajuan.integer' => 'Data contoh tidak dapat diproses. Pengajuan asli dari siswa akan muncul di sini.',
            'id_pengajuan.exists' => 'Pengajuan tidak ditemukan atau data contoh tidak dapat diproses.',
            'keputusan.required' => 'Pilih keputusan terlebih dahulu.',
            'keputusan.in' => 'Keputusan tidak dikenali.',
        ]);

        $status = $data['keputusan'] === 'setujui' ? 'disetujui' : 'ditolak';

        $idPengajuan = (int) $data['id_pengajuan'];

        try {
            DB::transaction(function () use ($idPengajuan, $status, $data, $pengajuanService) {
                // Kunci baris agar dua petugas tidak memproses pengajuan yang sama.
                $pengajuan = DB::table('pengajuan_perubahan')
                    ->where('id_pengajuan', $idPengajuan)
                    ->lockForUpdate()
                    ->first(['id_pengajuan', 'id_siswa', 'status_pengajuan']);

                if (! $pengajuan) {
                    throw ValidationException::withMessages([
                        'id_pengajuan' => 'Pengajuan tidak ditemukan.',
                    ]);
                }

                if ($pengajuan->status_pengajuan !== 'menunggu') {
                    throw ValidationException::withMessages([
                        'id_pengajuan' => 'Pengajuan ini sudah diproses sebelumnya dan tidak dapat diproses ulang.',
                    ]);
                }

                abort_unless(
                    $pengajuanService->waliKelasMenanganiSiswa(
                        (int) session('id_pengguna'),
                        (int) $pengajuan->id_siswa
                    ),
                    404
                );

                if ($status === 'disetujui') {
                    // sp_setujui_pengajuan membaca nama kolom, bukan label tampilan.
                    $this->samakanNamaFieldPengajuan($idPengajuan);
                }

                // Penerapan perubahan disetujui memakai stored procedure agar
                // semua jenis field pada ref_field_data ikut diproses.
                $pengajuanService->putuskan(
                    $idPengajuan,
                    (int) session('id_pengguna'),
                    $status,
                    $data['catatan'] ?? null
                );
            }, 3);
        } catch (QueryException $e) {
            report($e);

            // Tampilkan pesan RAISE EXCEPTION dari stored procedure agar wali kelas tahu penyebabnya.
            $baris = explode("\n", trim((string) ($e->errorInfo[2] ?? '')));
            $pesanDb = trim($baris[0] ?? '');
            $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
            $pesan = match ($sqlState) {
                'P0001' => $pesanDb !== '' ? $pesanDb : 'Pengajuan tidak dapat diproses.',
                '23505' => 'Nilai tertentu pada perubahan sudah digunakan dan harus berbeda.',
                default => 'Pengajuan tidak dapat diproses karena terjadi kendala sistem. Silakan coba kembali atau hubungi Tata Usaha.',
            };

            return $this->gagal($pesan, 'id_pengajuan');
        }

        try {
            $idPenggunaTujuan = DB::table('pengajuan_perubahan as pp')
                ->join('siswa as s', 's.id_siswa', '=', 'pp.id_siswa')
                ->where('pp.id_pengajuan', $idPengajuan)
                ->value('s.id_pengguna');

            if ($idPenggunaTujuan) {
                $catatanNotif = trim((string) ($data['catatan'] ?? ''));
                [$judulNotif, $pesanNotif] = match ($status) {
                    'disetujui' => [
                        'Pengajuan perubahan data disetujui',
                        'Pengajuan perubahan data kamu telah disetujui dan data diperbarui.'.($catatanNotif !== '' ? ' Catatan: '.$catatanNotif : ''),
                    ],
                    default => [
                        'Pengajuan perubahan data ditolak',
                        'Pengajuan perubahan data kamu ditolak.'.($catatanNotif !== '' ? ' Catatan: '.$catatanNotif : ''),
                    ],
                };

                $sudahAda = DB::table('notifikasi')
                    ->where('id_pengguna', $idPenggunaTujuan)
                    ->where('judul', $judulNotif)
                    ->where('pesan', $pesanNotif)
                    ->exists();

                if (! $sudahAda) {
                    DB::table('notifikasi')->insert([
                        'id_pengguna' => $idPenggunaTujuan,
                        'judul' => $judulNotif,
                        'pesan' => $pesanNotif,
                        'dibaca' => false,
                        'dibuat_pada' => now('Asia/Jakarta'),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $pesan = match ($status) {
            'disetujui' => 'Pengajuan disetujui dan data siswa diperbarui.',
            'ditolak' => 'Pengajuan ditolak.',
        };

        return redirect()
            ->route('guru.pengajuan')
            ->with('success', $pesan);
    }

    public function kenaikanSimpan(Request $request, KenaikanKelasService $kenaikan)
    {
        $data = $request->validate([
            'id_tahun_ajaran' => ['required', 'integer', 'exists:tahun_ajaran,id_tahun_ajaran'],
            'id_siswa' => ['nullable', 'array'],
            'id_siswa.*' => ['integer', 'exists:siswa,id_siswa'],
            'tingkat' => ['nullable', 'in:X,XI,XII'],
            'program' => ['nullable', 'in:MIA,IIS'],
        ], [
            'id_tahun_ajaran.required' => 'Pilih tahun ajaran asal.',
            'id_tahun_ajaran.exists' => 'Tahun ajaran tidak ditemukan.',
            'id_siswa.*.exists' => 'Ada siswa yang tidak ditemukan.',
        ]);

        $hasil = $kenaikan->proses(
            (int) $data['id_tahun_ajaran'],
            $data['id_siswa'] ?? [],
            $data['tingkat'] ?? null,
            $data['program'] ?? null
        );

        $total = $hasil['naik'] + $hasil['tidak_naik'] + $hasil['lulus'];

        if ($total === 0) {
            return back()->withInput()->withErrors(['umum' => 'Tidak ada siswa aktif yang dapat diproses pada tahun ajaran ini.']);
        }

        $pesan = "Kenaikan kelas diproses: {$hasil['naik']} naik kelas, {$hasil['tidak_naik']} tidak naik, {$hasil['lulus']} lulus.";
        if ($hasil['tanpa_tujuan'] > 0) {
            $pesan .= " {$hasil['tanpa_tujuan']} siswa dilewati karena kelas tujuan belum tersedia.";
        }

        return redirect()
            ->route('tu.kenaikan', [
                'tahun_ajaran' => $data['id_tahun_ajaran'],
                'tingkat' => $data['tingkat'] ?? null,
                'program' => $data['program'] ?? null,
            ])
            ->with('success', $pesan);
    }

    public function dokumenSimpan(Request $request, int $id, PengaturanService $pengaturan)
    {
        $maksMb = max(1, (int) ($pengaturan->nilai('ukuran_berkas_mb') ?: 2));
        $maksKb = $maksMb * 1024;

        $data = $request->validate([
            'id_siswa' => ['required', 'integer', 'exists:siswa,id_siswa'],
            'id_jenis_dokumen' => [
                'required',
                'string',
                'max:80',
                'exists:jenis_dokumen,nama_dokumen',
                Rule::notIn(PpdbService::CHECKLIST_TIDAK_DIPERIKSA),
            ],
            'nomor_dokumen' => ['nullable', 'string', 'max:100'],
            'berkas' => ['required', 'file', new SafeUploadFile($pengaturan->formatBerkasDiizinkan()), 'max:'.$maksKb],
            'ada_fisik' => ['nullable', 'boolean'],
        ], [
            'id_jenis_dokumen.required' => 'Pilih jenis dokumen.',
            'id_jenis_dokumen.exists' => 'Jenis dokumen tidak terdaftar.',
            'id_jenis_dokumen.not_in' => 'Jenis dokumen tersebut tidak lagi digunakan.',
            'berkas.required' => 'Pilih berkas yang akan diunggah.',
            'berkas.mimes' => 'Berkas harus berupa PDF, JPG, atau PNG.',
            'berkas.max' => 'Ukuran berkas maksimal '.$maksMb.' MB.',
        ]);

        if ((int) $data['id_siswa'] !== $id) {
            return $this->gagal('Data siswa tidak sesuai.', 'id_siswa');
        }

        $file = $request->file('berkas');
        $path = $file->store('dokumen-siswa', 'private');

        $catatan = trim(($data['nomor_dokumen'] ?? '').($request->boolean('ada_fisik') ? ' · fisik sudah diterima' : ''));

        DB::table('dokumen_siswa')->insert([
            'id_siswa' => $id,
            'jenis_dokumen' => $data['id_jenis_dokumen'],
            'nama_file_asli' => $file->getClientOriginalName(),
            'path_file' => $path,
            'status_verifikasi' => 'menunggu',
            'catatan' => $catatan !== '' ? $catatan : null,
            'diunggah_pada' => now(),
        ]);

        return redirect()
            ->route('tu.siswa.dokumen', $id)
            ->with('success', 'Dokumen berhasil diunggah.');
    }

    public function dokumenNonaktif(int $id, int $idDokumen)
    {
        $dokumen = DB::table('dokumen_siswa')
            ->where('id_dokumen', $idDokumen)
            ->where('id_siswa', $id)
            ->first();

        if (! $dokumen) {
            return $this->gagal('Dokumen tidak ditemukan. Dokumen contoh pada tampilan tidak dapat dinonaktifkan.');
        }

        $catatan = trim(($dokumen->catatan ? $dokumen->catatan.' — ' : '').'Dinonaktifkan oleh Tata Usaha');

        DB::transaction(function () use ($id, $dokumen, $idDokumen, $catatan) {
            DB::table('dokumen_siswa')
                ->where('id_dokumen', $idDokumen)
                ->update([
                    'status_verifikasi' => 'ditolak',
                    'catatan' => $catatan,
                ]);

            $idPengguna = DB::table('siswa')->where('id_siswa', $id)->value('id_pengguna');
            if ($idPengguna) {
                $judul = 'Berkas ditolak';
                $pesan = 'Dokumen "'.($dokumen->jenis_dokumen ?? 'Berkas').'" ditolak oleh Tata Usaha. Periksa catatan dan siapkan perbaikan jika diminta.';
                $sudahAda = DB::table('notifikasi')
                    ->where('id_pengguna', $idPengguna)
                    ->where('judul', $judul)
                    ->where('pesan', $pesan)
                    ->exists();
                if (! $sudahAda) {
                    DB::table('notifikasi')->insert([
                        'id_pengguna' => $idPengguna,
                        'judul' => $judul,
                        'pesan' => $pesan,
                        'dibaca' => false,
                        'dibuat_pada' => now('Asia/Jakarta'),
                    ]);
                }
            }
        });

        return redirect()
            ->route('tu.siswa.dokumen', $id)
            ->with('success', 'Dokumen berhasil dinonaktifkan.');
    }
    /* ================= PPDB PUBLIK ================= */

    public function berkasUlang(Request $request)
    {
        $data = $request->validate([
            'kode_pendaftaran' => ['required', 'string', 'max:40'],
            'nisn' => ['required', 'digits:10'],
        ], [
            'kode_pendaftaran.required' => 'Kode pendaftaran tidak ditemukan. Cek status terlebih dahulu.',
            'nisn.required' => 'NISN diperlukan untuk memastikan pengajuan yang akan diperbaiki.',
            'nisn.digits' => 'NISN harus 10 digit.',
        ]);

        $pendaftaran = DB::table('pendaftaran_ppdb')
            ->where('nomor_pendaftaran', $data['kode_pendaftaran'])
            ->where('nisn', $data['nisn'])
            ->first();

        if (! $pendaftaran) {
            return $this->gagal('Pendaftaran tidak dapat diproses. Periksa kembali NISN dan kode pendaftaran.', 'kode_pendaftaran');
        }

        if (! in_array($pendaftaran->status_pendaftaran, ['perlu_perbaikan', 'ditolak'], true)) {
            return $this->gagal('Berkas ulang hanya dapat diunggah jika Tata Usaha meminta perbaikan.', 'kode_pendaftaran');
        }

        if (empty($pendaftaran->token_draf)) {
            return $this->gagal('Formulir perbaikan tidak tersedia. Hubungi Tata Usaha.', 'kode_pendaftaran');
        }

        return redirect()
            ->route('ppdb', ['draf' => $pendaftaran->token_draf])
            ->with('success', 'Silakan perbaiki data atau berkas melalui formulir pendaftaran.');
    }

    /* ================= JADWAL PELAJARAN ================= */

    public function jadwalSimpan(Request $request)
    {
        $data = $request->validate([
            'id_semester' => ['required', 'integer', 'exists:semester,id_semester'],
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
        ], [
            'id_semester.required' => 'Pilih semester terlebih dahulu.',
            'id_semester.exists' => 'Semester yang dipilih tidak ditemukan.',
            'id_kelas.required' => 'Pilih kelas terlebih dahulu.',
            'id_kelas.exists' => 'Kelas yang dipilih tidak ditemukan.',
        ]);

        $isMatriks = $request->has('matriks');

        if ($isMatriks) {
            $slotDefinisi = PageController::daftarSlotJadwal();
            $baris = [];
            foreach ($request->input('matriks', []) as $hari => $slots) {
                if (! is_array($slots)) {
                    continue;
                }
                foreach ($slots as $slotIdx => $cell) {
                    if (! is_array($cell) || ! filled($cell['id_mata_pelajaran'] ?? null)) {
                        continue;
                    }
                    $slotIdx = (int) $slotIdx;
                    if (! isset($slotDefinisi[$slotIdx])) {
                        continue;
                    }

                    $baris[] = [
                        'id_jadwal' => filled($cell['id_jadwal'] ?? null) ? (int) $cell['id_jadwal'] : null,
                        'hari' => (int) $hari,
                        'jam_mulai' => $slotDefinisi[$slotIdx]['mulai'],
                        'jam_selesai' => $slotDefinisi[$slotIdx]['selesai'],
                        'id_mata_pelajaran' => (int) $cell['id_mata_pelajaran'],
                        'nama_guru_mapel' => trim((string) ($cell['nama_guru_mapel'] ?? '')),
                        'id_guru' => filled($cell['id_guru'] ?? null) ? (int) $cell['id_guru'] : null,
                    ];
                }
            }
        } else {
            $baris = collect($request->input('jadwal', []))
                ->filter(fn ($row) => is_array($row)
                    && (filled($row['id_mata_pelajaran'] ?? null)
                        || filled($row['nama_guru_mapel'] ?? null)
                        || filled($row['id_guru'] ?? null)
                        || filled($row['jam_mulai'] ?? null)
                        || filled($row['jam_selesai'] ?? null)
                        || filled($row['ruang'] ?? null)))
                ->values()
                ->all();
        }

        $validator = validator(['jadwal' => $baris], [
            'jadwal' => ['array'],
            'jadwal.*.id_jadwal' => ['nullable', 'integer'],
            'jadwal.*.hari' => ['required', 'integer', 'between:1,7'],
            'jadwal.*.jam_mulai' => ['required', 'date_format:H:i'],
            'jadwal.*.jam_selesai' => ['required', 'date_format:H:i'],
            'jadwal.*.id_mata_pelajaran' => ['required', 'integer', 'exists:mata_pelajaran,id_mata_pelajaran'],
            'jadwal.*.id_guru' => ['nullable', 'integer', 'exists:guru,id_guru'],
            'jadwal.*.nama_guru_mapel' => [$isMatriks ? 'nullable' : 'required', 'string', 'max:150'],
            'jadwal.*.ruang' => ['nullable', 'string', 'max:50'],
        ], [
            'jadwal.*.hari.required' => 'Hari wajib dipilih pada setiap baris jadwal.',
            'jadwal.*.hari.between' => 'Hari yang dipilih tidak valid.',
            'jadwal.*.jam_mulai.required' => 'Jam mulai wajib diisi pada setiap baris jadwal.',
            'jadwal.*.jam_mulai.date_format' => 'Format jam mulai harus HH:MM.',
            'jadwal.*.jam_selesai.required' => 'Jam selesai wajib diisi pada setiap baris jadwal.',
            'jadwal.*.jam_selesai.date_format' => 'Format jam selesai harus HH:MM.',
            'jadwal.*.id_mata_pelajaran.required' => 'Mata pelajaran wajib dipilih pada setiap baris jadwal.',
            'jadwal.*.id_mata_pelajaran.exists' => 'Mata pelajaran yang dipilih tidak ditemukan.',
            'jadwal.*.id_guru.exists' => 'Guru mata pelajaran yang dipilih tidak ditemukan.',
            'jadwal.*.nama_guru_mapel.required' => 'Nama guru mata pelajaran wajib diisi pada setiap baris jadwal.',
            'jadwal.*.nama_guru_mapel.max' => 'Nama guru mata pelajaran maksimal 150 karakter.',
            'jadwal.*.ruang.max' => 'Nama ruang maksimal 50 karakter.',
        ]);

        $validator->after(function ($validator) use ($baris) {
            $terpakai = [];

            foreach ($baris as $i => $row) {
                $mulai = $row['jam_mulai'] ?? '';
                $selesai = $row['jam_selesai'] ?? '';

                if ($mulai === '' || $selesai === '') {
                    continue;
                }

                if ($selesai <= $mulai) {
                    $validator->errors()->add("jadwal.$i.jam_selesai", 'Jam selesai harus lebih besar dari jam mulai.');

                    continue;
                }

                $hari = (int) ($row['hari'] ?? 0);

                foreach ($terpakai as $lain) {
                    if ((int) $lain['hari'] !== $hari) {
                        continue;
                    }

                    if ($mulai < $lain['jam_selesai'] && $lain['jam_mulai'] < $selesai) {
                        $validator->errors()->add("jadwal.$i.jam_mulai", 'Jam ini bentrok dengan baris lain pada hari yang sama.');
                        break;
                    }
                }

                $terpakai[] = [
                    'hari' => $hari,
                    'jam_mulai' => $mulai,
                    'jam_selesai' => $selesai,
                ];
            }
        });

        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        $jadwal = $validator->validated()['jadwal'] ?? [];

        $tersimpan = DB::table('jadwal_pelajaran')
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $data['id_semester'])
            ->pluck('id_jadwal')
            ->map(fn ($id) => (int) $id)
            ->all();

        $dipakai = collect($jadwal)
            ->pluck('id_jadwal')
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->all();

        $bukanMilik = array_diff($dipakai, $tersimpan);

        if ($bukanMilik) {
            return $this->gagal('Ada jadwal yang bukan milik kelas dan semester ini. Muat ulang halaman lalu coba lagi.');
        }

        $dihapus = array_diff($tersimpan, $dipakai);

        if ($dihapus && DB::table('sesi_absensi')->whereIn('id_jadwal', $dihapus)->exists()) {
            return $this->gagal('Ada jadwal yang sudah dipakai untuk absensi sehingga tidak bisa dihapus. Hapus sesi absensi terkait terlebih dahulu.');
        }

        DB::transaction(function () use ($data, $jadwal, $dihapus) {
            $waliGuruId = DB::table('penugasan_wali_kelas')
                ->where('id_kelas', $data['id_kelas'])
                ->where('id_semester', $data['id_semester'])
                ->value('id_guru');
            $defaultGuruId = $waliGuruId ?: DB::table('guru')->where('is_aktif', true)->orderBy('id_guru')->value('id_guru');

            if (! $defaultGuruId) {
                $defaultGuruId = DB::table('guru')->insertGetId([
                    'nama_guru' => 'Guru Pengampu',
                    'is_aktif' => true,
                ], 'id_guru');
            }

            foreach ($jadwal as $i => $row) {
                $namaGuru = trim((string) ($row['nama_guru_mapel'] ?? ''));

                if ($namaGuru === '') {
                    if (filled($row['id_guru'] ?? null)) {
                        $jadwal[$i]['id_guru'] = (int) $row['id_guru'];
                    } else {
                        $jadwal[$i]['id_guru'] = (int) $defaultGuruId;
                    }

                    continue;
                }

                $guruId = DB::table('guru')
                    ->whereRaw('LOWER(nama_guru) = LOWER(?)', [$namaGuru])
                    ->orderBy('id_guru')
                    ->value('id_guru');

                if ($guruId) {
                    $jadwal[$i]['id_guru'] = (int) $guruId;

                    continue;
                }

                $jadwal[$i]['id_guru'] = (int) DB::table('guru')->insertGetId([
                    'nama_guru' => $namaGuru,
                    'is_aktif' => true,
                ], 'id_guru');
            }

            if ($dihapus) {
                DB::table('jadwal_pelajaran')->whereIn('id_jadwal', $dihapus)->delete();
            }

            foreach ($jadwal as $row) {
                $payload = [
                    'id_kelas' => $data['id_kelas'],
                    'id_semester' => $data['id_semester'],
                    'id_mata_pelajaran' => $row['id_mata_pelajaran'],
                    'id_guru' => $row['id_guru'],
                    'hari' => $row['hari'],
                    'jam_mulai' => $row['jam_mulai'],
                    'jam_selesai' => $row['jam_selesai'],
                ];

                // Form TU tidak lagi mengirim ruang; jangan hapus ruang yang sudah ada.
                if (array_key_exists('ruang', $row)) {
                    $payload['ruang'] = trim((string) $row['ruang']) ?: null;
                }

                if (filled($row['id_jadwal'] ?? null)) {
                    DB::table('jadwal_pelajaran')
                        ->where('id_jadwal', $row['id_jadwal'])
                        ->update($payload);
                } else {
                    DB::table('jadwal_pelajaran')->insert($payload);
                }
            }
        });

        return redirect()
            ->route('akademik.jadwal', [
                'semester' => $data['id_semester'],
                'kelas' => $data['id_kelas'],
            ])
            ->with('success', 'Jadwal pelajaran berhasil disimpan.');
    }

    /* ================= PEMBANTU ================= */

    /** Normalisasi kode kehadiran H/S/I/A (atau kode lama) ke kunci rekap. */
    private function kodeKehadiran($kode): string
    {
        $kode = strtolower(trim((string) $kode));

        return match ($kode) {
            'h', 'hadir' => 'hadir',
            'i', 'izin' => 'izin',
            's', 'sakit' => 'sakit',
            'a', 'alpa', 'alpha' => 'alpha',
            default => $kode,
        };
    }

    /** Pastikan status kehadiran dasar tersedia, lalu kembalikan peta kode => id. */
    private function statusKehadiranIds(): array
    {
        $daftar = ['H' => 'Hadir', 'I' => 'Izin', 'S' => 'Sakit', 'A' => 'Alpa'];
        $ids = [];

        foreach ($daftar as $kode => $nama) {
            $baris = DB::table('status_kehadiran')->where('kode', $kode)->first()
                ?? DB::table('status_kehadiran')->where('kode', strtolower($kode))->first();

            $ids[$kode] = $baris
                ? $baris->id_status_hadir
                : DB::table('status_kehadiran')->insertGetId([
                    'kode' => $kode,
                    'nama_status' => $nama,
                ], 'id_status_hadir');
        }

        return $ids;
    }

    /** Halaman mutasi sesuai peran yang sedang masuk. */
    private function ruteMutasi(): string
    {
        return session('role') === 'kepsek' ? 'kepsek.mutasi' : 'tu.mutasi';
    }

    /** Petakan komponen nilai form rapor ke komponen penilaian yang tersedia. */
    private function komponenRapor(): array
    {
        $rows = DB::table('komponen_penilaian')
            ->orderBy('id_komponen')
            ->get(['id_komponen', 'nama_komponen'])
            ->map(fn ($row) => [
                'id' => (int) $row->id_komponen,
                'nama' => mb_strtolower((string) $row->nama_komponen),
            ])
            ->all();

        $pilih = function (array $kataKunci, array $dipakai) use ($rows): ?int {
            foreach ($kataKunci as $kunci) {
                foreach ($rows as $row) {
                    if (! in_array($row['id'], $dipakai, true) && str_contains($row['nama'], $kunci)) {
                        return $row['id'];
                    }
                }
            }

            foreach ($rows as $row) {
                if (! in_array($row['id'], $dipakai, true)) {
                    return $row['id'];
                }
            }

            return null;
        };

        $tugas = $pilih(['tugas'], []);
        $keterampilan = $pilih(
            ['keterampilan', 'praktik', 'praktikum', 'proyek', 'quiz', 'kuis'],
            array_values(array_filter([$tugas], fn ($id) => $id !== null))
        );
        $ujian = $pilih(
            ['ujian', 'uts', 'pts', 'uas', 'pas'],
            array_values(array_filter([$tugas, $keterampilan], fn ($id) => $id !== null))
        );

        return [
            'tugas' => $tugas,
            'keterampilan' => $keterampilan,
            'ujian' => $ujian,
        ];
    }

    /** Bobot tiap komponen yang dipetakan ke form rapor. */
    private function bobotKomponenRapor(array $komponen): array
    {
        $ids = array_values(array_filter($komponen, fn ($id) => $id !== null));
        $bobot = $ids
            ? DB::table('komponen_penilaian')
                ->whereIn('id_komponen', $ids)
                ->pluck('bobot_persen', 'id_komponen')
                ->all()
            : [];

        $hasil = [];

        foreach ($komponen as $jenis => $idKomponen) {
            $hasil[$jenis] = $idKomponen !== null ? (float) ($bobot[$idKomponen] ?? 0) : 0.0;
        }

        return $hasil;
    }

    /** Rata-rata berbobot dari komponen yang sudah diisi. */
    private function nilaiAkhirKomponen(array $nilai, array $bobot): ?float
    {
        $jumlah = 0.0;
        $totalBobot = 0.0;

        foreach ($nilai as $jenis => $angka) {
            if ($angka === null || $angka === '') {
                continue;
            }

            $bobotKomponen = (float) ($bobot[$jenis] ?? 0);

            if ($bobotKomponen <= 0) {
                continue;
            }

            $jumlah += (float) $angka * $bobotKomponen;
            $totalBobot += $bobotKomponen;
        }

        return $totalBobot > 0 ? round($jumlah / $totalBobot, 2) : null;
    }

    private function angkaNilai($nilai): ?float
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return is_numeric($nilai) ? (float) $nilai : null;
    }

    /** Rekap kehadiran satu siswa pada kelas dan semester tertentu. */
    private function rekapKehadiran(int $idSiswa, int $idKelas, int $idSemester): array
    {
        $rows = DB::table('detail_absensi as da')
            ->join('sesi_absensi as sa', 'sa.id_sesi', '=', 'da.id_sesi')
            ->join('status_kehadiran as sh', 'sh.id_status_hadir', '=', 'da.id_status_hadir')
            ->where('da.id_siswa', $idSiswa)
            ->where('sa.id_kelas', $idKelas)
            ->where('sa.id_semester', $idSemester)
            ->select('sh.kode', DB::raw('COUNT(*) AS jumlah'))
            ->groupBy('sh.kode')
            ->pluck('jumlah', 'kode')
            ->all();

        $rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];

        foreach ($rows as $kode => $jumlah) {
            $kode = $this->kodeKehadiran($kode);

            if (isset($rekap[$kode])) {
                $rekap[$kode] += (int) $jumlah;
            }
        }

        return $rekap;
    }

    private function gagal(string $pesan, string $field = 'umum')
    {
        return back()->withInput()->withErrors([$field => $pesan]);
    }

    private function siswaSesi(): ?Siswa
    {
        if (! session('id_pengguna')) {
            return null;
        }

        return Siswa::where('id_pengguna', session('id_pengguna'))->first();
    }

    private function tahunAjaranAktif(): ?object
    {
        return DB::table('tahun_ajaran')
            ->where('is_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->first()
            ?? DB::table('tahun_ajaran')->orderByDesc('tanggal_mulai')->first();
    }

    private function semesterAktif(): ?object
    {
        return DB::table('semester')
            ->where('is_aktif', true)
            ->orderByDesc('tanggal_mulai')
            ->first()
            ?? DB::table('semester')->orderByDesc('tanggal_mulai')->first();
    }

    private function periodeAktif(): ?object
    {
        $hariIni = now()->toDateString();

        return DB::table('periode_update')
            ->where('status', 'aktif')
            ->whereDate('tanggal_mulai', '<=', $hariIni)
            ->whereDate('tanggal_selesai', '>=', $hariIni)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_periode_update')
            ->first();
    }

    /**
     * Samakan nama_field detail pengajuan dengan nama kolom pada ref_field_data.
     * sp_setujui_pengajuan bekerja berdasarkan nama kolom, sedangkan form web
     * lama menyimpan label tampilan (mis. "Nama lengkap").
     */
    private function samakanNamaFieldPengajuan(int $idPengajuan): void
    {
        $detail = DB::table('detail_pengajuan')
            ->where('id_pengajuan', $idPengajuan)
            ->get(['id_detail_pengajuan', 'nama_field']);

        if ($detail->isEmpty()) {
            throw ValidationException::withMessages([
                'id_pengajuan' => 'Pengajuan tidak memiliki detail perubahan.',
            ]);
        }

        foreach ($detail as $d) {
            $nama = trim((string) $d->nama_field);

            $ref = DB::table('ref_field_data')
                ->where('nama_kolom', $nama)
                ->first(['nama_kolom', 'boleh_diubah_siswa']);

            if (! $ref) {
                $ref = DB::table('ref_field_data')
                    ->whereRaw('lower(label_field) = ?', [mb_strtolower($nama)])
                    ->orderBy('id_field')
                    ->first(['nama_kolom', 'boleh_diubah_siswa']);
            }

            if (! $ref || ! $ref->boleh_diubah_siswa) {
                throw ValidationException::withMessages([
                    'id_pengajuan' => 'Field perubahan "'.$nama.'" tidak terdaftar atau tidak diizinkan pada aturan field data.',
                ]);
            }

            $kolom = $ref->nama_kolom;

            if ($kolom !== $nama) {
                DB::table('detail_pengajuan')
                    ->where('id_detail_pengajuan', $d->id_detail_pengajuan)
                    ->update(['nama_field' => $kolom]);
            }
        }
    }

    /** Buat akun pengguna + data siswa dari pendaftaran yang disahkan. */
    private function sahkanPendaftaran(object $pendaftaran): void
    {
        $nisn = $pendaftaran->nisn;

        $siswaLama = DB::table('siswa')->where('nisn', $nisn)->first();

        if ($siswaLama) {
            if ($siswaLama->id_pengguna) {
                DB::table('pengguna')
                    ->where('id_pengguna', $siswaLama->id_pengguna)
                    ->update(['is_aktif' => true, 'diubah_pada' => now()]);
            }

            $this->tempatkanSiswa((int) $siswaLama->id_siswa, $pendaftaran);

            return;
        }

        $idRole = DB::table('role')->where('nama_role', 'siswa')->value('id_role');

        if (! $idRole) {
            throw ValidationException::withMessages([
                'id_pendaftaran' => 'Peran siswa belum tersedia di tabel role.',
            ]);
        }

        $pengguna = DB::table('pengguna')
            ->where('username', $nisn)
            ->first(['id_pengguna', 'id_role', 'is_aktif']);

        if ($pengguna && (int) $pengguna->id_role !== (int) $idRole) {
            throw ValidationException::withMessages([
                'id_pendaftaran' => 'Akun siswa tidak dapat diaktifkan. Hubungi administrator.',
            ]);
        }

        $idPengguna = $pengguna->id_pengguna ?? null;

        if ($idPengguna && DB::table('siswa')->where('id_pengguna', $idPengguna)->exists()) {
            throw ValidationException::withMessages([
                'id_pendaftaran' => 'Akun siswa tidak dapat diaktifkan. Hubungi administrator.',
            ]);
        }

        if ($idPengguna) {
            DB::table('pengguna')
                ->where('id_pengguna', $idPengguna)
                ->update(['is_aktif' => true, 'diubah_pada' => now()]);
        } else {
            $idPengguna = DB::table('pengguna')->insertGetId([
                'id_role' => $idRole,
                'username' => $nisn,
                'password_hash' => Hash::make($nisn),
                'is_aktif' => true,
                'dibuat_pada' => now(),
                'diubah_pada' => now(),
            ], 'id_pengguna');
        }

        $idStatus = DB::table('status_siswa')->where('kode_status', 'aktif')->value('id_status_siswa')
            ?? DB::table('status_siswa')->orderBy('id_status_siswa')->value('id_status_siswa');

        if (! $idStatus) {
            throw ValidationException::withMessages([
                'id_pendaftaran' => 'Status siswa belum tersedia. Tambahkan data status siswa terlebih dahulu.',
            ]);
        }

        $idSiswa = DB::table('siswa')->insertGetId([
            'id_pengguna' => $idPengguna,
            'id_status_siswa' => $idStatus,
            'id_agama' => $pendaftaran->id_agama,
            'nis' => $this->nisBerikutnya($pendaftaran),
            'nisn' => $nisn,
            'nik' => $pendaftaran->nik,
            'nama_lengkap' => $pendaftaran->nama_calon_siswa,
            'jenis_kelamin' => $pendaftaran->jenis_kelamin,
            'tempat_lahir' => $pendaftaran->tempat_lahir,
            'tanggal_lahir' => $pendaftaran->tanggal_lahir,
            'email' => $pendaftaran->email,
            'no_telepon' => $pendaftaran->no_telepon,
            'tanggal_masuk' => now()->toDateString(),
            'dibuat_pada' => now(),
            'diubah_pada' => now(),
        ], 'id_siswa');

        $this->simpanAlamatSiswa($idSiswa, $pendaftaran->id_pendaftaran);
        $this->simpanOrangTuaSiswa($idSiswa, $pendaftaran->id_pendaftaran);

        DB::table('dokumen_siswa')
            ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
            ->update(['id_siswa' => $idSiswa]);

        $this->tempatkanSiswa($idSiswa, $pendaftaran);
    }

    private function simpanAlamatSiswa(int $idSiswa, int $idPendaftaran): void
    {
        $alamat = DB::table('ppdb_alamat')->where('id_pendaftaran', $idPendaftaran)->first();

        if (! $alamat) {
            return;
        }

        DB::table('alamat_siswa')->insert([
            'id_siswa' => $idSiswa,
            'alamat' => $alamat->jalan ?: '-',
            'rt' => $alamat->rt,
            'rw' => $alamat->rw,
            'kelurahan' => $alamat->kelurahan,
            'kecamatan' => $alamat->kecamatan,
            'kota' => $alamat->kota,
            'provinsi' => $alamat->provinsi,
            'kode_pos' => $alamat->kode_pos,
        ]);
    }

    private function simpanOrangTuaSiswa(int $idSiswa, int $idPendaftaran): void
    {
        $orangTua = DB::table('ppdb_orang_tua')
            ->where('id_pendaftaran', $idPendaftaran)
            ->whereIn('hubungan', ['ayah', 'ibu', 'wali'])
            ->get();

        foreach ($orangTua as $row) {
            if (! $row->nama_lengkap) {
                continue;
            }

            DB::table('orang_tua_wali')->insert([
                'id_siswa' => $idSiswa,
                'hubungan' => $row->hubungan,
                'nama_lengkap' => $row->nama_lengkap,
                'id_pekerjaan' => $row->id_pekerjaan,
                'no_telepon' => $row->no_telepon,
                'alamat' => $row->alamat,
            ]);
        }
    }

    private function tempatkanSiswa(int $idSiswa, object $pendaftaran): void
    {
        if (DB::table('keanggotaan_kelas')->where('id_siswa', $idSiswa)->exists()) {
            return;
        }

        $semester = $this->semesterAktif();

        if (! $semester) {
            throw ValidationException::withMessages([
                'id_pendaftaran' => 'Semester aktif belum tersedia. Atur semester terlebih dahulu.',
            ]);
        }

        $kelas = DB::table('kelas')
            ->where('is_aktif', true)
            ->where('id_tahun_ajaran', $semester->id_tahun_ajaran)
            ->when(
                $pendaftaran->id_kelas_diterima,
                fn ($query) => $query->where('id_kelas', $pendaftaran->id_kelas_diterima),
                fn ($query) => $query->where('nama_kelas', $pendaftaran->kelas_tujuan)
            )
            ->first(['id_kelas', 'nama_kelas']);

        if (! $kelas || ($pendaftaran->kelas_tujuan && $kelas->nama_kelas !== $pendaftaran->kelas_tujuan)) {
            throw ValidationException::withMessages([
                'id_pendaftaran' => 'Kelas tujuan pendaftaran tidak tersedia pada tahun ajaran aktif. Minta Tata Usaha menambahkan atau memeriksa kelas yang dipilih.',
            ]);
        }
        $idKelas = (int) $kelas->id_kelas;

        DB::table('pendaftaran_ppdb')
            ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
            ->update(['id_kelas_diterima' => $idKelas]);

        DB::table('keanggotaan_kelas')->updateOrInsert(
            [
                'id_siswa' => $idSiswa,
                'id_semester' => $semester->id_semester,
            ],
            [
                'id_kelas' => $idKelas,
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => null,
                'status' => 'aktif',
            ]
        );
    }

    private function nisBerikutnya(object $pendaftaran): ?string
    {
        if ($pendaftaran->nis) {
            return $pendaftaran->nis;
        }

        $tahun = substr((string) ($pendaftaran->tahun_pelajaran ?: now()->year), 0, 4);
        $panjang = strlen($tahun);
        $terakhir = DB::table('siswa')
            ->where('nis', 'like', $tahun.'%')
            ->pluck('nis')
            ->map(fn ($nis) => (int) substr((string) $nis, $panjang))
            ->max();

        return $tahun.str_pad((string) (((int) $terakhir) + 1), 3, '0', STR_PAD_LEFT);
    }
}
