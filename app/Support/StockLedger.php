<?php

namespace App\Support;

use App\Models\StockEntry;
use Illuminate\Support\Carbon;

/**
 * Perhitungan persediaan obat, pengganti rumus di file Excel persediaan.
 * Stok akhir hari = stok awal + masuk − keluar. Bila hari itu ada stock opname (hitung fisik sebelum transaksi),
 * stok awal hari itu mengikuti hasil hitung dan bedanya dengan stok sistem dicatat sebagai selisih SO.
 */
class StockLedger
{
    /** Hari pemakaian untuk safety stock dan reorder point, sama dengan rumus Excel (×7 dan ×10). */
    public const SAFETY_DAYS = 7;

    public const ROP_DAYS = 10;

    /**
     * Rekap per obat untuk satu unit dan periode.
     *
     * @return array<int, array{start: int, in: int, out: int, adjust: int, end: int, counted_at: ?Carbon}>
     */
    public static function summary(string $location, Carbon $from, Carbon $to): array
    {
        $rows = StockEntry::where('location', $location)->whereDate('date', '<=', $to)
            ->orderBy('date')->get(['medicine_id', 'date', 'qty_in', 'qty_out', 'counted']);
        $out = [];
        foreach ($rows as $e) {
            $s = $out[$e->medicine_id] ?? ['start' => 0, 'in' => 0, 'out' => 0, 'adjust' => 0, 'end' => 0, 'counted_at' => null];
            $base = $e->counted ?? $s['end'];
            $after = $base + $e->qty_in - $e->qty_out;
            if ($e->date->lt($from)) {
                $s['start'] = $after;
            } else {
                $s['in'] += $e->qty_in;
                $s['out'] += $e->qty_out;
                $s['adjust'] += $base - $s['end'];
            }
            $s['end'] = $after;
            if ($e->counted !== null) {
                $s['counted_at'] = $e->date;
            }
            $out[$e->medicine_id] = $s;
        }

        return $out;
    }

    /**
     * Total keluar per obat dalam periode.
     *
     * @return array<int, int>
     */
    public static function usage(string $location, Carbon $from, Carbon $to): array
    {
        return StockEntry::where('location', $location)->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            ->groupBy('medicine_id')->selectRaw('medicine_id, SUM(qty_out) AS total')
            ->pluck('total', 'medicine_id')->map(fn ($v) => (int) $v)->all();
    }

    public static function safety(float $avg): int
    {
        return (int) ceil($avg * self::SAFETY_DAYS);
    }

    public static function rop(float $avg): int
    {
        return (int) ceil($avg * self::ROP_DAYS);
    }

    /**
     * Status stok: habis, perlu dipesan (di bawah ROP), minus (salah isi), atau aman.
     *
     * @return array{0: string, 1: string} [label, warna badge]
     */
    public static function status(int $end, float $avg): array
    {
        return match (true) {
            $end < 0 => ['Minus', 'red'],
            $end === 0 => ['Habis', 'red'],
            $avg > 0 && $end <= self::rop($avg) => ['Perlu pesan', 'amber'],
            default => ['Aman', 'green'],
        };
    }
}
