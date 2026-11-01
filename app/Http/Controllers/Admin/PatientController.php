<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Khanza\KhanzaPatients;
use App\Services\Khanza\KhanzaReports;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Data pasien dari SIMRS Khanza (hanya baca): pencarian, profil, riwayat kunjungan, dan detail tiap kunjungan.
 * Setiap pembukaan data pasien dicatat di Log aktivitas, dan halamannya tidak boleh disimpan di cache browser.
 */
class PatientController extends Controller
{
    protected function private($response)
    {
        return response($response)->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }

    protected function unavailable(): ?string
    {
        if (! KhanzaReports::configured()) {
            return 'Database SIMRS Khanza belum dihubungkan. Isi pengaturan KHANZA_DB_* di file .env server (lihat database/khanza/README.txt).';
        }
        try {
            KhanzaReports::ping();
        } catch (Throwable $e) {
            return 'Database SIMRS Khanza tidak bisa dihubungi: '.mb_substr($e->getMessage(), 0, 160);
        }

        return null;
    }

    public function index(Request $request)
    {
        $term = trim((string) $request->query('q'));
        try {
            $date = Carbon::createFromFormat('Y-m-d', (string) $request->query('tanggal', today()->toDateString()))->startOfDay();
        } catch (Throwable) {
            $date = today();
        }
        $view = ['term' => $term, 'date' => $date, 'results' => [], 'today' => [], 'errors_' => [], 'unavailable' => $this->unavailable()];
        if (! $view['unavailable']) {
            $k = new KhanzaPatients;
            if ($term !== '') {
                $view['results'] = $k->search($term);
                ActivityLog::record('Mencari data pasien: "'.mb_substr($term, 0, 60).'"');
            } else {
                $view['today'] = $k->registrations($date->toDateString());
            }
            $view['errors_'] = $k->errors;
        }

        return $this->private(view('admin.patients.index', $view));
    }

    public function show(string $rm)
    {
        abort_unless(preg_match('/^[A-Za-z0-9.\-]{1,20}$/', $rm), 404);
        if ($msg = $this->unavailable()) {
            return redirect()->route('admin.patients')->with('err', $msg);
        }
        $k = new KhanzaPatients;
        $patient = $k->patient($rm);
        abort_unless($patient, 404, 'Pasien dengan No. RM tersebut tidak ditemukan di SIMRS Khanza.');
        $visits = $k->visits($rm);
        $clinical = $k->clinicalSummary($rm, $visits);
        ActivityLog::record('Membuka data pasien RM '.$rm);

        return $this->private(view('admin.patients.show', ['p' => $patient, 'visits' => $visits, 'clinical' => $clinical, 'errors_' => $k->errors]));
    }

    public function visit(Request $request, string $rm)
    {
        $noRawat = (string) $request->query('rawat');
        abort_unless(preg_match('/^[A-Za-z0-9.\-]{1,20}$/', $rm) && preg_match('#^[0-9/]{8,20}$#', $noRawat), 404);
        if ($msg = $this->unavailable()) {
            return redirect()->route('admin.patients')->with('err', $msg);
        }
        $k = new KhanzaPatients;
        $patient = $k->patient($rm);
        $visit = $patient ? $k->visit($rm, $noRawat) : null;
        abort_unless($visit, 404, 'Kunjungan tidak ditemukan untuk pasien ini.');
        ActivityLog::record('Membuka kunjungan '.$noRawat.' pasien RM '.$rm);

        return $this->private(view('admin.patients.visit', ['p' => $patient, 'v' => $visit, 'errors_' => $k->errors]));
    }
}
