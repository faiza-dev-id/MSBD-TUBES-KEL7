<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class DetailPengajuan extends Model
{
    protected $table = 'detail_pengajuan';
    protected $primaryKey = 'id_detail_pengajuan';
    public $timestamps = false;
    protected $guarded = ['id_detail_pengajuan'];
    public function pengajuanPerubahan(): BelongsTo { return $this->belongsTo(PengajuanPerubahan::class, 'id_pengajuan', 'id_pengajuan'); }
}
