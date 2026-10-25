<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Agama extends Model
{
    protected $table = 'agama';
    protected $primaryKey = 'id_agama';
    public $timestamps = false;
    protected $guarded = ['id_agama'];
    public function siswa(): HasMany { return $this->hasMany(Siswa::class, 'id_agama', 'id_agama'); }
}
