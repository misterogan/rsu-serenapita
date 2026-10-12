<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ChatConversation;
use App\Models\Doctor;
use App\Models\Poli;
use App\Services\AppointmentService;
use App\Services\Simrs\SimrsClient;
use App\Support\SiteSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class AppointmentController extends Controller
{
    public const MAX_DAYS = 30;

    public function create(Request $request)
    {
        $polis = Poli::where('active', true)->orderBy('sort')
            ->with(['doctors' => fn ($q) => $q->public()->orderBy('name')])->get()->filter(fn ($p) => $p->doctors->isNotEmpty())->values();

        return view('site.appointment', [
            'polis' => $polis,
            'booking' => SiteSettings::get('booking'),
            'simrs' => SiteSettings::get('simrs'),
            'mine' => Appointment::with('doctor')->whereIn('code', session('my_appointments', []))->orderBy('date')->get(),
            'booked' => session('booked') ? Appointment::with('doctor.poli')->where('code', session('booked'))->first() : null,
            'preselect' => $request->query('doctor'),
            'minDate' => today()->addDay()->toDateString(),
            'maxDate' => today()->addDays(self::MAX_DAYS)->toDateString(),
        ]);
    }

    /** JSON: jam yang tersedia untuk dokter + tanggal. */
    public function times(Request $request, SimrsClient $simrs)
    {
        $data = $request->validate(['doctor' => 'required|integer', 'date' => 'required|date']);
        $doctor = Doctor::public()->with('leaves')->findOrFail($data['doctor']);
        $date = Carbon::parse($data['date']);

        if ($date->lt(today()->addDay()) || $date->gt(today()->addDays(self::MAX_DAYS))) {
            return response()->json(['times' => [], 'message' => 'Pilih tanggal mulai besok sampai '.self::MAX_DAYS.' hari ke depan.']);
        }
        if (! $doctor->slotFor($date->dayOfWeek)) {
            $days = collect($doctor->schedule ?? [])->map(fn ($s) => Doctor::DAYS[$s[0]])->implode(', ');

            return response()->json(['times' => [], 'message' => "Dokter tidak praktik hari ".Doctor::DAYS[$date->dayOfWeek].". Pilih $days."]);
        }
        if ($leave = $doctor->onLeave($date)) {
            return response()->json(['times' => [], 'message' => 'Dokter sedang cuti sampai '.$leave->date_to->translatedFormat('d F Y').'.']);
        }
        try {
            $times = $simrs->availableTimes($doctor, $date->toDateString());
        } catch (Throwable $e) {
            report($e);

            return response()->json(['times' => [], 'message' => 'Jadwal belum bisa dimuat dari sistem rumah sakit. Silakan coba lagi atau hubungi pendaftaran.']);
        }

        return response()->json(['times' => $times, 'message' => $times ? '' : 'Kuota pada tanggal ini sudah penuh. Pilih tanggal lain.']);
    }

    public function store(Request $request, SimrsClient $simrs, AppointmentService $service)
    {
        $b = SiteSettings::get('booking');
        abort_unless($b['online'], 403, 'Pendaftaran online sedang ditutup.');
        $isBpjs = $request->input('payment') === 'BPJS Kesehatan';

        $data = $request->validate([
            'doctor_id' => 'required|integer',
            'date' => 'required|date|after:today|before_or_equal:'.today()->addDays(self::MAX_DAYS)->toDateString(),
            'time' => ['required', 'regex:/^\d{2}\.\d{2}$/'],
            'name' => 'required|string|min:3|max:100',
            'phone' => ['required', 'string', 'max:20'],
            'birth_date' => 'required|date|before:today',
            'nik' => [$b['require_nik'] ? 'required' : 'nullable', 'digits:16'],
            'payment' => 'required|in:BPJS Kesehatan,Umum,Asuransi lain',
            'bpjs_number' => [$isBpjs && $b['require_bpjs'] ? 'required' : 'nullable', 'digits:13'],
            'referral_number' => [$isBpjs && $b['require_bpjs'] ? 'required' : 'nullable', 'string', 'max:30'],
            'medical_record' => 'nullable|string|max:30',
            'note' => 'nullable|string|max:500',
            'consent' => 'accepted',
        ], [], [
            'doctor_id' => 'dokter', 'date' => 'tanggal', 'time' => 'jam', 'name' => 'nama', 'phone' => 'nomor HP', 'birth_date' => 'tanggal lahir',
            'nik' => 'NIK', 'payment' => 'cara bayar', 'bpjs_number' => 'nomor kartu BPJS', 'referral_number' => 'nomor rujukan',
            'medical_record' => 'nomor rekam medis', 'note' => 'keluhan', 'consent' => 'persetujuan',
        ]);

        $phone = ChatConversation::normalizePhone($data['phone']);
        if (! preg_match('/^08[1-9]\d{6,11}$/', $phone)) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP tidak valid. Contoh: 081234567890.']);
        }
        $doctor = Doctor::public()->with('leaves')->findOrFail($data['doctor_id']);
        if (! in_array($data['time'], $simrs->availableTimes($doctor, $data['date']), true)) {
            throw ValidationException::withMessages(['time' => 'Jam ini sudah tidak tersedia. Pilih jam lain.']);
        }

        unset($data['consent']);
        $appt = $service->create(array_merge($data, ['phone' => $phone, 'doctor_id' => $doctor->id]));
        $request->session()->push('my_appointments', $appt->code);

        return redirect()->route('appointment')->with('booked', $appt->code);
    }
}
