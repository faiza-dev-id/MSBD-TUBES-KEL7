<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenSiswa extends Model
{
    protected $table = 'dokumen_siswa';

    protected $primaryKey = 'id_dokumen';

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

    public function jenisDokumen()
    {
        return $this->belongsTo(
            JenisDokumen::class,
            'id_jenis_dokumen',
            'id_jenis_dokumen'
        );
    }
}
