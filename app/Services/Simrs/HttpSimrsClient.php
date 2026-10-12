<?php

namespace App\Services\Simrs;

use App\Models\Doctor;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Klien HTTP ke API gateway SIMRS.
 * Nama endpoint dan field di bawah adalah contoh kontrak; sesuaikan dengan dokumentasi vendor SIMRS.
 */
class HttpSimrsClient implements SimrsClient
{
    protected function http(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('serenapita.simrs.base_url'), '/'))
            ->withToken(config('serenapita.simrs.token'))
            ->acceptJson()
            ->timeout(config('serenapita.simrs.timeout', 15))
            ->retry(2, 300, throw: false);
    }

    public function polis(): array
    {
        return collect($this->http()->get('/clinics')->throw()->json('data', []))
            ->map(fn ($p) => ['code' => (string) $p['kode'], 'name' => $p['nama']])->all();
    }

    public function doctors(): array
    {
        return collect($this->http()->get('/doctors')->throw()->json('data', []))->map(fn ($d) => [
            'code' => (string) $d['kode'],
            'name' => $d['nama'],
            'poli_code' => (string) $d['kode_poli'],
            'status' => $d['status'] ?? 'Aktif',
            // diharapkan: [{"hari":1,"mulai":"08.00","selesai":"12.00"}]
            'schedule' => collect($d['jadwal'] ?? [])->map(fn ($j) => [(int) $j['hari'], $j['mulai'], $j['selesai']])->all(),
        ])->all();
    }

    public function leaves(): array
    {
        return collect($this->http()->get('/leaves')->throw()->json('data', []))->map(fn ($c) => [
            'code' => (string) $c['kode'],
            'doctor_code' => (string) $c['kode_dokter'],
            'from' => $c['mulai'],
            'to' => $c['selesai'],
            'reason' => $c['keterangan'] ?? null,
        ])->all();
    }

    public function availableTimes(Doctor $doctor, string $date): array
    {
        return collect($this->http()->get('/slots', ['doctor' => $doctor->simrs_code, 'date' => $date])->throw()->json('data', []))
            ->filter(fn ($s) => ($s['sisa'] ?? 1) > 0)->pluck('jam')->values()->all();
    }

    public function createBooking(array $payload): array
    {
        $res = $this->http()->withHeaders(['Idempotency-Key' => $payload['code']])->post('/bookings', $payload)->throw();

        return [
            'booking_code' => (string) $res->json('data.kode_booking'),
            'queue_number' => $res->json('data.nomor_antrean'),
            'status' => $res->json('data.status', 'Menunggu'),
        ];
    }

    public function bookingStatus(string $bookingCode): ?string
    {
        return $this->http()->get('/bookings/'.urlencode($bookingCode))->json('data.status');
    }

    public function ping(): int
    {
        $start = microtime(true);
        $this->http()->get('/ping')->throw();

        return (int) round((microtime(true) - $start) * 1000);
    }
}
