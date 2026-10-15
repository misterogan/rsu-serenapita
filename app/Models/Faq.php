<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'category', 'show', 'sort'];
    protected $casts = ['show' => 'bool'];
}
