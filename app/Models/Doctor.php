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

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('show', true)->where('status', '!=', 'Nonaktif')
            ->whereHas('poli', fn ($p) => $p->where('active', true));
    }

    /** Sesi praktik pertama pada satu hari (0 = Minggu), atau null bila tidak praktik. */
    public function slotFor(int $day): ?array
    {
        return $this->slotsFor($day)[0] ?? null;
    }

    /** Semua sesi praktik pada satu hari, urut jam mulai. Satu hari bisa punya lebih dari satu sesi. */
    public function slotsFor(int $day): array
    {
        return collect($this->schedule ?? [])->filter(fn ($s) => (int) $s[0] === $day)->sortBy(fn ($s) => $s[1])->values()->all();
    }

    /** Teks jam praktik satu hari, misalnya "10.00–12.00, 13.30–19.30". */
    public function hoursText(int $day): ?string
    {
        $s = $this->slotsFor($day);

        return $s ? implode(', ', array_map(fn ($x) => $x[1].'–'.$x[2], $s)) : null;
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
        $slots = $this->slotsFor($date->dayOfWeek);
        if (! $slots || $this->onLeave($date)) {
            return [];
        }
        $out = [];
        foreach ($slots as $slot) {
            [$h1, $m1] = array_map('intval', explode('.', $slot[1]));
            [$h2, $m2] = array_map('intval', explode('.', $slot[2]));
            for ($m = $h1 * 60 + $m1; $m < $h2 * 60 + $m2; $m += $interval) {
                $out[] = sprintf('%02d.%02d', intdiv($m, 60), $m % 60);
            }
        }

        return array_values(array_unique($out));
    }

    public function initials(): string
    {
        $name = preg_replace('/^(dr\.|drg\.|Ns\.)\s*/', '', $this->name);
        $words = array_filter(preg_split('/[\s,]+/', $name), fn ($w) => $w !== '' && ctype_upper($w[0]) && ! str_contains($w, '.'));

        return implode('', array_map(fn ($w) => $w[0], array_slice(array_values($words), 0, 2))) ?: '?';
    }

    public function scheduleText(): string
    {
        return collect($this->schedule ?? [])->sortBy(fn ($s) => (($s[0] + 6) % 7).$s[1])->map(fn ($s) => self::DAYS[$s[0]].' '.$s[1].'–'.$s[2])->implode(', ');
    }
}
