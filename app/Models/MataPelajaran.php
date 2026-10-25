<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';
    protected $primaryKey = 'id_mata_pelajaran';
    public $timestamps = false;
    protected $guarded = ['id_mata_pelajaran'];
    public function jadwalPelajaran(): HasMany { return $this->hasMany(JadwalPelajaran::class, 'id_mata_pelajaran', 'id_mata_pelajaran'); }
}
