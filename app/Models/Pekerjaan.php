<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Pekerjaan extends Model
{
    protected $table = 'pekerjaan';
    protected $primaryKey = 'id_pekerjaan';
    public $timestamps = false;
    protected $guarded = ['id_pekerjaan'];
    public function orangTuaWali(): HasMany { return $this->hasMany(OrangTuaWali::class, 'id_pekerjaan', 'id_pekerjaan'); }
}
