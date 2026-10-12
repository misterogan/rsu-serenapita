<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN = 'Administrator';

    protected $fillable = ['name', 'permissions'];
    protected $casts = ['permissions' => 'array'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function allows(string $module): bool
    {
        return $this->name === self::ADMIN || in_array($module, $this->permissions ?? [], true);
    }
}
