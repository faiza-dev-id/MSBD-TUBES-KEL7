<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAudit extends Model
{
    protected $table = 'log_audit';
    protected $primaryKey = 'id_log';
    public $timestamps = false;
    protected $guarded = ['id_log'];

    // PERBAIKAN: kolom jsonb wajib di-cast, kalau tidak insert array akan error 500.
    protected function casts(): array
    {
        return ['nilai_lama' => 'array', 'nilai_baru' => 'array', 'waktu' => 'datetime'];
    }

    public function pengguna(): BelongsTo { return $this->belongsTo(Pengguna::class, 'id_pengguna_pengubah', 'id_pengguna'); }
}
