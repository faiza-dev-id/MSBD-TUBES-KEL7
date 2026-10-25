<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class RefAlasanPengembalian extends Model {
 protected $table = 'ref_alasan_pengembalian'; protected $primaryKey = 'id_alasan'; public $timestamps = false; protected $guarded = ['id_alasan'];
}
