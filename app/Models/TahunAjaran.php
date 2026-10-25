<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';
    protected $primaryKey = 'id_tahun_ajaran';
    public $timestamps = false;
    protected $guarded = ['id_tahun_ajaran'];
    public function periodeUpdate(): HasMany { return $this->hasMany(PeriodeUpdate::class, 'id_tahun_ajaran', 'id_tahun_ajaran'); }
    public function semester(): HasMany { return $this->hasMany(Semester::class, 'id_tahun_ajaran', 'id_tahun_ajaran'); }
}
