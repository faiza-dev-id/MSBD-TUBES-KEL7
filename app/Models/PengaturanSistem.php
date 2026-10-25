<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class PengaturanSistem extends Model {
 protected $table = 'pengaturan_sistem'; protected $primaryKey = 'id_pengaturan'; public $timestamps = false; protected $guarded = ['id_pengaturan'];
}
