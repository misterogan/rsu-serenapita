<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    public const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    protected $fillable = ['simrs_code', 'name', 'poli_id', 'status', 'schedule', 'color', 'bio', 'photo', 'show'];
    protected $casts = ['schedule' => 'array', 'show' => 'bool'];

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(DoctorLeave::class);
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('show', true)->where('status', '!=', 'Nonaktif')
            ->whereHas('poli', fn ($p) => $p->where('active', true));
    }

    /** Jadwal untuk satu hari (0 = Minggu), atau null bila tidak praktik. */
    public function slotFor(int $day): ?array
    {
        foreach ($this->schedule ?? [] as $s) {
            if ((int) $s[0] === $day) {
                return $s;
            }
        }

        return null;
    }

    public function onLeave(Carbon|string $date): ?DoctorLeave
    {
        $d = Carbon::parse($date)->toDateString();

        return $this->leaves->first(fn ($l) => $d >= $l->date_from->toDateString() && $d <= $l->date_to->toDateString());
    }

    /** Daftar jam kedatangan untuk tanggal tertentu dengan jarak menit tertentu. */
    public function timesFor(Carbon|string $date, int $interval = 30): array
    {
        $date = Carbon::parse($date);
        $slot = $this->slotFor($date->dayOfWeek);
        if (! $slot || $this->onLeave($date)) {
            return [];
        }
        [$h1, $m1] = array_map('intval', explode('.', $slot[1]));
        [$h2, $m2] = array_map('intval', explode('.', $slot[2]));
        $out = [];
        for ($m = $h1 * 60 + $m1; $m < $h2 * 60 + $m2; $m += $interval) {
            $out[] = sprintf('%02d.%02d', intdiv($m, 60), $m % 60);
        }

        return $out;
    }

    public function initials(): string
    {
        $name = preg_replace('/^(dr\.|drg\.|Ns\.)\s*/', '', $this->name);
        $words = array_filter(preg_split('/[\s,]+/', $name), fn ($w) => $w !== '' && ctype_upper($w[0]) && ! str_contains($w, '.'));

        return implode('', array_map(fn ($w) => $w[0], array_slice(array_values($words), 0, 2))) ?: '?';
    }

    public function scheduleText(): string
    {
        return collect($this->schedule ?? [])->map(fn ($s) => self::DAYS[$s[0]].' '.$s[1].'–'.$s[2])->implode(', ');
    }
}
