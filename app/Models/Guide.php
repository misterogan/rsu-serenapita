<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    public const CATEGORIES = ['Umum', 'Rawat jalan', 'Rawat inap', 'BPJS', 'IGD', 'Besuk', 'Pembayaran'];
    protected $fillable = ['title', 'category', 'content', 'show', 'sort'];
    protected $casts = ['show' => 'bool'];

    /**
     * Isi panduan menjadi blok: paragraf biasa, atau daftar (baris diawali "-").
     *
     * @return array<int, array{type: string, items: string[]}>
     */
    public function blocks(): array
    {
        $out = [];
        foreach (preg_split('/\R\s*\R/', trim((string) $this->content)) as $chunk) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $chunk))));
            if (! $lines) {
                continue;
            }
            $isList = collect($lines)->every(fn ($l) => preg_match('/^[-•*]\s*/', $l));
            $out[] = $isList
                ? ['type' => 'list', 'items' => array_map(fn ($l) => preg_replace('/^[-•*]\s*/', '', $l), $lines)]
                : ['type' => 'p', 'items' => [implode(' ', $lines)]];
        }

        return $out;
    }
}
