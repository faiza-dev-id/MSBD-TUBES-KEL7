?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';

    protected $primaryKey = 'id_siswa';

    public $timestamps = false;

    protected $guarded = [];

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function agama()
    {
        return $this->belongsTo(Agama::class, 'id_agama', 'id_agama');
    }

    public function alamat()
    {
        return $this->hasMany(AlamatSiswa::class, 'id_siswa', 'id_siswa');
    }

    public function orangTuaWali()
    {
        return $this->hasMany(OrangTuaWali::class, 'id_siswa', 'id_siswa');
    }

    public function dokumen()
    {
        return $this->hasMany(DokumenSiswa::class, 'id_siswa', 'id_siswa');
    }

    public function keanggotaanKelas()
    {
        return $this->hasMany(KeanggotaanKelas::class, 'id_siswa', 'id_siswa');
    }
}