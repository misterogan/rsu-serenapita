<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOpening extends Model
{
    public const TYPES = ['Tetap', 'Kontrak', 'Paruh waktu', 'Magang'];
    public const EDUCATION = ['SMA/SMK', 'D3', 'D4/S1', 'Profesi (Ners/Apoteker/Dokter)', 'S2', 'Spesialis'];

    protected $fillable = ['slug', 'title', 'department', 'employment_type', 'education', 'location', 'quota', 'deadline', 'status', 'summary', 'description', 'requirements', 'benefits'];
    protected $casts = ['deadline' => 'date'];

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** Lowongan yang tampil di situs: Dibuka atau Ditutup (bukan Draf). */
    public function scopePublished(Builder $q): Builder
    {
        return $q->whereIn('status', ['Dibuka', 'Ditutup']);
    }

    public function isOpen(): bool
    {
        return $this->status === 'Dibuka' && (! $this->deadline || $this->deadline->gte(today()));
    }

    /** @return string[] */
    public function lines(string $field): array
    {
        return array_values(array_filter(array_map(fn ($l) => trim(ltrim(trim($l), '-•*0123456789.) ')), preg_split('/\R/', (string) $this->{$field}))));
    }

    /** @return string[] */
    public function paragraphs(string $field): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', (string) $this->{$field}))));
    }
}
