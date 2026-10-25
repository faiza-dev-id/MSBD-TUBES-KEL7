<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Guru extends Model
{
    protected $table = 'guru';
    protected $primaryKey = 'id_guru';
    public $timestamps = false;
    protected $guarded = ['id_guru'];
    public function pengguna(): BelongsTo { return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna'); }
    public function jadwalPelajaran(): HasMany { return $this->hasMany(JadwalPelajaran::class, 'id_guru', 'id_guru'); }
    public function penugasanWaliKelas(): HasMany { return $this->hasMany(PenugasanWaliKelas::class, 'id_guru', 'id_guru'); }
}
