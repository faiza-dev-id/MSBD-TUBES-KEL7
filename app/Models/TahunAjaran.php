<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';
    protected $primaryKey = 'id_tahun_ajaran';
    public $timestamps = false;
    protected $guarded = ['id_tahun_ajaran'];

    protected function casts(): array
    {
        return ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'is_aktif' => 'boolean'];
    }

    public function periodeUpdate(): HasMany { return $this->hasMany(PeriodeUpdate::class, 'id_tahun_ajaran', 'id_tahun_ajaran'); }
    public function semester(): HasMany { return $this->hasMany(Semester::class, 'id_tahun_ajaran', 'id_tahun_ajaran'); }
    public function kelas(): HasMany { return $this->hasMany(Kelas::class, 'id_tahun_ajaran', 'id_tahun_ajaran'); }
}
