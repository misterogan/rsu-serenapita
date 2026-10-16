<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN = 'Administrator';

    /** Hak akses Dasbor manajemen. Peran dengan hak ini hanya bisa membuka dasbor, bukan menu konten. */
    public const MG_OPS = 'management:ops';
    public const MG_FINANCE = 'management:finance';

    protected $fillable = ['name', 'permissions'];
    protected $casts = ['permissions' => 'array'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function allows(string $module): bool
    {
        if ($module === 'management') {
            return $this->isExecutive();
        }
        if ($this->isExecutive()) {
            return false;
        }

        return $this->name === self::ADMIN || in_array($module, $this->permissions ?? [], true);
    }

    /** Peran manajemen (Komisaris, Direktur, Akunting): hanya Dasbor manajemen, tidak terlihat oleh pengelola konten. */
    public function isExecutive(): bool
    {
        return (bool) array_intersect([self::MG_OPS, self::MG_FINANCE], $this->permissions ?? []);
    }

    /** Bagian dasbor yang boleh dilihat: "ops" (operasional) atau "finance" (keuangan). */
    public function canSee(string $scope): bool
    {
        return in_array($scope === 'finance' ? self::MG_FINANCE : self::MG_OPS, $this->permissions ?? [], true);
    }

    /** @return int[] */
    public static function executiveIds(): array
    {
        return static::all()->filter->isExecutive()->pluck('id')->all();
    }
}
