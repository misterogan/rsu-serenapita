<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role_id', 'is_active', 'last_login_at'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'bool',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function canModule(string $module): bool
    {
        return (bool) $this->role?->allows($module);
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    }
}
