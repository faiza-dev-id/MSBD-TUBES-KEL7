<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class JadwalPelajaran extends Model
{
    protected $table = 'jadwal_pelajaran';
    protected $primaryKey = 'id_jadwal';
    public $timestamps = false;
    protected $guarded = ['id_jadwal'];
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas'); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class, 'id_semester', 'id_semester'); }
    public function guru(): BelongsTo { return $this->belongsTo(Guru::class, 'id_guru', 'id_guru'); }
    public function mataPelajaran(): BelongsTo { return $this->belongsTo(MataPelajaran::class, 'id_mata_pelajaran', 'id_mata_pelajaran'); }
}
