<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Services\AppointmentService;

class AppointmentController extends Controller
{
    public function resend(Appointment $appointment, AppointmentService $service)
    {
        $ok = $service->sendToSimrs($appointment);
        ActivityLog::record('Mengirim ulang janji '.$appointment->code.' ke SIMRS');

        return back()->with($ok ? 'ok' : 'err', $ok ? 'Janji terkirim ke SIMRS.' : 'Masih gagal: '.$appointment->simrs_error);
    }
}
