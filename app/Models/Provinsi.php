<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class Provinsi extends Model {
 protected $table = 'provinsi'; protected $primaryKey = 'id_provinsi'; public $timestamps = false; protected $guarded = ['id_provinsi'];
}
