<?php

namespace App\Services;

use App\Models\Appointment;
use App\Services\Simrs\SimrsClient;
use App\Support\SiteSettings;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AppointmentService
{
    public function __construct(protected SimrsClient $simrs) {}

    public function create(array $data): Appointment
    {
        do {
            $code = 'SRN-'.strtoupper(Str::random(6));
        } while (Appointment::where('code', $code)->exists());

        $appt = Appointment::create($data + ['code' => $code, 'status' => 'Menunggu']);
        $s = SiteSettings::get('simrs');
        if (($s['send_booking'] ?? true) && ! $this->sendToSimrs($appt) && ! ($s['fallback'] ?? true)) {
            $appt->delete();
            throw ValidationException::withMessages(['time' => 'Sistem pendaftaran rumah sakit sedang tidak dapat dihubungi. Silakan coba lagi nanti atau hubungi bagian pendaftaran.']);
        }

        return $appt;
    }

    public function sendToSimrs(Appointment $appt): bool
    {
        $appt->loadMissing('doctor.poli');
        try {
            $res = $this->simrs->createBooking([
                'code' => $appt->code,
                'kode_dokter' => $appt->doctor->simrs_code,
                'kode_poli' => $appt->doctor->poli?->simrs_code,
                'tanggal' => $appt->date->toDateString(),
                'jam' => $appt->time,
                'nama' => $appt->name,
                'nik' => $appt->nik,
                'no_hp' => $appt->phone,
                'tanggal_lahir' => $appt->birth_date?->toDateString(),
                'cara_bayar' => $appt->payment,
                'no_bpjs' => $appt->bpjs_number,
                'no_rujukan' => $appt->referral_number,
                'no_rm' => $appt->medical_record,
                'keluhan' => $appt->note,
            ]);
            $appt->update([
                'simrs_booking_code' => $res['booking_code'], 'queue_number' => $res['queue_number'],
                'status' => $res['status'] ?: 'Menunggu', 'simrs_sent_at' => now(), 'simrs_error' => null,
            ]);

            return true;
        } catch (Throwable $e) {
            report($e);
            $appt->update(['simrs_error' => mb_substr($e->getMessage(), 0, 250)]);

            return false;
        }
    }
}
