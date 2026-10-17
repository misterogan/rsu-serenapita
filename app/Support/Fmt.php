<?php

namespace App\Support;

/** Format angka untuk laporan (gaya Indonesia). */
class Fmt
{
    public static function n($v, int $d = 0): string
    {
        return $v === null || $v === '' ? '—' : number_format((float) $v, $d, ',', '.');
    }

    public static function rp($v): string
    {
        return $v === null ? '—' : 'Rp '.number_format((float) $v, 0, ',', '.');
    }

    /** Rupiah ringkas: Rp 1,2 M / Rp 345 jt. */
    public static function rps($v): string
    {
        $v = (float) $v;
        $a = abs($v);
        $s = $v < 0 ? '−' : '';

        return match (true) {
            $a >= 1e9 => $s.'Rp '.number_format($a / 1e9, 2, ',', '.').' M',
            $a >= 1e6 => $s.'Rp '.number_format($a / 1e6, 1, ',', '.').' jt',
            default => $s.'Rp '.number_format($a, 0, ',', '.'),
        };
    }

    public static function pct($a, $b): float
    {
        return $b ? round($a / $b * 100, 1) : 0;
    }

    public static function pf($a, $b): string
    {
        return number_format($b ? $a / $b * 100 : 0, 1, ',', '.');
    }

    public static function delta($cur, $old): ?float
    {
        return $old ? round(($cur - $old) / $old * 100, 1) : null;
    }

    public static function month(string $ym): string
    {
        return \Illuminate\Support\Carbon::parse($ym.'-01')->translatedFormat('M y');
    }
}
