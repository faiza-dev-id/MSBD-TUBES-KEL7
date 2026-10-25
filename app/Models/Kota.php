<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class Kota extends Model {
 protected $table = 'kota'; protected $primaryKey = 'id_kota'; public $timestamps = false; protected $guarded = ['id_kota'];
}
