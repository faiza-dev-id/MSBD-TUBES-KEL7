<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class DokumenSiswa extends Model
{
    protected $table = 'dokumen_siswa';
    protected $primaryKey = 'id_dokumen';
    public $timestamps = false;
    protected $guarded = ['id_dokumen'];
    public function pendaftaranPpdb(): BelongsTo { return $this->belongsTo(PendaftaranPpdb::class, 'id_pendaftaran', 'id_pendaftaran'); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa'); }
}
