<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staf extends Model
{
    protected $table = 'staf';
    protected $primaryKey = 'id_staf';
    public $timestamps = false;
    protected $guarded = [];
}
