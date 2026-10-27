<?php

namespace App\Observers;

use App\Models\LogAudit;
use App\Support\AuditPelaku;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    private const RAHASIA = [
        'password',
        'password_hash',
        'remember_token',
        'token',
        'api_token',
        'nik',
        'no_telepon',
        'email',
        'nis',
        'nisn',
    ];

    public function created(Model $model): void
    {
        $this->catat($model, 'INSERT', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = array_keys($model->getChanges());

        // Abaikan perubahan yang hanya memperbarui waktu login.
        if (
            ! empty($changes) &&
            empty(array_diff($changes, ['terakhir_login']))
        ) {
            return;
        }

        $this->catat(
            $model,
            'UPDATE',
            $model->getRawOriginal(),
            $model->getAttributes()
        );
    }

    public function deleted(Model $model): void
    {
        $this->catat($model, 'DELETE', $model->getAttributes(), null);
    }

    private function catat(
        Model $model,
        string $operasi,
        ?array $lama,
        ?array $baru
    ): void {
        $bersihkan = function (?array $atribut): ?array {
            if ($atribut === null) {
                return null;
            }

            foreach (array_keys($atribut) as $kolom) {
                if (in_array(strtolower($kolom), self::RAHASIA, true)) {
                    unset($atribut[$kolom]);
                }
            }

            return $atribut;
        };

        $pelaku = AuditPelaku::sekarang();

        LogAudit::create([
            'nama_tabel' => $model->getTable(),
            'operasi' => $operasi,
            'id_data' => (string) $model->getKey(),
            'nilai_lama' => $bersihkan($lama),
            'nilai_baru' => $bersihkan($baru),
            'id_pengguna_pengubah' => $pelaku['id'] ?? null,
            'username_pengubah' => $pelaku['username'] ?? null,
            'waktu' => now(),
        ]);
    }
}
