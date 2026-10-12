<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['key', 'label', 'route', 'visible', 'locked', 'sort'];
    protected $casts = ['visible' => 'bool', 'locked' => 'bool'];
}
