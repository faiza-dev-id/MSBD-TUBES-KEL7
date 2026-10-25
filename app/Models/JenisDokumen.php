<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class JenisDokumen extends Model {
 protected $table = 'jenis_dokumen'; protected $primaryKey = 'id_jenis_dokumen'; public $timestamps = false; protected $guarded = ['id_jenis_dokumen'];
}
