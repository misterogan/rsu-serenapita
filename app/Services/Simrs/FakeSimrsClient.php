<?php

namespace App\Services\Simrs;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Support\Str;

/**
 * SIMRS tiruan untuk pengembangan. Mengembalikan data contoh agar situs bisa dicoba
 * sebelum API SIMRS asli tersedia. Ganti SIMRS_DRIVER=http di .env saat siap.
 */
class FakeSimrsClient implements SimrsClient
{
    public const QUOTA_PER_SLOT = 2;

    public function polis(): array
    {
        return array_map(fn ($p) => ['code' => $p[0], 'name' => $p[1]], [
            ['P01', 'Poli Penyakit Dalam'], ['P02', 'Poli Anak'], ['P03', 'Poli Kebidanan & Kandungan'],
            ['P04', 'Poli Bedah Umum'], ['P05', 'Poli Saraf'], ['P06', 'Poli Mata'], ['P07', 'Poli THT'],
            ['P08', 'Poli Gigi & Mulut'], ['P09', 'Poli Umum'], ['P10', 'Fisioterapi'],
        ]);
    }

    public function doctors(): array
    {
        $rows = [
            ['D01', 'dr. Rahmat Simanjuntak, Sp.PD', 'P01', [[1, '08.00', '12.00'], [3, '08.00', '12.00'], [5, '13.00', '16.00']]],
            ['D02', 'dr. Lestari Br. Sinaga, Sp.PD', 'P01', [[2, '09.00', '13.00'], [4, '09.00', '13.00'], [6, '08.00', '11.00']]],
            ['D03', 'dr. Maria Situmorang, Sp.A', 'P02', [[1, '09.00', '13.00'], [2, '09.00', '13.00'], [4, '14.00', '16.00'], [6, '08.00', '12.00']]],
            ['D04', 'dr. Johannes Tampubolon, Sp.OG', 'P03', [[1, '13.00', '16.00'], [3, '13.00', '16.00'], [5, '08.00', '12.00']]],
            ['D05', 'dr. Fransiska Manik, Sp.OG', 'P03', [[2, '08.00', '12.00'], [4, '08.00', '12.00'], [6, '09.00', '12.00']]],
            ['D06', 'dr. Daniel Pasaribu, Sp.B', 'P04', [[2, '13.00', '16.00'], [3, '08.00', '11.00'], [5, '09.00', '12.00']]],
            ['D07', 'dr. Agnes Hutasoit, Sp.N', 'P05', [[1, '10.00', '14.00'], [4, '10.00', '14.00']]],
            ['D08', 'dr. Samuel Nainggolan, Sp.M', 'P06', [[2, '09.00', '12.00'], [5, '13.00', '16.00']]],
            ['D09', 'dr. Ruth Siregar, Sp.THT-KL', 'P07', [[3, '09.00', '13.00'], [6, '08.00', '11.00']]],
            ['D10', 'drg. Yohana Sihombing', 'P08', [[1, '08.00', '14.00'], [2, '08.00', '14.00'], [3, '08.00', '14.00'], [4, '08.00', '14.00'], [5, '08.00', '14.00']]],
            ['D11', 'dr. Benny Silalahi', 'P09', [[1, '08.00', '16.00'], [2, '08.00', '16.00'], [3, '08.00', '16.00'], [4, '08.00', '16.00'], [5, '08.00', '16.00'], [6, '08.00', '13.00']]],
            ['D12', 'Ns. Petrus Lingga, S.Ft', 'P10', [[1, '08.00', '15.00'], [3, '08.00', '15.00'], [5, '08.00', '15.00']]],
        ];

        return array_map(fn ($d) => ['code' => $d[0], 'name' => $d[1], 'poli_code' => $d[2], 'status' => 'Aktif', 'schedule' => $d[3]], $rows);
    }

    public function leaves(): array
    {
        return [
            ['code' => 'C01', 'doctor_code' => 'D07', 'from' => today()->addDays(5)->toDateString(), 'to' => today()->addDays(7)->toDateString(), 'reason' => 'Seminar nasional neurologi'],
            ['code' => 'C02', 'doctor_code' => 'D09', 'from' => today()->subDays(2)->toDateString(), 'to' => today()->addDay()->toDateString(), 'reason' => 'Cuti tahunan'],
        ];
    }

    public function availableTimes(Doctor $doctor, string $date): array
    {
        $interval = (int) (\App\Support\SiteSettings::get('booking')['interval'] ?? 30);
        $taken = Appointment::query()->where('doctor_id', $doctor->id)->whereDate('date', $date)
            ->where('status', '!=', 'Batal')->selectRaw('time, count(*) as n')->groupBy('time')->pluck('n', 'time');

        return array_values(array_filter($doctor->timesFor($date, $interval), fn ($t) => ($taken[$t] ?? 0) < self::QUOTA_PER_SLOT));
    }

    public function createBooking(array $payload): array
    {
        return ['booking_code' => 'SIM-'.strtoupper(Str::random(6)), 'queue_number' => (string) random_int(1, 40), 'status' => 'Menunggu'];
    }

    public function bookingStatus(string $bookingCode): ?string
    {
        return null;
    }

    public function ping(): int
    {
        return random_int(60, 180);
    }
}
