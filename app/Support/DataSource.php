<?php

namespace App\Support;

/**
 * Sumber data dokter, poliklinik, jadwal, dan cuti.
 * - manual : dikelola langsung di backoffice (selama SIMRS belum terhubung)
 * - simrs  : diambil otomatis dari SIMRS, backoffice hanya mengatur tampilan
 */
class DataSource
{
    public static function manual(): bool
    {
        try {
            return (SiteSettings::get('simrs')['source'] ?? 'manual') !== 'simrs';
        } catch (\Throwable) {
            return true; // database belum siap (misalnya saat instalasi awal)
        }
    }

    public static function label(): string
    {
        return self::manual() ? 'Manual (backoffice)' : 'SIMRS';
    }
}
