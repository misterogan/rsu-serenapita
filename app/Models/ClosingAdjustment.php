<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Jurnal penyesuaian / koreksi closing yang dibuat Akunting (memakai akun COA baru). */
class ClosingAdjustment extends Model
{
    public const KINDS = [
        'penyesuaian' => 'Penyesuaian / reklasifikasi (debet = kredit)',
        'koreksi' => 'Koreksi selisih Khanza (boleh sepihak, untuk melengkapi jurnal Khanza yang tidak seimbang)',
    ];

    protected $fillable = ['number', 'date', 'description', 'kind', 'user_id', 'user_name'];

    public function isCorrection(): bool
    {
        return $this->kind === 'koreksi';
    }

    protected $casts = ['date' => 'date'];

    public function lines(): HasMany
    {
        return $this->hasMany(ClosingAdjustmentLine::class, 'adjustment_id');
    }

    public static function nextNumber(): string
    {
        $last = (int) static::query()->where('number', 'like', 'JP-%')->get('number')->map(fn ($a) => (int) substr($a->number, 3))->max();

        return 'JP-'.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
