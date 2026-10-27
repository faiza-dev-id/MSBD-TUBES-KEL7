<?php

namespace App\Services;

use App\Models\PengaturanSistem;
use App\Models\Semester;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * DFD 1.4 Atur Pengaturan Sistem (D27). Tabel pengaturan_sistem berisi pasangan kunci/nilai.
 * "tahun_ajaran_aktif" TIDAK disimpan di sini: sumber kebenarannya tahun_ajaran.is_aktif.
 */
class PengaturanService
{
    public const FORMAT_BOLEH = ['pdf', 'jpg', 'jpeg', 'png'];
    private const CACHE = 'pengaturan_sistem';

    /** Urutan, label, keterangan, dan nilai bawaan tiap pengaturan. */
    public function definisi(): array
    {
        return [
            'nama_sekolah'     => ['Nama sekolah', 'Tampil di header dan laporan', config('sekolah.nama')],
            'max_login_gagal'  => ['Batas login gagal', 'Akun dikunci sementara setelah gagal sebanyak ini', '5'],
            'durasi_kunci'     => ['Lama penguncian (menit)', 'Lama akun terkunci setelah gagal beruntun', '15'],
            'ukuran_berkas_mb' => ['Ukuran maksimal berkas (MB)', 'Batas unggah per berkas', '2'],
            'format_berkas'    => ['Format berkas diizinkan', 'Pisahkan dengan koma. Yang tersedia: pdf, jpg, jpeg, png', 'pdf,jpg,png'],
            'tahun_ajaran_aktif' => ['Tahun ajaran aktif', 'Dipakai sebagai tahun ajaran berjalan di seluruh sistem', null],
        ];
    }

    /** Baca satu nilai (dengan cache). Jatuh ke nilai bawaan bila tabel/DB bermasalah. */
    public function nilai(string $kunci): ?string
    {
        try {
            $semua = Cache::remember(self::CACHE, 300, fn () => PengaturanSistem::query()->pluck('nilai', 'kunci')->all());
        } catch (\Throwable $e) {
            $semua = [];
        }

        return $semua[$kunci] ?? ($this->definisi()[$kunci][2] ?? null);
    }

    /** Bentuk [label, kunci, nilai, keterangan] seperti yang dipakai view. */
    public function untukForm(): array
    {
        $baris = [];
        foreach ($this->definisi() as $kunci => [$label, $ket]) {
            $nilai = $kunci === 'tahun_ajaran_aktif'
                ? TahunAjaran::where('is_aktif', true)->value('nama_tahun_ajaran')
                : $this->nilai($kunci);
            $baris[] = [$label, $kunci, $nilai, $ket];
        }

        return $baris;
    }

    /** Rapikan "PDF, jpg ,png" -> "pdf,jpg,png" (sudah divalidasi controller). */
    public function rapikanFormat(string $v): string
    {
        return collect(explode(',', strtolower($v)))->map(fn ($x) => trim($x))->filter()->unique()->values()->implode(',');
    }

    public function simpan(array $d, ?int $idPengguna): void
    {
        DB::transaction(function () use ($d, $idPengguna) {
            foreach ($this->definisi() as $kunci => [$label, $ket]) {
                if ($kunci === 'tahun_ajaran_aktif' || !array_key_exists($kunci, $d)) continue;

                $nilai = (string) ($kunci === 'format_berkas' ? $this->rapikanFormat($d[$kunci]) : $d[$kunci]);
                $baris = PengaturanSistem::firstOrNew(['kunci' => $kunci]);
                if ($baris->exists && $baris->nilai === $nilai) continue; // tidak berubah: tidak perlu audit

                $baris->fill([
                    'nilai'                => $nilai,
                    'deskripsi'            => $ket,
                    'id_pengguna_pengubah' => $idPengguna,
                    'tgl_diubah'           => now(),
                ])->save();
            }

            if (!empty($d['tahun_ajaran_aktif'])) {
                $this->aktifkanTahunAjaran($d['tahun_ajaran_aktif']);
            }
        });

        Cache::forget(self::CACHE);
    }

    private function aktifkanTahunAjaran(string $nama): void
    {
        $ta = TahunAjaran::where('nama_tahun_ajaran', $nama)->firstOrFail();
        if ($ta->is_aktif) return;

        // Urutan penting: index unik hanya mengizinkan satu baris aktif.
        TahunAjaran::where('is_aktif', true)->get()->each->update(['is_aktif' => false]);
        $ta->update(['is_aktif' => true]);
        // Semester aktif milik tahun ajaran lama tidak berlaku lagi; TU memilih semester baru.
        Semester::where('is_aktif', true)->get()->each->update(['is_aktif' => false]);
    }
}
