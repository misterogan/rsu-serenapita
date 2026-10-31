<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    /** Golongan obat, urutannya sama dengan file Excel persediaan. */
    public const GROUPS = ['INJEKSI', 'SIRUP', 'DROPS', 'SALEP', 'INHALER', 'SUPOSITORIA', 'TABLET', 'KULKAS', 'NARKOTIKA', 'HIGH ALERT', 'CAIRAN', 'SUSU', 'ALKES', 'LAINNYA'];

    public const UNITS = ['Ampul', 'Bag', 'Botol', 'Box', 'Bungkus', 'CC', 'Flash', 'Galon', 'Gulung', 'Jerigen', 'Kaleng', 'Kapsul', 'Liter', 'Pack', 'Pcs', 'Pen', 'Pot', 'Sachet', 'Set', 'Suppositoria', 'Syringe', 'Tablet', 'Tabung', 'Tetes', 'Tube', 'Vial'];

    /** Satuan dari SIMRS yang ditulis berbeda-beda, diseragamkan ke UNITS. */
    public const UNIT_ALIASES = ['CAP' => 'Kapsul', 'CAPSUL' => 'Kapsul', 'KAPSUL' => 'Kapsul', 'TAB' => 'Tablet', 'FLES' => 'Flash', 'FLEXPEN' => 'Pen',
        'PACKS' => 'Pack', 'SUPP' => 'Suppositoria', 'SUPPO' => 'Suppositoria', 'PC' => 'Pcs', '' => 'Pcs'];

    /** Jenis barang: obat, atau BMHP (bahan medis habis pakai). Daftar dan stok keduanya dipisah di menu Farmasi. */
    public const KINDS = ['obat' => 'Obat', 'bmhp' => 'BMHP'];

    /** Batas hari sebelum tanggal kedaluwarsa untuk diberi peringatan. */
    public const ED_WARN_DAYS = 90;

    protected $fillable = ['code', 'kind', 'name', 'group', 'unit', 'price', 'expired_at', 'is_active'];

    protected $casts = ['price' => 'float', 'expired_at' => 'date', 'is_active' => 'bool'];

    public function entries(): HasMany
    {
        return $this->hasMany(StockEntry::class);
    }

    public static function normalizeUnit(?string $unit): string
    {
        $key = strtoupper(trim((string) $unit));
        if (isset(self::UNIT_ALIASES[$key])) {
            return self::UNIT_ALIASES[$key];
        }
        foreach (self::UNITS as $u) {
            if (strtoupper($u) === $key) {
                return $u;
            }
        }

        return ucfirst(strtolower($key));
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

    /** Barang satu jenis (hanya yang aktif, kecuali $all), urut golongan seperti di Excel lalu nama. */
    public static function sorted(string $kind = 'obat', bool $all = false)
    {
        return static::where('kind', $kind)->when(! $all, fn ($q) => $q->where('is_active', true))->get()
            ->sortBy(function ($m) {
                $order = array_search($m->group, self::GROUPS, true);

                return sprintf('%02d %s', $order === false ? 99 : $order, mb_strtoupper($m->name));
            }, SORT_NATURAL)
            ->values();
    }
}
