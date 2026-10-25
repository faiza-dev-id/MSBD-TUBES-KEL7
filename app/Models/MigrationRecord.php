<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class MigrationRecord extends Model
{
    protected $table = 'migrations';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = ['id'];
}
