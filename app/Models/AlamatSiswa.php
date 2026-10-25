<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class AlamatSiswa extends Model
{
    protected $table = 'alamat_siswa';
    protected $primaryKey = 'id_alamat';
    public $timestamps = false;
    protected $guarded = ['id_alamat'];
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa'); }
}
