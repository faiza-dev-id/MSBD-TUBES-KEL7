<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** Target ERD model. Its backing table is created by the target-extension migration. */
class RefChecklistItem extends Model {
 protected $table = 'ref_checklist_item'; protected $primaryKey = 'id_item'; public $timestamps = false; protected $guarded = ['id_item'];
}
