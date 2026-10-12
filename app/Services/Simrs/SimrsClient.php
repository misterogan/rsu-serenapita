<?php

namespace App\Services\Simrs;

use App\Models\Doctor;

/**
 * Kontrak komunikasi dengan SIMRS. Sesuaikan HttpSimrsClient dengan dokumentasi API vendor SIMRS.
 */
interface SimrsClient
{
    /** @return array<int, array{code:string, name:string}> */
    public function polis(): array;

    /** @return array<int, array{code:string, name:string, poli_code:string, status:string, schedule:array}> */
    public function doctors(): array;

    /** @return array<int, array{code:string, doctor_code:string, from:string, to:string, reason:?string}> */
    public function leaves(): array;

    /** Jam yang masih tersedia untuk dokter pada tanggal tertentu (format "08.30"). */
    public function availableTimes(Doctor $doctor, string $date): array;

    /** @return array{booking_code:string, queue_number:?string, status:string} */
    public function createBooking(array $payload): array;

    public function bookingStatus(string $bookingCode): ?string;

    /** Mengembalikan waktu respons dalam milidetik, atau melempar exception bila gagal. */
    public function ping(): int;
}
