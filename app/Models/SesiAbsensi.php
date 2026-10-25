<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class SesiAbsensi extends Model {
 protected $table = 'sesi_absensi'; protected $primaryKey = 'id_sesi'; public $timestamps = false; protected $guarded = ['id_sesi'];
}
