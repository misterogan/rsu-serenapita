<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Salinan data Khanza untuk closing pada satu tanggal cut off. Isi per dataset disimpan di closing_snapshot_data. */
class ClosingSnapshot extends Model
{
    protected $fillable = ['cutoff', 'year_start', 'opening', 'errors', 'user_id', 'user_name'];

    protected $casts = ['cutoff' => 'date', 'year_start' => 'date', 'errors' => 'array'];

    protected array $loaded = [];

    public static function latest(): ?self
    {
        return static::query()->orderByDesc('id')->first();
    }

    /** @return array<int, array<string, mixed>> */
    public function rows(string $dataset): array
    {
        if (! array_key_exists($dataset, $this->loaded)) {
            $raw = DB::table('closing_snapshot_data')->where('snapshot_id', $this->id)->where('dataset', $dataset)->value('rows');
            $this->loaded[$dataset] = $raw ? (json_decode($raw, true) ?: []) : [];
        }

        return $this->loaded[$dataset];
    }

    public function has(string $dataset): bool
    {
        return DB::table('closing_snapshot_data')->where('snapshot_id', $this->id)->where('dataset', $dataset)->exists();
    }

    public function store(string $dataset, array $rows): void
    {
        DB::table('closing_snapshot_data')->updateOrInsert(['snapshot_id' => $this->id, 'dataset' => $dataset], ['rows' => json_encode($rows, JSON_UNESCAPED_UNICODE)]);
        $this->loaded[$dataset] = $rows;
    }
}
