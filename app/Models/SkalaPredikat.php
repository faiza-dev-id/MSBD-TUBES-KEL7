<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class SkalaPredikat extends Model {
 protected $table = 'skala_predikat'; protected $primaryKey = 'id_predikat'; public $timestamps = false; protected $guarded = ['id_predikat'];
}
