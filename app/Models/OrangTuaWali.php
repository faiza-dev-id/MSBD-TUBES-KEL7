<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class OrangTuaWali extends Model
{
    protected $table = 'orang_tua_wali';
    protected $primaryKey = 'id_orang_tua_wali';
    public $timestamps = false;
    protected $guarded = ['id_orang_tua_wali'];
    public function pekerjaan(): BelongsTo { return $this->belongsTo(Pekerjaan::class, 'id_pekerjaan', 'id_pekerjaan'); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa'); }
}
