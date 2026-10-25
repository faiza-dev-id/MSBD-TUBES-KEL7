<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class NilaiSiswa extends Model {
 protected $table = 'nilai_siswa'; protected $primaryKey = 'id_nilai'; public $timestamps = false; protected $guarded = ['id_nilai'];
}
