<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class CetakRapor extends Model {
 protected $table = 'cetak_rapor'; protected $primaryKey = 'id_cetak'; public $timestamps = false; protected $guarded = ['id_cetak'];
}
