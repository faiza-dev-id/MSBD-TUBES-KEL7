<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrangTuaWali extends Model
{
    protected $table = 'orang_tua_wali';

    protected $primaryKey = 'id_ortu';

    public $timestamps = false;

    protected $guarded = [];

    public function siswa()
    {
        return $this->belongsTo(
            Siswa::class,
            'id_siswa',
            'id_siswa'
        );
    }

    public function pekerjaan()
    {
        return $this->belongsTo(
            Pekerjaan::class,
            'id_pekerjaan',
            'id_pekerjaan'
        );
    }
}
