<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class LogAudit extends Model
{
    protected $table = 'log_audit';
    protected $primaryKey = 'id_log';
    public $timestamps = false;
    protected $guarded = ['id_log'];
    public function pengguna(): BelongsTo { return $this->belongsTo(Pengguna::class, 'id_pengguna_pengubah', 'id_pengguna'); }
}
