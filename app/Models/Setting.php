<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];
    protected $casts = ['value' => 'array'];

    /** Ambil nilai pengaturan. Nilai array digabung dengan default agar kunci baru tetap ada. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->map(
            fn ($v) => is_string($v) ? json_decode($v, true) : $v
        )->all());

        $value = $all[$key] ?? null;
        if ($value === null) {
            return $default;
        }
        if (is_array($value) && is_array($default) && array_is_list($default) === false) {
            return array_replace($default, $value);
        }

        return $value;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }
}
