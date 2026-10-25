<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Siswa extends Model
{
    protected $table = 'siswa';
    protected $primaryKey = 'id_siswa';
    public $timestamps = false;
    protected $guarded = ['id_siswa'];
    public function pengguna(): BelongsTo { return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna'); }
    public function alamat(): HasMany { return $this->hasMany(AlamatSiswa::class, 'id_siswa', 'id_siswa'); }
    public function orangTuaWali(): HasMany { return $this->hasMany(OrangTuaWali::class, 'id_siswa', 'id_siswa'); }
    public function dokumen(): HasMany { return $this->hasMany(DokumenSiswa::class, 'id_siswa', 'id_siswa'); }
    public function keanggotaanKelas(): HasMany { return $this->hasMany(KeanggotaanKelas::class, 'id_siswa', 'id_siswa'); }
    public function alamatSiswa(): HasMany { return $this->hasMany(AlamatSiswa::class, 'id_siswa', 'id_siswa'); }
    public function dokumenSiswa(): HasMany { return $this->hasMany(DokumenSiswa::class, 'id_siswa', 'id_siswa'); }
    public function konfirmasiData(): HasMany { return $this->hasMany(KonfirmasiData::class, 'id_siswa', 'id_siswa'); }
    public function pengajuanPerubahan(): HasMany { return $this->hasMany(PengajuanPerubahan::class, 'id_siswa', 'id_siswa'); }
}
