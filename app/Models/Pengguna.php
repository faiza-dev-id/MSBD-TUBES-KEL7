<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';
    public $timestamps = false;

    protected $guarded = [];
    protected $hidden = ['password_hash'];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getRememberTokenName()
    {
        return '';
    }

    public function role()
{
    return $this->belongsTo(Role::class, 'id_role', 'id_role');
}

    public function siswa()
    {
        return $this->hasOne(Siswa::class, 'id_pengguna', 'id_pengguna');
    }

    public function guru()
    {
        return $this->hasOne(Guru::class, 'id_pengguna', 'id_pengguna');
    }

    public function staf()
    {
        return $this->hasOne(Staf::class, 'id_pengguna', 'id_pengguna');
    }

    
    public function namaTampil(): string
    {
        return $this->siswa?->nama_lengkap
            ?? $this->guru?->nama_guru
            ?? $this->staf?->nama_staf
            ?? $this->username;
    }
}
