<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['user_id', 'user_name', 'action'];
    protected $casts = ['created_at' => 'datetime'];

    public static function record(string $action): void
    {
        $user = Auth::user();
        static::create(['user_id' => $user?->id, 'user_name' => $user?->name ?? 'Sistem', 'action' => mb_substr($action, 0, 250)]);
    }
}
