<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class CacheEntry extends Model
{
    protected $table = 'cache';
    protected $primaryKey = 'key';
    public $timestamps = false;
    protected $guarded = ['key'];
}
