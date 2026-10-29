<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAudit;
use App\Models\LogLogin;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * DFD 1.5 Pantau Log Aktivitas (Kepala Sekolah): D26 Log Login, D25 Log Audit.
 * Hanya baca. Akses dibatasi middleware role:kepsek di routes/web.php.
 */
class LogController extends Controller
{
    private const ZONA = 'Asia/Jakarta';   // config app.timezone = UTC, jadi dikonversi saat tampil
    private const PER_HALAMAN = 25;

    /** Kolom yang tidak boleh tampil di layar walau tersimpan di jejak audit. */
    private const RAHASIA = ['password_hash', 'password', 'remember_token', 'token', 'api_token'];

    public function login(Request $r)
    {
        $f = $r->validate([
            'cari'   => 'nullable|string|max:100',
            'hasil'  => 'nullable|in:berhasil,gagal',
            'dari'   => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
        ]);

        $log = LogLogin::query()
            ->when($f['cari'] ?? null, fn ($q, $v) => $q->where('username_dicoba', 'ilike', '%'.$this->escapeLike($v).'%'))
            ->when(($f['hasil'] ?? null) === 'berhasil', fn ($q) => $q->where('berhasil', true))
            ->when(($f['hasil'] ?? null) === 'gagal', fn ($q) => $q->where('berhasil', false))
            ->when($f['dari'] ?? null, fn ($q, $v) => $q->where('waktu', '>=', $this->awalHari($v)))
            ->when($f['sampai'] ?? null, fn ($q, $v) => $q->where('waktu', '<', $this->awalHari($v)->addDay()))
            ->orderByDesc('waktu')->orderByDesc('id_log_login')
            ->paginate(self::PER_HALAMAN)->withQueryString()
            ->through(fn ($l) => [
                'waktu'     => $this->tampilWaktu($l->waktu),
                'user'      => $l->username_dicoba,
                'ok'        => (bool) $l->berhasil,
                'ip'        => $l->ip_address ?? '-',
                'perangkat' => $this->ringkasPerangkat($l->user_agent),
            ]);

        return view('admin.log-login', ['log' => $log, 'f' => $f]);
    }

    public function audit(Request $r)
    {
        $f = $r->validate([
            'cari'   => 'nullable|string|max:100',
            'tabel'  => 'nullable|string|max:100',
            'aksi'   => 'nullable|in:INSERT,UPDATE,DELETE',
            'dari'   => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
        ]);

        $log = LogAudit::query()
            ->when($f['cari'] ?? null, fn ($q, $v) => $q->where('username_pengubah', 'ilike', '%'.$this->escapeLike($v).'%'))
            ->when($f['tabel'] ?? null, fn ($q, $v) => $q->where('nama_tabel', $v))
            ->when($f['aksi'] ?? null, fn ($q, $v) => $q->where('operasi', $v))
            ->when($f['dari'] ?? null, fn ($q, $v) => $q->where('waktu', '>=', $this->awalHari($v)))
            ->when($f['sampai'] ?? null, fn ($q, $v) => $q->where('waktu', '<', $this->awalHari($v)->addDay()))
            ->orderByDesc('waktu')->orderByDesc('id_log')
            ->paginate(self::PER_HALAMAN)->withQueryString();

        $daftarTabel = LogAudit::query()->distinct()->orderBy('nama_tabel')->pluck('nama_tabel');

        return view('admin.audit', ['log' => $log, 'f' => $f, 'daftarTabel' => $daftarTabel]);
    }

    /* ---------------- helper ---------------- */

    private function escapeLike(string $v): string
    {
        return addcslashes($v, '%_\\');
    }

    private function awalHari(string $tanggal): Carbon
    {
        return Carbon::parse($tanggal, self::ZONA)->startOfDay()->utc();
    }

    private function tampilWaktu($waktu): string
    {
        return Carbon::parse($waktu)->timezone(self::ZONA)->locale('id')->translatedFormat('d M Y H:i');
    }

    private function json($nilai): ?array
    {
        if (is_array($nilai)) return $nilai;
        if (!is_string($nilai) || $nilai === '') return null;
        $d = json_decode($nilai, true);

        return is_array($d) ? $d : null;
    }

    /** Ringkas "Mozilla/5.0 ... Chrome/..." menjadi "Chrome · Windows". */
    private function ringkasPerangkat(?string $ua): string
    {
        if (!$ua) return '-';
        $browser = match (true) {
            str_contains($ua, 'Edg/')                                  => 'Edge',
            str_contains($ua, 'OPR/')                                  => 'Opera',
            str_contains($ua, 'Firefox/')                              => 'Firefox',
            str_contains($ua, 'Chrome/')                               => 'Chrome',
            str_contains($ua, 'Safari/')                               => 'Safari',
            default                                                    => 'Lainnya',
        };
        $os = match (true) {
            str_contains($ua, 'Android')                               => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')   => 'iOS',
            str_contains($ua, 'Windows')                               => 'Windows',
            str_contains($ua, 'Mac OS')                                => 'macOS',
            str_contains($ua, 'Linux')                                 => 'Linux',
            default                                                    => '',
        };

        return $os ? "$browser · $os" : $browser;
    }

    /** Tampilkan hanya kolom yang berubah (UPDATE) dan sembunyikan kolom rahasia. */
    private function bandingkan(string $op, ?array $lama, ?array $baru): array
    {
        $lama = $lama ?? [];
        $baru = $baru ?? [];

        if ($op === 'UPDATE') {
            $kunci = array_filter(
                array_unique([...array_keys($lama), ...array_keys($baru)]),
                fn ($k) => ($lama[$k] ?? null) !== ($baru[$k] ?? null)
            );
        } else {
            $kunci = array_keys($op === 'INSERT' ? $baru : $lama);
        }

        $ubah = fn (?array $d) => $this->susun($d, $kunci);

        return [$op === 'INSERT' ? '-' : $ubah($lama), $op === 'DELETE' ? '-' : $ubah($baru)];
    }

    private function susun(array $data, array $kunci): string
    {
        $baris = [];
        foreach ($kunci as $k) {
            if (!array_key_exists($k, $data)) continue;
            $v = in_array($k, self::RAHASIA, true) ? '(disembunyikan)'
                : (is_bool($data[$k]) ? ($data[$k] ? 'ya' : 'tidak')
                : (is_array($data[$k]) ? json_encode($data[$k]) : (string) ($data[$k] ?? 'kosong')));
            $baris[] = $k.': '.mb_strimwidth($v, 0, 120, '…');
        }

        return $baris ? implode(' · ', array_slice($baris, 0, 8)) : '-';
    }
}
