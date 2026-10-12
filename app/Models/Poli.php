<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poli extends Model
{
    protected $fillable = ['simrs_code', 'name', 'description', 'active', 'featured', 'sort'];
    protected $casts = ['active' => 'bool', 'featured' => 'bool'];

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }
}
