<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class StatusSiswa extends Model
{
    protected $table = 'status_siswa';
    protected $primaryKey = 'id_status_siswa';
    public $timestamps = false;
    protected $guarded = ['id_status_siswa'];
    public function siswa(): HasMany { return $this->hasMany(Siswa::class, 'id_status_siswa', 'id_status_siswa'); }
}
