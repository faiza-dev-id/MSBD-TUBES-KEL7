<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class KonfirmasiData extends Model
{
    protected $table = 'konfirmasi_data';
    protected $primaryKey = 'id_konfirmasi';
    public $timestamps = false;
    protected $guarded = ['id_konfirmasi'];
    public function periodeUpdate(): BelongsTo { return $this->belongsTo(PeriodeUpdate::class, 'id_periode_update', 'id_periode_update'); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa'); }
}
