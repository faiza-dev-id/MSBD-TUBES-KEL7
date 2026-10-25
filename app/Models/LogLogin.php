<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogLogin extends Model
{
    protected $table = 'log_login';
    protected $primaryKey = 'id_log_login';
    public $timestamps = false;
    protected $guarded = [];
}
