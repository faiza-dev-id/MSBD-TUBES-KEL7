<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class Pengguna extends User
{
    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';
    public $timestamps = false;
    protected $guarded = ['id_pengguna'];
    use HasApiTokens;
    protected $hidden = ['password_hash'];
    public function getAuthIdentifierName(): string { return 'id_pengguna'; }
    public function getAuthPasswordName(): string { return 'password_hash'; }
    public function getAuthPassword(): string { return $this->password_hash; }
    public function role(): BelongsTo { return $this->belongsTo(Role::class, 'id_role', 'id_role'); }
    public function hasPermission(string $code): bool
    {
        return $this->is_aktif && $this->role()->whereHas('hakAkses', fn ($q) => $q->where('kode_hak_akses', $code))->exists();
    }
    public function guru(): HasMany { return $this->hasMany(Guru::class, 'id_pengguna', 'id_pengguna'); }
    public function logAudit(): HasMany { return $this->hasMany(LogAudit::class, 'id_pengguna_pengubah', 'id_pengguna'); }
    public function logLogin(): HasMany { return $this->hasMany(LogLogin::class, 'id_pengguna', 'id_pengguna'); }
    public function notifikasi(): HasMany { return $this->hasMany(Notifikasi::class, 'id_pengguna', 'id_pengguna'); }
    public function pendaftaranPpdb(): HasMany { return $this->hasMany(PendaftaranPpdb::class, 'id_pengguna_pemeriksa', 'id_pengguna'); }
    public function pengajuanPerubahan(): HasMany { return $this->hasMany(PengajuanPerubahan::class, 'id_pemeriksa', 'id_pengguna'); }
    public function periodeUpdate(): HasMany { return $this->hasMany(PeriodeUpdate::class, 'dibuat_oleh', 'id_pengguna'); }
    public function siswa(): HasMany { return $this->hasMany(Siswa::class, 'id_pengguna', 'id_pengguna'); }
    public function staf(): HasMany { return $this->hasMany(Staf::class, 'id_pengguna', 'id_pengguna'); }
}
