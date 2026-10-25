<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Sekolah extends Model
{
    protected $table = 'sekolah';
    protected $primaryKey = 'id_sekolah';
    public $timestamps = false;
    protected $guarded = ['id_sekolah'];
    public function siswa(): HasMany { return $this->hasMany(Siswa::class, 'id_sekolah_asal', 'id_sekolah'); }
}
