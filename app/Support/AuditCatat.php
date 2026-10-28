<?php

namespace App\Support;

use App\Models\LogAudit;

/** Catat satu baris jejak audit secara manual (untuk perubahan lewat DB::table yang tidak melewati observer). */
class AuditCatat
{
    public static function tulis(string $tabel, string $operasi, int|string|null $idData, ?array $lama, ?array $baru): void
    {
        $p = AuditPelaku::sekarang();

        LogAudit::create([
            'nama_tabel'           => $tabel,
            'operasi'              => $operasi,
            'id_data'              => $idData !== null ? (string) $idData : null,
            'nilai_lama'           => $lama,
            'nilai_baru'           => $baru,
            'id_pengguna_pengubah' => $p['id'] ?? null,
            'username_pengubah'    => $p['username'] ?? null,
            'waktu'                => now(),
        ]);
    }
}
