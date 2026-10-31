<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isian stok satu obat di satu unit pada satu tanggal.
 * counted = hasil hitung fisik (stock opname) sebelum transaksi hari itu; bila diisi, menjadi stok awal hari itu.
 */
class StockEntry extends Model
{
    public const LOCATIONS = ['gudang' => 'Gudang Farmasi', 'sentral' => 'Farmasi Sentral'];

    protected $fillable = ['medicine_id', 'location', 'date', 'qty_in', 'qty_out', 'counted', 'note', 'user_id'];

    protected $casts = ['date' => 'date', 'qty_in' => 'int', 'qty_out' => 'int', 'counted' => 'int'];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
