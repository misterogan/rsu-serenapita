<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    protected $fillable = ['title', 'text', 'tab', 'button', 'link', 'active', 'sort', 'image', 'image_position'];
    protected $casts = ['active' => 'bool'];
}
