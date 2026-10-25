<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class MutasiSiswa extends Model {
 protected $table = 'mutasi_siswa'; protected $primaryKey = 'id_mutasi'; public $timestamps = false; protected $guarded = ['id_mutasi'];
}
