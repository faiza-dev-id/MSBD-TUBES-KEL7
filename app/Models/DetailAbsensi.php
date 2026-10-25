<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class DetailAbsensi extends Model {
 protected $table = 'detail_absensi'; protected $primaryKey = 'id_detail_absensi'; public $timestamps = false; protected $guarded = ['id_detail_absensi'];
}
