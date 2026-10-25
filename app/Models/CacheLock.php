<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class CacheLock extends Model
{
    protected $table = 'cache_locks';
    protected $primaryKey = 'key';
    public $timestamps = false;
    protected $guarded = ['key'];
}
