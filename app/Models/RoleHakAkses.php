<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class RoleHakAkses extends Model
{
    protected $table = 'role_hak_akses';
    protected $primaryKey = 'id_role';
    public $timestamps = false;
    protected $guarded = ['id_role'];
    public function hakAkses(): BelongsTo { return $this->belongsTo(HakAkses::class, 'id_hak_akses', 'id_hak_akses'); }
    public function role(): BelongsTo { return $this->belongsTo(Role::class, 'id_role', 'id_role'); }
}
