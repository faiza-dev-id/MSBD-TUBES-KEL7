<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class JobBatch extends Model
{
    protected $table = 'job_batches';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['id'];
}
