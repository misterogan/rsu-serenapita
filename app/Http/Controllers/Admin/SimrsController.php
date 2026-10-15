<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SyncLog;
use App\Services\Simrs\SimrsClient;
use App\Services\Simrs\SimrsSync;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Throwable;

class SimrsController extends Controller
{
    public function index()
    {
        return view('admin.simrs', [
            's' => SiteSettings::get('simrs'),
            'driver' => config('serenapita.simrs.driver'),
            'baseUrl' => config('serenapita.simrs.base_url'),
            'history' => SyncLog::latest('id')->limit(50)->get(),
            'okCount' => SyncLog::where('ok', true)->count(),
            'allCount' => SyncLog::count(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate(['mode' => 'required|in:webhook,poll', 'interval' => 'required|integer|in:1,2,5,10']);
        SiteSettings::put('simrs', array_merge(SiteSettings::get('simrs'), $data, [
            'send_booking' => $request->boolean('send_booking'), 'fallback' => $request->boolean('fallback'),
        ]));
        ActivityLog::record('Memperbarui pengaturan integrasi SIMRS');

        return back()->with('ok', 'Pengaturan SIMRS disimpan.');
    }

    /** Klien SIMRS sungguhan (bukan mode manual), untuk uji koneksi sebelum berpindah sumber data. */
    protected function realClient(): SimrsClient
    {
        return config('serenapita.simrs.driver') === 'http' ? new \App\Services\Simrs\HttpSimrsClient : new \App\Services\Simrs\FakeSimrsClient;
    }

    public function test()
    {
        $simrs = $this->realClient();
        try {
            $ms = $simrs->ping();
            SyncLog::create(['ok' => true, 'message' => "Uji koneksi berhasil, respons {$ms} ms"]);
            $s = SiteSettings::get('simrs');
            $s['status'] = 'Terhubung';
            SiteSettings::put('simrs', $s);

            return back()->with('ok', "Koneksi SIMRS berhasil ({$ms} ms).");
        } catch (Throwable $e) {
            SyncLog::create(['ok' => false, 'message' => 'Uji koneksi gagal: '.mb_substr($e->getMessage(), 0, 180)]);

            return back()->with('err', 'Koneksi SIMRS gagal: '.$e->getMessage());
        }
    }

    public function sync(SimrsSync $sync)
    {
        $r = $sync->run('manual');
        if (! empty($r['manual'])) {
            return back()->with('err', $r['message']);
        }
        ActivityLog::record('Menjalankan sinkronisasi SIMRS manual');

        return back()->with($r['ok'] ? 'ok' : 'err', $r['message']);
    }

    /** Pindah sumber data dokter dan jadwal: manual (backoffice) atau SIMRS. */
    public function source(Request $request)
    {
        $source = $request->validate(['source' => 'required|in:manual,simrs'])['source'];
        $s = SiteSettings::get('simrs');
        if (($s['source'] ?? 'manual') === $source) {
            return back()->with('ok', 'Sumber data tidak berubah.');
        }
        $s['source'] = $source;
        SiteSettings::put('simrs', $s);
        ActivityLog::record($source === 'simrs' ? 'Mengubah sumber data dokter dan jadwal ke SIMRS' : 'Mengubah sumber data dokter dan jadwal ke manual');

        if ($source === 'manual') {
            return back()->with('ok', 'Mode manual aktif. Dokter, poliklinik, jadwal, cuti, dan status janji temu sekarang dikelola di backoffice.');
        }
        $r = app(SimrsSync::class)->run('perpindahan ke SIMRS');

        return back()->with($r['ok'] ? 'ok' : 'err', $r['ok']
            ? 'Mode SIMRS aktif. '.$r['message'].'. Dokter yang kodenya tidak ada di SIMRS otomatis dinonaktifkan.'
            : 'Mode SIMRS aktif, tetapi sinkron pertama gagal: '.$r['message'].'. Periksa koneksi atau kembalikan ke mode manual.');
    }
}
