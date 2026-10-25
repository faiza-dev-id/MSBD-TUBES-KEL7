<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class KeanggotaanKelas extends Model
{
    protected $table = 'keanggotaan_kelas';
    protected $primaryKey = 'id_keanggotaan';
    public $timestamps = false;
    protected $guarded = ['id_keanggotaan'];
    public function kelas(): BelongsTo { return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas'); }
    public function semester(): BelongsTo { return $this->belongsTo(Semester::class, 'id_semester', 'id_semester'); }
    public function siswa(): BelongsTo { return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa'); }
}
