<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlamatSiswa extends Model
{
    protected $table = 'alamat_siswa';

    protected $primaryKey = 'id_alamat';

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
}
