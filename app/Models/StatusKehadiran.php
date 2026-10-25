<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class StatusKehadiran extends Model {
 protected $table = 'status_kehadiran'; protected $primaryKey = 'id_status_hadir'; public $timestamps = false; protected $guarded = ['id_status_hadir'];
}
