<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;

class Pengguna extends User
{
    private const HAK_AKSES_PER_ROLE = [
        'tu' => ['dashboard.view', 'siswa.view', 'siswa.update.approve', 'ppdb.verify', 'akademik.manage'],
        'kepsek' => ['dashboard.view', 'siswa.view', 'user.manage', 'audit.view', 'loginlog.view'],
        'wali_kelas' => ['dashboard.view', 'siswa.view', 'siswa.update.approve'],
        'siswa' => ['siswa.update.request'],
    ];

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    public $timestamps = false;

    protected $guarded = ['id_pengguna'];

    use HasApiTokens;

    protected $hidden = ['password_hash'];

    public function getAuthIdentifierName(): string
    {
        return 'id_pengguna';
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    /**
     * Cari akun untuk login berdasarkan username, atau NISN untuk akun siswa.
     */
    public static function findForLogin(string $identitas): ?self
    {
        $identitas = trim($identitas);

        if ($identitas === '') {
            return null;
        }

        $user = static::with('role')
            ->where('username', $identitas)
            ->first();

        if ($user) {
            return $user;
        }

        $idPengguna = Siswa::query()
            ->where('nisn', $identitas)
            ->whereNotNull('id_pengguna')
            ->value('id_pengguna');

        if (! $idPengguna) {
            return null;
        }

        $user = static::with('role')
            ->where('id_pengguna', $idPengguna)
            ->first();

        return $user?->role?->kode() === 'siswa' ? $user : null;
    }

    public function hasPermission(string $code): bool
    {
        $role = $this->role;

        return $this->is_aktif
            && $role !== null
            && in_array($code, self::HAK_AKSES_PER_ROLE[$role->kode()] ?? [], true);
    }

    public function guru(): HasMany
    {
        return $this->hasMany(Guru::class, 'id_pengguna', 'id_pengguna');
    }

    public function logAudit(): HasMany
    {
        return $this->hasMany(LogAudit::class, 'id_pengguna_pengubah', 'id_pengguna');
    }

    public function logLogin(): HasMany
    {
        return $this->hasMany(LogLogin::class, 'id_pengguna', 'id_pengguna');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_pengguna', 'id_pengguna');
    }

    public function pendaftaranPpdb(): HasMany
    {
        return $this->hasMany(PendaftaranPpdb::class, 'id_pengguna_pemeriksa', 'id_pengguna');
    }

    public function pengajuanPerubahan(): HasMany
    {
        return $this->hasMany(PengajuanPerubahan::class, 'id_pemeriksa', 'id_pengguna');
    }

    public function periodeUpdate(): HasMany
    {
        return $this->hasMany(PeriodeUpdate::class, 'dibuat_oleh', 'id_pengguna');
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'id_pengguna', 'id_pengguna');
    }

    public function staf(): HasMany
    {
        return $this->hasMany(Staf::class, 'id_pengguna', 'id_pengguna');
    }
}
