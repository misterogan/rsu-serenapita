<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\Simrs\SimrsSync;
use Illuminate\Http\Request;

/**
 * Dipanggil SIMRS saat ada perubahan. Header wajib: X-Simrs-Secret = SIMRS_WEBHOOK_SECRET.
 * Contoh body: {"event":"schedule"} atau {"event":"appointment","kode_booking":"SIM-ABC123","status":"Hadir"}
 */
class SimrsWebhookController extends Controller
{
    public function __invoke(Request $request, SimrsSync $sync)
    {
        $secret = config('serenapita.simrs.webhook_secret');
        abort_unless($secret && hash_equals($secret, (string) $request->header('X-Simrs-Secret')), 401);

        if (in_array($request->input('event'), ['appointment', 'janji'], true)) {
            $data = $request->validate(['kode_booking' => 'required|string', 'status' => 'required|in:'.implode(',', Appointment::STATUSES)]);
            $n = Appointment::where('simrs_booking_code', $data['kode_booking'])->update(['status' => $data['status']]);

            return response()->json(['ok' => true, 'updated' => $n]);
        }

        return response()->json($sync->run('webhook'));
    }
}
