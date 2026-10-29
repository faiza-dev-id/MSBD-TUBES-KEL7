<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Siswa;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        if (!$siswa) {
            return $this->gagal('Akunmu belum terhubung ke data siswa. Hubungi Tata Usaha.');
        }

        $periode = DB::table('periode_update')
            ->where('id_periode_update', $data['id_periode'])
            ->first();

        if (!$periode || $periode->status !== 'aktif') {
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

    public function pengajuanSiswa(Request $request)
    {
        $data = $request->validate([
            'id_field' => ['required', 'in:alamat,no_hp,nama,nik_kk'],
            'nilai_baru' => ['required', 'string', 'max:500'],
            'bukti' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'id_field.required' => 'Pilih data yang ingin diubah.',
            'nilai_baru.required' => 'Isi nilai baru.',
            'bukti.mimes' => 'Bukti harus berupa PDF, JPG, atau PNG.',
            'bukti.max' => 'Ukuran bukti maksimal 2 MB.',
        ]);

        if (in_array($data['id_field'], ['nama', 'nik_kk'], true) && !$request->hasFile('bukti')) {
            return $this->gagal('Bukti pendukung wajib dilampirkan untuk perubahan nama, NIK, atau KK.', 'bukti');
        }

        $siswa = $this->siswaSesi();

        if (!$siswa) {
            return $this->gagal('Akunmu belum terhubung ke data siswa. Hubungi Tata Usaha.');
        }

        $periode = $this->periodeAktif();

        if (!$periode) {
            return $this->gagal('Periode update data belum dibuka. Hubungi Tata Usaha.');
        }

        $label = [
            'alamat' => 'Alamat',
            'no_hp' => 'No. HP',
            'nama' => 'Nama lengkap',
            'nik_kk' => 'NIK / No. KK',
        ][$data['id_field']];

        $nilaiLama = match ($data['id_field']) {
            'alamat' => DB::table('alamat_siswa')->where('id_siswa', $siswa->id_siswa)->value('alamat'),
            'no_hp' => $siswa->no_telepon,
            'nama' => $siswa->nama_lengkap,
            'nik_kk' => $siswa->nik,
        };

        $path = $request->hasFile('bukti')
            ? $request->file('bukti')->store('bukti-pengajuan', 'public')
            : null;

        DB::transaction(function () use ($data, $siswa, $periode, $label, $nilaiLama, $path) {
            $id = DB::table('pengajuan_perubahan')->insertGetId([
                'id_siswa' => $siswa->id_siswa,
                'id_periode_update' => $periode->id_periode_update,
                'tgl_pengajuan' => now(),
                'status_pengajuan' => 'menunggu',
                'alasan' => 'Perubahan ' . $label,
            ], 'id_pengajuan');

            DB::table('detail_pengajuan')->insert([
                'id_pengajuan' => $id,
                'nama_field' => $label,
                'nilai_lama' => json_encode($nilaiLama),
                'nilai_baru' => json_encode($data['nilai_baru']),
                'bukti_path' => $path,
            ]);
        });

        return redirect()
            ->route('siswa.pengajuan')
            ->with('success', 'Pengajuan perubahan berhasil dikirim dan menunggu persetujuan Tata Usaha.');
    }

    /* ================= GURU ================= */

    public function catatanSimpan(Request $request)
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

        if (!$idGuru) {
            return $this->gagal('Akunmu belum terhubung ke data guru. Hubungi Tata Usaha.');
        }

        $tahun = $this->tahunAjaranAktif();

        if (!$tahun) {
            return $this->gagal('Belum ada tahun ajaran. Tambahkan dulu di Konfigurasi Akademik.');
        }

        DB::table('catatan_wali_kelas')->insert([
            'id_siswa' => $data['id_siswa'],
            'id_guru' => $idGuru,
            'id_tahun_ajaran' => $tahun->id_tahun_ajaran,
            'isi_catatan' => $data['isi_catatan'],
            'tgl_dibuat' => now(),
        ]);

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
            'status.*' => ['required', 'in:hadir,izin,sakit,alpha'],
            'keterangan' => ['nullable', 'array'],
            'keterangan.*' => ['nullable', 'string', 'max:255'],
        ], [
            'id_kelas.required' => 'Pilih kelas terlebih dahulu.',
            'id_kelas.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'tanggal.required' => 'Tanggal presensi wajib diisi.',
            'tanggal.date' => 'Tanggal presensi tidak valid.',
            'status.required' => 'Isi status kehadiran siswa.',
            'status.*.in' => 'Status kehadiran hanya boleh hadir, izin, sakit, atau alpha.',
        ]);

        $idGuru = Guru::where('id_pengguna', session('id_pengguna'))->value('id_guru');

        if (!$idGuru) {
            return $this->gagal('Akunmu belum terhubung ke data guru. Hubungi Tata Usaha.');
        }

        $semester = $this->semesterAktif();

        if (!$semester) {
            return $this->gagal('Belum ada semester. Tambahkan dulu di Konfigurasi Akademik.');
        }

        $menjadiWali = DB::table('penugasan_wali_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->exists();

        if (!$menjadiWali) {
            return $this->gagal('Kamu bukan wali kelas ini pada semester aktif.');
        }

        $jadwal = DB::table('jadwal_pelajaran')
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->first();

        if (!$jadwal) {
            return $this->gagal('Jadwal pelajaran kelas ini belum tersedia. Tata Usaha harus menyusun jadwal terlebih dahulu.');
        }

        $anggota = DB::table('keanggotaan_kelas')
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->where('status', 'aktif')
            ->pluck('id_siswa')
            ->all();

        if (!$anggota) {
            return $this->gagal('Belum ada siswa aktif di kelas ini.');
        }

        $statusIds = $this->statusKehadiranIds();

        DB::transaction(function () use ($data, $jadwal, $anggota, $statusIds, $idGuru) {
            $sesi = DB::table('sesi_absensi')
                ->where('id_jadwal', $jadwal->id_jadwal)
                ->where('tanggal', $data['tanggal'])
                ->first();

            if ($sesi) {
                $idSesi = $sesi->id_sesi;
            } else {
                $idSesi = DB::table('sesi_absensi')->insertGetId([
                    'id_jadwal' => $jadwal->id_jadwal,
                    'id_guru_pencatat' => $idGuru,
                    'tanggal' => $data['tanggal'],
                    'topik_materi' => 'Presensi harian wali kelas',
                    'jam_dibuka' => now()->format('H:i:s'),
                    'status_sesi' => 'dibuka',
                ], 'id_sesi');
            }

            foreach ($anggota as $idSiswa) {
                $kode = $data['status'][$idSiswa] ?? 'hadir';

                if (!isset($statusIds[$kode])) {
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
            ->route('guru.absensi', ['kelas' => $data['id_kelas'], 'tanggal' => $data['tanggal']])
            ->with('success', 'Presensi kelas berhasil disimpan.');
    }

    public function raporSimpan(Request $request)
    {
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'id_siswa' => ['required', 'integer', 'exists:siswa,id_siswa'],
            'nilai' => ['nullable', 'array'],
            'nilai.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'catatan_wali' => ['nullable', 'string', 'max:2000'],
            'status_rapor' => ['nullable', 'in:draft,final'],
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

        if (!$idGuru) {
            return $this->gagal('Akunmu belum terhubung ke data guru. Hubungi Tata Usaha.');
        }

        $semester = $this->semesterAktif();

        if (!$semester) {
            return $this->gagal('Belum ada semester. Tambahkan dulu di Konfigurasi Akademik.');
        }

        $menjadiWali = DB::table('penugasan_wali_kelas')
            ->where('id_guru', $idGuru)
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->exists();

        if (!$menjadiWali) {
            return $this->gagal('Kamu bukan wali kelas ini pada semester aktif.');
        }

        $anggota = DB::table('keanggotaan_kelas')
            ->where('id_kelas', $data['id_kelas'])
            ->where('id_semester', $semester->id_semester)
            ->where('id_siswa', $data['id_siswa'])
            ->first();

        if (!$anggota) {
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

        if (!$terisi) {
            return $this->gagal('Isi minimal satu nilai mata pelajaran.');
        }

        $mapelValid = DB::table('mata_pelajaran')
            ->pluck('id_mata_pelajaran')
            ->map(fn ($id) => (int) $id)
            ->all();

        $terisi = array_filter($terisi, fn ($id) => in_array($id, $mapelValid, true), ARRAY_FILTER_USE_KEY);

        if (!$terisi) {
            return $this->gagal('Mata pelajaran yang dipilih tidak ditemukan.');
        }

        $rataRata = round(array_sum($terisi) / count($terisi), 2);

        $rekapRows = DB::table('detail_absensi as da')
            ->join('sesi_absensi as sa', 'sa.id_sesi', '=', 'da.id_sesi')
            ->join('jadwal_pelajaran as j', 'j.id_jadwal', '=', 'sa.id_jadwal')
            ->join('status_kehadiran as sh', 'sh.id_status_hadir', '=', 'da.id_status_hadir')
            ->where('da.id_siswa', $data['id_siswa'])
            ->where('j.id_kelas', $data['id_kelas'])
            ->where('j.id_semester', $semester->id_semester)
            ->select('sh.kode', DB::raw('COUNT(*) AS jumlah'))
            ->groupBy('sh.kode')
            ->pluck('jumlah', 'kode')
            ->all();

        $rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];

        foreach ($rekapRows as $kode => $jumlah) {
            $kode = strtolower((string) $kode);

            if (isset($rekap[$kode])) {
                $rekap[$kode] += (int) $jumlah;
            }
        }

        $mapelForm = array_map('intval', array_keys($semuaNilai));

        DB::transaction(function () use ($data, $semester, $anggota, $terisi, $rataRata, $rekap, $mapelForm) {
            $rapor = DB::table('rapor')
                ->where('id_anggota', $anggota->id_keanggotaan)
                ->where('id_semester', $semester->id_semester)
                ->first();

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

    /* ================= TATA USAHA ================= */

    public function mutasiSimpan(Request $request)
    {
        $data = $request->validate([
            'id_siswa' => ['required', 'integer', 'exists:siswa,id_siswa'],
            'jenis' => ['required', 'in:pindah_masuk,pindah_keluar,keluar'],
            'alasan' => ['required', 'string', 'max:2000'],
            'id_sekolah_tujuan' => ['nullable', 'integer', 'exists:sekolah,id_sekolah'],
        ], [
            'id_siswa.required' => 'Pilih siswa yang akan dimutasi.',
            'id_siswa.exists' => 'Siswa yang dipilih tidak ditemukan.',
            'jenis.required' => 'Pilih jenis mutasi.',
            'jenis.in' => 'Jenis mutasi tidak dikenali.',
            'alasan.required' => 'Alasan mutasi wajib diisi.',
            'id_sekolah_tujuan.exists' => 'Sekolah tujuan tidak ditemukan.',
        ]);

        if (!session('id_pengguna')) {
            return $this->gagal('Sesi masuk tidak dikenali. Silakan masuk ulang.');
        }

        DB::table('mutasi_siswa')->insert([
            'id_siswa' => $data['id_siswa'],
            'id_pengaju' => session('id_pengguna'),
            'id_sekolah_tujuan' => $data['id_sekolah_tujuan'] ?? null,
            'jenis' => $data['jenis'],
            'alasan' => $data['alasan'],
            'status' => 'menunggu',
            'tgl_pengajuan' => now(),
        ]);

        return redirect()
            ->route($this->ruteMutasi())
            ->with('success', 'Pengajuan mutasi berhasil dikirim dan menunggu keputusan.');
    }

    public function mutasiPutuskan(Request $request, int $id)
    {
        $data = $request->validate([
            'keputusan' => ['required', 'in:setujui,tolak'],
        ], [
            'keputusan.required' => 'Pilih keputusan terlebih dahulu.',
            'keputusan.in' => 'Keputusan tidak dikenali.',
        ]);

        $mutasi = DB::table('mutasi_siswa')->where('id_mutasi', $id)->first();

        if (!$mutasi) {
            return $this->gagal('Pengajuan mutasi tidak ditemukan.');
        }

        if ($mutasi->status !== 'menunggu') {
            return $this->gagal('Pengajuan mutasi ini sudah diputuskan.');
        }

        $status = $data['keputusan'] === 'setujui' ? 'disetujui' : 'ditolak';

        try {
            DB::transaction(function () use ($mutasi, $status) {
                DB::table('mutasi_siswa')
                    ->where('id_mutasi', $mutasi->id_mutasi)
                    ->update([
                        'status' => $status,
                        'id_penyetuju' => session('id_pengguna'),
                        'tgl_keputusan' => now(),
                    ]);

                if ($status !== 'disetujui') {
                    return;
                }

                if (in_array($mutasi->jenis, ['pindah_keluar', 'keluar'], true)) {
                    $kodeStatus = $mutasi->jenis === 'keluar' ? 'keluar' : 'mutasi';
                    $idStatus = DB::table('status_siswa')->where('kode_status', $kodeStatus)->value('id_status_siswa');

                    if ($idStatus) {
                        DB::table('siswa')
                            ->where('id_siswa', $mutasi->id_siswa)
                            ->update(['id_status_siswa' => $idStatus, 'diubah_pada' => now()]);
                    }

                    DB::table('keanggotaan_kelas')
                        ->where('id_siswa', $mutasi->id_siswa)
                        ->where('status', 'aktif')
                        ->update([
                            'status' => $mutasi->jenis === 'keluar' ? 'keluar' : 'pindah',
                            'tanggal_selesai' => now()->toDateString(),
                        ]);
                }

                if ($mutasi->jenis === 'pindah_masuk') {
                    $idStatus = DB::table('status_siswa')->where('kode_status', 'aktif')->value('id_status_siswa');

                    if ($idStatus) {
                        DB::table('siswa')
                            ->where('id_siswa', $mutasi->id_siswa)
                            ->update(['id_status_siswa' => $idStatus, 'diubah_pada' => now()]);
                    }
                }
            });
        } catch (QueryException $e) {
            report($e);

            return $this->gagal('Mutasi tidak dapat diproses karena data terkait tidak lengkap.');
        }

        return redirect()
            ->route($this->ruteMutasi())
            ->with('success', $status === 'disetujui' ? 'Mutasi disetujui.' : 'Mutasi ditolak.');
    }

    public function pengingat(Request $request)
    {
        $periode = $this->periodeAktif();

        if (!$periode) {
            return $this->gagal('Belum ada periode update yang aktif. Buka periode terlebih dahulu.');
        }

        $belum = DB::table('siswa as s')
            ->join('pengguna as p', 'p.id_pengguna', '=', 's.id_pengguna')
            ->leftJoin('konfirmasi_data as kd', function ($join) use ($periode) {
                $join->on('kd.id_siswa', '=', 's.id_siswa')
                    ->where('kd.id_periode_update', '=', $periode->id_periode_update);
            })
            ->whereNull('kd.id_konfirmasi')
            ->select('s.id_siswa', 'p.id_pengguna')
            ->get();

        if ($belum->isEmpty()) {
            return back()->with('success', 'Tidak ada siswa yang perlu diingatkan. Semua sudah merespons.');
        }

        foreach ($belum as $s) {
            DB::table('notifikasi')->insert([
                'id_pengguna' => $s->id_pengguna,
                'judul' => 'Pengingat konfirmasi data',
                'pesan' => 'Periode update data "' . $periode->nama_periode . '" masih terbuka. Konfirmasi datamu sebelum '
                    . \Illuminate\Support\Carbon::parse($periode->tanggal_selesai)->locale('id')->translatedFormat('d M Y') . '.',
                'dibaca' => false,
                'dibuat_pada' => now(),
            ]);
        }

        return back()->with('success', 'Pengingat dikirim ke ' . $belum->count() . ' siswa yang belum merespons.');
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

        if (!$tahun) {
            return $this->gagal('Tahun ajaran tidak ditemukan.', 'id_tahun_ajaran');
        }

        DB::transaction(function () use ($data, $tahun) {
            DB::table('periode_update')
                ->where('status', 'aktif')
                ->update(['status' => 'ditutup']);

            DB::table('periode_update')->insert([
                'id_tahun_ajaran' => $tahun->id_tahun_ajaran,
                'nama_periode' => 'Update Data ' . $tahun->nama_tahun_ajaran,
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

        if (!$periode) {
            return $this->gagal('Periode update tidak ditemukan.');
        }

        DB::table('periode_update')
            ->where('id_periode_update', $id)
            ->update(['status' => 'ditutup']);

        return redirect()
            ->route('tu.periode')
            ->with('success', 'Periode update berhasil ditutup.');
    }

    public function verifikasiSimpan(Request $request)
    {
        $data = $request->validate([
            'id_pendaftaran' => ['required', 'integer', 'exists:pendaftaran_ppdb,id_pendaftaran'],
            'hasil' => ['required', 'in:terima,kembalikan,tolak,sahkan'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'id_alasan' => ['nullable', 'string', 'max:150'],
            'lolos' => ['nullable', 'array'],
            'lolos.*' => ['string', 'max:150'],
        ], [
            'id_pendaftaran.required' => 'Data pendaftaran tidak dikenali.',
            'id_pendaftaran.exists' => 'Data pendaftaran tidak ditemukan.',
            'hasil.required' => 'Pilih keputusan verifikasi.',
            'hasil.in' => 'Keputusan verifikasi tidak dikenali.',
        ]);

        $pendaftaran = DB::table('pendaftaran_ppdb')
            ->where('id_pendaftaran', $data['id_pendaftaran'])
            ->first();

        if (!$pendaftaran) {
            return $this->gagal('Data pendaftaran tidak ditemukan.', 'id_pendaftaran');
        }

        if ($data['hasil'] === 'sahkan' && !$pendaftaran->nisn) {
            return $this->gagal('NISN pendaftar belum diisi, akun siswa tidak dapat dibuat.', 'nisn');
        }

        $status = match ($data['hasil']) {
            'terima' => 'diverifikasi',
            'kembalikan' => 'perlu_perbaikan',
            'tolak' => 'ditolak',
            'sahkan' => 'diterima',
        };

        $idAlasan = null;

        if (!empty($data['id_alasan'])) {
            $idAlasan = DB::table('ref_alasan_pengembalian')
                ->where('nama_alasan', $data['id_alasan'])
                ->value('id_alasan');
        }

        DB::transaction(function () use ($data, $pendaftaran, $status, $idAlasan) {
            $idVerifikasi = DB::table('verifikasi_pendaftaran')->insertGetId([
                'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                'id_petugas' => session('id_pengguna'),
                'id_alasan' => $idAlasan,
                'hasil' => $status,
                'catatan' => $data['catatan'] ?? null,
                'tgl_verifikasi' => now(),
            ], 'id_verifikasi');

            foreach (array_unique($data['lolos'] ?? []) as $namaItem) {
                $idItem = DB::table('ref_checklist_item')
                    ->where('nama_item', $namaItem)
                    ->value('id_item');

                if ($idItem) {
                    DB::table('detail_verifikasi')->insert([
                        'id_verifikasi' => $idVerifikasi,
                        'id_item' => $idItem,
                        'lolos' => true,
                    ]);
                }
            }

            DB::table('pendaftaran_ppdb')
                ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
                ->update([
                    'status_pendaftaran' => $status,
                    'catatan_verifikasi' => $data['catatan'] ?? null,
                    'id_pengguna_pemeriksa' => session('id_pengguna'),
                    'diperiksa_pada' => now(),
                ]);

            if ($data['hasil'] === 'sahkan') {
                $this->sahkanPendaftaran($pendaftaran);
            }
        });

        $pesan = match ($data['hasil']) {
            'terima' => 'Berkas diterima dan menunggu pengesahan.',
            'kembalikan' => 'Berkas dikembalikan untuk diperbaiki.',
            'tolak' => 'Pendaftaran ditolak.',
            'sahkan' => 'Pendaftaran disahkan dan akun siswa berhasil dibuat.',
        };

        return redirect()
            ->route('tu.verifikasi', $pendaftaran->id_pendaftaran)
            ->with('success', $pesan);
    }

    public function persetujuanSimpan(Request $request)
    {
        $data = $request->validate([
            'id_pengajuan' => ['required', 'integer', 'exists:pengajuan_perubahan,id_pengajuan'],
            'keputusan' => ['required', 'in:setujui,kembalikan,tolak'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], [
            'id_pengajuan.required' => 'Pengajuan tidak dikenali.',
            'id_pengajuan.integer' => 'Data contoh tidak dapat diproses. Pengajuan asli dari siswa akan muncul di sini.',
            'id_pengajuan.exists' => 'Pengajuan tidak ditemukan atau data contoh tidak dapat diproses.',
            'keputusan.required' => 'Pilih keputusan terlebih dahulu.',
            'keputusan.in' => 'Keputusan tidak dikenali.',
        ]);

        $pengajuan = DB::table('pengajuan_perubahan')
            ->where('id_pengajuan', $data['id_pengajuan'])
            ->first();

        if (!$pengajuan) {
            return $this->gagal('Pengajuan tidak ditemukan.', 'id_pengajuan');
        }

        $status = match ($data['keputusan']) {
            'setujui' => 'disetujui',
            'kembalikan' => 'perlu_perbaikan',
            'tolak' => 'ditolak',
        };

        try {
            DB::transaction(function () use ($data, $pengajuan, $status) {
                if ($data['keputusan'] === 'setujui') {
                    $this->terapkanPengajuan($pengajuan);
                }

                DB::table('pengajuan_perubahan')
                    ->where('id_pengajuan', $pengajuan->id_pengajuan)
                    ->update([
                        'status_pengajuan' => $status,
                        'catatan_pemeriksa' => $data['catatan'] ?? null,
                        'id_pemeriksa' => session('id_pengguna'),
                        'diputuskan_pada' => now(),
                    ]);
            });
        } catch (QueryException $e) {
            report($e);

            return $this->gagal('Perubahan tidak dapat diterapkan karena data bentrok (misalnya NIK sudah dipakai).');
        }

        $pesan = match ($data['keputusan']) {
            'setujui' => 'Pengajuan disetujui dan data siswa diperbarui.',
            'kembalikan' => 'Pengajuan dikembalikan untuk diperbaiki siswa.',
            'tolak' => 'Pengajuan ditolak.',
        };

        return redirect()
            ->route('tu.persetujuan')
            ->with('success', $pesan);
    }

    public function penempatanSimpan(Request $request)
    {
        $data = $request->validate([
            'id_siswa' => ['required', 'array', 'min:1'],
            'id_siswa.*' => ['integer', 'exists:siswa,id_siswa'],
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'tgl_masuk' => ['required', 'date'],
        ], [
            'id_siswa.required' => 'Pilih minimal satu siswa.',
            'id_siswa.*.exists' => 'Ada siswa yang tidak ditemukan.',
            'id_kelas.required' => 'Pilih kelas tujuan.',
            'id_kelas.exists' => 'Kelas yang dipilih tidak ditemukan. Tambahkan dulu di Konfigurasi Akademik.',
            'tgl_masuk.required' => 'Tanggal masuk wajib diisi.',
        ]);

        $semester = $this->semesterAktif();

        if (!$semester) {
            return $this->gagal('Belum ada semester. Tambahkan dulu di Konfigurasi Akademik.');
        }

        $tertempatkan = 0;

        DB::transaction(function () use ($data, $semester, &$tertempatkan) {
            foreach ($data['id_siswa'] as $idSiswa) {
                DB::table('keanggotaan_kelas')->updateOrInsert(
                    [
                        'id_siswa' => $idSiswa,
                        'id_semester' => $semester->id_semester,
                    ],
                    [
                        'id_kelas' => $data['id_kelas'],
                        'tanggal_mulai' => $data['tgl_masuk'],
                        'tanggal_selesai' => null,
                        'status' => 'aktif',
                    ]
                );

                DB::table('siswa')
                    ->where('id_siswa', $idSiswa)
                    ->whereNull('tanggal_masuk')
                    ->update([
                        'tanggal_masuk' => $data['tgl_masuk'],
                        'diubah_pada' => now(),
                    ]);

                $tertempatkan++;
            }
        });

        return redirect()
            ->route('tu.penempatan')
            ->with('success', $tertempatkan . ' siswa berhasil ditempatkan ke kelas.');
    }

    public function kenaikanSimpan(Request $request)
    {
        $data = $request->validate([
            'id_kelas_asal' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'id_kelas_tujuan' => ['required', 'integer', 'exists:kelas,id_kelas', 'different:id_kelas_asal'],
            'id_siswa' => ['required', 'array', 'min:1'],
            'id_siswa.*' => ['integer', 'exists:siswa,id_siswa'],
        ], [
            'id_kelas_asal.required' => 'Pilih kelas asal.',
            'id_kelas_tujuan.required' => 'Pilih kelas tujuan.',
            'id_kelas_tujuan.different' => 'Kelas tujuan tidak boleh sama dengan kelas asal.',
            'id_siswa.required' => 'Pilih minimal satu siswa yang naik kelas.',
            'id_siswa.*.exists' => 'Ada siswa yang tidak ditemukan.',
        ]);

        $diproses = DB::table('keanggotaan_kelas')
            ->whereIn('id_siswa', $data['id_siswa'])
            ->where('id_kelas', $data['id_kelas_asal'])
            ->where('status', 'aktif')
            ->update([
                'id_kelas' => $data['id_kelas_tujuan'],
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => null,
                'status' => 'aktif',
            ]);

        if ($diproses === 0) {
            return $this->gagal('Tidak ada siswa aktif di kelas asal yang bisa diproses.');
        }

        return redirect()
            ->route('tu.kenaikan')
            ->with('success', $diproses . ' siswa berhasil dinaikkan ke kelas tujuan.');
    }

    public function dokumenSimpan(Request $request, int $id)
    {
        $data = $request->validate([
            'id_siswa' => ['required', 'integer', 'exists:siswa,id_siswa'],
            'id_jenis_dokumen' => ['required', 'string', 'max:80'],
            'nomor_dokumen' => ['nullable', 'string', 'max:100'],
            'berkas' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'ada_fisik' => ['nullable', 'boolean'],
        ], [
            'id_jenis_dokumen.required' => 'Pilih jenis dokumen.',
            'berkas.required' => 'Pilih berkas yang akan diunggah.',
            'berkas.mimes' => 'Berkas harus berupa PDF, JPG, atau PNG.',
            'berkas.max' => 'Ukuran berkas maksimal 2 MB.',
        ]);

        if ((int) $data['id_siswa'] !== $id) {
            return $this->gagal('Data siswa tidak sesuai.', 'id_siswa');
        }

        $file = $request->file('berkas');
        $path = $file->store('dokumen-siswa', 'public');

        $catatan = trim(($data['nomor_dokumen'] ?? '') . ($request->boolean('ada_fisik') ? ' · fisik sudah diterima' : ''));

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

        if (!$dokumen) {
            return $this->gagal('Dokumen tidak ditemukan. Dokumen contoh pada tampilan tidak dapat dinonaktifkan.');
        }

        $catatan = trim(($dokumen->catatan ? $dokumen->catatan . ' · ' : '') . 'Dinonaktifkan oleh Tata Usaha');

        DB::table('dokumen_siswa')
            ->where('id_dokumen', $idDokumen)
            ->update([
                'status_verifikasi' => 'ditolak',
                'catatan' => $catatan,
            ]);

        return redirect()
            ->route('tu.siswa.dokumen', $id)
            ->with('success', 'Dokumen berhasil dinonaktifkan.');
    }

    /* ================= PPDB PUBLIK ================= */

    public function berkasUlang(Request $request)
    {
        $data = $request->validate([
            'kode_pendaftaran' => ['required', 'string', 'max:40'],
            'id_jenis_dokumen' => ['required', 'string', 'max:80'],
            'berkas_ulang' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'kode_pendaftaran.required' => 'Kode pendaftaran tidak ditemukan. Cek status terlebih dahulu.',
            'id_jenis_dokumen.required' => 'Jenis dokumen tidak dikenali.',
            'berkas_ulang.required' => 'Pilih berkas yang akan diunggah ulang.',
            'berkas_ulang.mimes' => 'Berkas harus berupa PDF, JPG, atau PNG.',
            'berkas_ulang.max' => 'Ukuran berkas maksimal 2 MB.',
        ]);

        $pendaftaran = DB::table('pendaftaran_ppdb')
            ->where('nomor_pendaftaran', $data['kode_pendaftaran'])
            ->first();

        if (!$pendaftaran) {
            return $this->gagal('Kode pendaftaran tidak ditemukan.', 'kode_pendaftaran');
        }

        $file = $request->file('berkas_ulang');
        $path = $file->store('dokumen-ppdb', 'public');

        DB::transaction(function () use ($data, $pendaftaran, $file, $path) {
            DB::table('dokumen_siswa')->insert([
                'id_pendaftaran' => $pendaftaran->id_pendaftaran,
                'jenis_dokumen' => $data['id_jenis_dokumen'],
                'nama_file_asli' => $file->getClientOriginalName(),
                'path_file' => $path,
                'status_verifikasi' => 'menunggu',
                'catatan' => 'Unggah ulang oleh pendaftar',
                'diunggah_pada' => now(),
            ]);

            if ($pendaftaran->status_pendaftaran === 'perlu_perbaikan') {
                DB::table('pendaftaran_ppdb')
                    ->where('id_pendaftaran', $pendaftaran->id_pendaftaran)
                    ->update([
                        'status_pendaftaran' => 'menunggu',
                        'catatan_verifikasi' => null,
                        'id_pengguna_pemeriksa' => null,
                        'diperiksa_pada' => null,
                    ]);
            }
        });

        return back()->with('success', 'Berkas berhasil diunggah ulang. Pendaftaran akan diperiksa kembali.');
    }

    /* ================= PEMBANTU ================= */

    /** Pastikan status kehadiran dasar tersedia, lalu kembalikan peta kode => id. */
    private function statusKehadiranIds(): array
    {
        $daftar = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpha' => 'Alpha'];
        $ids = [];

        foreach ($daftar as $kode => $nama) {
            $baris = DB::table('status_kehadiran')->where('kode', $kode)->first();

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

    private function gagal(string $pesan, string $field = 'umum')
    {
        return back()->withInput()->withErrors([$field => $pesan]);
    }

    private function siswaSesi(): ?Siswa
    {
        if (!session('id_pengguna')) {
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
        return DB::table('periode_update')
            ->where('status', 'aktif')
            ->orderByDesc('tanggal_mulai')
            ->first();
    }

    /** Terapkan nilai baru yang disetujui ke data siswa. */
    private function terapkanPengajuan(object $pengajuan): void
    {
        $detail = DB::table('detail_pengajuan')
            ->where('id_pengajuan', $pengajuan->id_pengajuan)
            ->get();

        foreach ($detail as $d) {
            $nilai = $d->nilai_baru;

            if (is_string($nilai)) {
                $decoded = json_decode($nilai, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $nilai = $decoded;
                }
            }

            if ($nilai === null) {
                continue;
            }

            $teks = is_scalar($nilai)
                ? (string) $nilai
                : (json_encode($nilai, JSON_UNESCAPED_UNICODE) ?: null);

            if ($teks === null) {
                continue;
            }

            $field = strtolower((string) $d->nama_field);

            if (str_contains($field, 'alamat')) {
                $ada = DB::table('alamat_siswa')
                    ->where('id_siswa', $pengajuan->id_siswa)
                    ->exists();

                if ($ada) {
                    DB::table('alamat_siswa')
                        ->where('id_siswa', $pengajuan->id_siswa)
                        ->update(['alamat' => $teks]);
                } else {
                    DB::table('alamat_siswa')->insert([
                        'id_siswa' => $pengajuan->id_siswa,
                        'alamat' => $teks,
                    ]);
                }
            } elseif (str_contains($field, 'hp') || str_contains($field, 'telepon')) {
                $this->ubahSiswa($pengajuan->id_siswa, ['no_telepon' => $teks]);
            } elseif (str_contains($field, 'nama')) {
                $this->ubahSiswa($pengajuan->id_siswa, ['nama_lengkap' => $teks]);
            } elseif (str_contains($field, 'nik') || str_contains($field, 'kk')) {
                $this->ubahSiswa($pengajuan->id_siswa, ['nik' => $teks]);
            }
        }
    }

    private function ubahSiswa(int $idSiswa, array $data): void
    {
        DB::table('siswa')
            ->where('id_siswa', $idSiswa)
            ->update($data + ['diubah_pada' => now()]);
    }

    /** Buat akun pengguna + data siswa dari pendaftaran yang disahkan. */
    private function sahkanPendaftaran(object $pendaftaran): void
    {
        $nisn = $pendaftaran->nisn;

        if (DB::table('siswa')->where('nisn', $nisn)->exists()) {
            return;
        }

        $idRole = DB::table('role')->where('nama_role', 'siswa')->value('id_role');

        if (!$idRole) {
            throw ValidationException::withMessages([
                'id_pendaftaran' => 'Peran siswa belum tersedia di tabel role.',
            ]);
        }

        $idPengguna = DB::table('pengguna')->where('username', $nisn)->value('id_pengguna');

        if (!$idPengguna) {
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

        if (!$idStatus) {
            throw ValidationException::withMessages([
                'id_pendaftaran' => 'Status siswa belum tersedia. Tambahkan data status siswa terlebih dahulu.',
            ]);
        }

        DB::table('siswa')->insert([
            'id_pengguna' => $idPengguna,
            'id_status_siswa' => $idStatus,
            'nis' => null,
            'nisn' => $nisn,
            'nik' => null,
            'nama_lengkap' => $pendaftaran->nama_calon_siswa,
            'jenis_kelamin' => null,
            'tempat_lahir' => null,
            'tanggal_lahir' => $pendaftaran->tanggal_lahir,
            'email' => null,
            'no_telepon' => $pendaftaran->no_telepon,
            'tanggal_masuk' => now()->toDateString(),
            'dibuat_pada' => now(),
            'diubah_pada' => now(),
        ]);
    }
}
