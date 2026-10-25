<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class LogLogin extends Model
{
    protected $table = 'log_login';
    protected $primaryKey = 'id_log_login';
    public $timestamps = false;
    protected $guarded = ['id_log_login'];
    public function pengguna(): BelongsTo { return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna'); }
}
