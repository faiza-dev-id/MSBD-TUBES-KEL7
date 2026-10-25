<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class Jenjang extends Model {
 protected $table = 'jenjang'; protected $primaryKey = 'id_jenjang'; public $timestamps = false; protected $guarded = ['id_jenjang'];
}
