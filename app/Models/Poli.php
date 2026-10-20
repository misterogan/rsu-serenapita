<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poli extends Model
{
    protected $fillable = ['simrs_code', 'name', 'description', 'active', 'featured', 'sort'];
    protected $casts = ['active' => 'bool', 'featured' => 'bool'];

    /** Alamat halaman poli, misalnya /poliklinik/poli-anak. */
    public function slug(): string
    {
        return \Illuminate\Support\Str::slug($this->name) ?: 'poli-'.$this->id;
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('active', true)->get()->first(fn ($p) => $p->slug() === $slug);
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }
}
