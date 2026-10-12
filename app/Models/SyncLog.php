<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['ok', 'message'];
    protected $casts = ['ok' => 'bool', 'created_at' => 'datetime'];
}
