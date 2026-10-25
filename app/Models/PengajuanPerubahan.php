<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class PengajuanPerubahan extends Model
{
    protected $table = 'pengajuan_perubahan';
    protected $primaryKey = 'id_pengajuan';
    public $timestamps = false;
    protected $guarded = ['id_pengajuan'];
    public function detail(): HasMany { return $this->hasMany(DetailPengajuan::class, 'id_pengajuan', 'id_pengajuan'); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa'); }
    public function detailPengajuan(): HasMany { return $this->hasMany(DetailPengajuan::class, 'id_pengajuan', 'id_pengajuan'); }
}
