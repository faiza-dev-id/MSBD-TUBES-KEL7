<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Kelas extends Model
{
    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    public $timestamps = false;
    protected $guarded = ['id_kelas'];
    public function tingkat(): BelongsTo { return $this->belongsTo(Tingkat::class, 'id_tingkat', 'id_tingkat'); }
    public function jadwalPelajaran(): HasMany { return $this->hasMany(JadwalPelajaran::class, 'id_kelas', 'id_kelas'); }
    public function keanggotaanKelas(): HasMany { return $this->hasMany(KeanggotaanKelas::class, 'id_kelas', 'id_kelas'); }
    public function penugasanWaliKelas(): HasMany { return $this->hasMany(PenugasanWaliKelas::class, 'id_kelas', 'id_kelas'); }
}
