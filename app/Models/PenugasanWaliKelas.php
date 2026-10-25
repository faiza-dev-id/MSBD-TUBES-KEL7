<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class PenugasanWaliKelas extends Model
{
    protected $table = 'penugasan_wali_kelas';
    protected $primaryKey = 'id_penugasan_wali_kelas';
    public $timestamps = false;
    protected $guarded = ['id_penugasan_wali_kelas'];
    public function guru(): BelongsTo { return $this->belongsTo(Guru::class, 'id_guru', 'id_guru'); }
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas'); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class, 'id_semester', 'id_semester'); }
}
