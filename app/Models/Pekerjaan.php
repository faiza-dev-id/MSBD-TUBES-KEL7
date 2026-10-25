<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pekerjaan extends Model
{
    protected $table = 'pekerjaan';

    protected $primaryKey = 'id_pekerjaan';

    public $timestamps = false;

    protected $guarded = [];

    public function orangTuaWali()
    {
        return $this->hasMany(
            OrangTuaWali::class,
            'id_pekerjaan',
            'id_pekerjaan'
        );
    }
}
