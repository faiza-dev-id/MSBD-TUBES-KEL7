<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class PendaftaranPpdb extends Model
{
    protected $table = 'pendaftaran_ppdb';
    protected $primaryKey = 'id_pendaftaran';
    public $timestamps = false;
    protected $guarded = ['id_pendaftaran'];
    public function dokumen(): HasMany { return $this->hasMany(DokumenSiswa::class, 'id_pendaftaran', 'id_pendaftaran'); }
    public function dokumenSiswa(): HasMany { return $this->hasMany(DokumenSiswa::class, 'id_pendaftaran', 'id_pendaftaran'); }
}
