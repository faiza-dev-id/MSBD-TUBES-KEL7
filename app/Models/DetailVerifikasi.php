<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class DetailVerifikasi extends Model {
 protected $table = 'detail_verifikasi'; protected $primaryKey = 'id_verifikasi'; public $timestamps = false; protected $guarded = ['id_verifikasi'];
}
