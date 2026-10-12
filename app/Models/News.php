<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    protected $fillable = ['slug', 'title', 'category_id', 'published_at', 'status', 'illustration', 'image', 'excerpt', 'body', 'featured', 'author_id'];
    protected $casts = ['published_at' => 'date', 'featured' => 'bool'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Berita yang boleh tampil di situs: status Terbit, atau Terjadwal yang tanggalnya sudah tiba. */
    public function scopeVisible(Builder $q): Builder
    {
        return $q->where(function ($q) {
            $q->where('status', 'Terbit')
              ->orWhere(fn ($q) => $q->where('status', 'Terjadwal')->whereDate('published_at', '<=', today()));
        })->whereDate('published_at', '<=', today())->orderByDesc('published_at');
    }

    /** @return string[] paragraf isi berita */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', (string) $this->body))));
    }
}
