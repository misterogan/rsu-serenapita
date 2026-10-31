<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    /** Golongan obat, urutannya sama dengan file Excel persediaan. */
    public const GROUPS = ['INJEKSI', 'SIRUP', 'DROPS', 'SALEP', 'INHALER', 'SUPOSITORIA', 'TABLET', 'KULKAS', 'NARKOTIKA', 'HIGH ALERT', 'CAIRAN', 'SUSU', 'LAINNYA'];

    public const UNITS = ['Ampul', 'Botol', 'Flash', 'Kapsul', 'Pcs', 'Pen', 'Sachet', 'Suppositoria', 'Syringe', 'Tablet', 'Tube', 'Vial'];

    /** Batas hari sebelum tanggal kedaluwarsa untuk diberi peringatan. */
    public const ED_WARN_DAYS = 90;

    protected $fillable = ['name', 'group', 'unit', 'price', 'expired_at', 'is_active'];

    protected $casts = ['price' => 'float', 'expired_at' => 'date', 'is_active' => 'bool'];

    public function entries(): HasMany
    {
        return $this->hasMany(StockEntry::class);
    }

    /** "expired", "soon" (kurang dari ED_WARN_DAYS hari), atau null. */
    public function expiryState(): ?string
    {
        if (! $this->expired_at) {
            return null;
        }
        if ($this->expired_at->lte(today())) {
            return 'expired';
        }

        return $this->expired_at->lte(today()->addDays(self::ED_WARN_DAYS)) ? 'soon' : null;
    }

    /** Urutan golongan seperti di Excel, lalu nama obat. */
    public static function sorted()
    {
        return static::where('is_active', true)->get()
            ->sortBy(function ($m) {
                $order = array_search($m->group, self::GROUPS, true);

                return sprintf('%02d %s', $order === false ? 99 : $order, mb_strtoupper($m->name));
            }, SORT_NATURAL)
            ->values();
    }
}
