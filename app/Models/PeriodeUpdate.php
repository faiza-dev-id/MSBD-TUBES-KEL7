<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class PeriodeUpdate extends Model
{
    protected $table = 'periode_update';
    protected $primaryKey = 'id_periode_update';
    public $timestamps = false;
    protected $guarded = ['id_periode_update'];
    public function pengguna(): BelongsTo { return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id_pengguna'); }
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran'); }
    public function konfirmasiData(): HasMany { return $this->hasMany(KonfirmasiData::class, 'id_periode_update', 'id_periode_update'); }
    public function pengajuanPerubahan(): HasMany { return $this->hasMany(PengajuanPerubahan::class, 'id_periode_update', 'id_periode_update'); }
}
