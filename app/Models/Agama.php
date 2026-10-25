<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agama extends Model
{
    protected $table = 'agama';

    protected $primaryKey = 'id_agama';

    public $timestamps = false;

    protected $guarded = [];

    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'id_agama', 'id_agama');
    }
}
