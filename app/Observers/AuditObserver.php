<?php

namespace App\Observers;

use App\Models\LogAudit;
use App\Support\AuditPelaku;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    private const RAHASIA = ['password', 'password_hash', 'remember_token', 'token', 'api_token'];

    public function created(Model $m): void { $this->catat($m, 'INSERT', null, $m->getAttributes()); }

    public function updated(Model $m): void { $this->catat($m, 'UPDATE', $m->getRawOriginal(), $m->getAttributes()); }

    public function deleted(Model $m): void { $this->catat($m, 'DELETE', $m->getAttributes(), null); }

    private function catat(Model $m, string $op, ?array $lama, ?array $baru): void
    {
        $bersih = fn (?array $a) => $a === null ? null : collect($a)->except(self::RAHASIA)->all();
        $pelaku = AuditPelaku::sekarang();

        LogAudit::create([
            'nama_tabel'           => $m->getTable(),
            'operasi'              => $op,
            'id_data'              => (string) $m->getKey(),
            'nilai_lama'           => $bersih($lama),
            'nilai_baru'           => $bersih($baru),
            'id_pengguna_pengubah' => $pelaku['id'] ?? null,
            'username_pengubah'    => $pelaku['username'] ?? null,
            'waktu'                => now(),
        ]);
    }
}
