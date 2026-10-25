<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Semester extends Model
{
    protected $table = 'semester';
    protected $primaryKey = 'id_semester';
    public $timestamps = false;
    protected $guarded = ['id_semester'];
    public function tahunAjaran(): BelongsTo { return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran'); }
    public function jadwalPelajaran(): HasMany { return $this->hasMany(JadwalPelajaran::class, 'id_semester', 'id_semester'); }
    public function keanggotaanKelas(): HasMany { return $this->hasMany(KeanggotaanKelas::class, 'id_semester', 'id_semester'); }
    public function penugasanWaliKelas(): HasMany { return $this->hasMany(PenugasanWaliKelas::class, 'id_semester', 'id_semester'); }
}
