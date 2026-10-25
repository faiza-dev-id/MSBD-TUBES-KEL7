<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class FailedJob extends Model
{
    protected $table = 'failed_jobs';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['id'];
}
