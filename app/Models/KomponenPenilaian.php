<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class KomponenPenilaian extends Model {
 protected $table = 'komponen_penilaian'; protected $primaryKey = 'id_komponen'; public $timestamps = false; protected $guarded = ['id_komponen'];
}
