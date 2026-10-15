<?php

namespace App\Services\Simrs;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Support\SiteSettings;
use Illuminate\Support\Str;

/**
 * Dipakai saat mode data "Manual": jadwal dan janji temu dikelola di database situs sendiri,
 * tanpa menghubungi SIMRS.
 */
class ManualSimrsClient implements SimrsClient
{
    public function polis(): array { return []; }
    public function doctors(): array { return []; }
    public function leaves(): array { return []; }

    public function availableTimes(Doctor $doctor, string $date): array
    {
        $b = SiteSettings::get('booking');
        $interval = max(5, (int) ($b['interval'] ?? 30));
        $quota = max(1, (int) ($b['slot_quota'] ?? 3));
        $taken = Appointment::query()->where('doctor_id', $doctor->id)->whereDate('date', $date)
            ->where('status', '!=', 'Batal')->selectRaw('time, count(*) as n')->groupBy('time')->pluck('n', 'time');

        return array_values(array_filter($doctor->timesFor($date, $interval), fn ($t) => ($taken[$t] ?? 0) < $quota));
    }

    public function createBooking(array $payload): array
    {
        $doctor = Doctor::with('poli')->where('simrs_code', $payload['kode_dokter'])->first();
        $n = Appointment::query()->where('doctor_id', $doctor?->id)->whereDate('date', $payload['tanggal'])
            ->whereNotNull('queue_number')->count() + 1;
        $prefix = strtoupper(mb_substr(preg_replace('/^(poli(klinik)?\s+)/i', '', $doctor?->poli?->name ?? 'A'), 0, 1));

        return ['booking_code' => 'LOK-'.strtoupper(Str::random(6)), 'queue_number' => $prefix.'-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT), 'status' => 'Menunggu'];
    }

    /** Status dikelola langsung di backoffice. */
    public function bookingStatus(string $bookingCode): ?string { return null; }

    public function ping(): int { return 0; }
}
