<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Role extends Model
{
    protected $table = 'role';
    protected $primaryKey = 'id_role';
    public $timestamps = false;
    protected $guarded = ['id_role'];
    public function pengguna(): HasMany { return $this->hasMany(Pengguna::class, 'id_role', 'id_role'); }
    public function hakAkses(): BelongsToMany { return $this->belongsToMany(HakAkses::class, 'role_hak_akses', 'id_role', 'id_hak_akses'); }
    public function roleHakAkses(): HasMany { return $this->hasMany(RoleHakAkses::class, 'id_role', 'id_role'); }
}
