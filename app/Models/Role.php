<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'role';

    protected $primaryKey = 'id_role';

    public $timestamps = false;

    protected $guarded = [];

    public function pengguna()
    {
        return $this->hasMany(Pengguna::class, 'id_role', 'id_role');
    }
}