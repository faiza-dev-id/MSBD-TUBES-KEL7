<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class HakAkses extends Model
{
    protected $table = 'hak_akses';
    protected $primaryKey = 'id_hak_akses';
    public $timestamps = false;
    protected $guarded = ['id_hak_akses'];
    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class, 'role_hak_akses', 'id_hak_akses', 'id_role'); }
    public function roleHakAkses(): HasMany { return $this->hasMany(RoleHakAkses::class, 'id_hak_akses', 'id_hak_akses'); }
}
