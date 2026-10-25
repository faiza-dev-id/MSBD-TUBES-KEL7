<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class Rapor extends Model {
 protected $table = 'rapor'; protected $primaryKey = 'id_rapor'; public $timestamps = false; protected $guarded = ['id_rapor'];
}
