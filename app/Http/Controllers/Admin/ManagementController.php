<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Khanza\KhanzaReports;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

/** Dasbor manajemen: laporan dari database SIMRS Khanza. */
class ManagementController extends Controller
{
    public const PRESETS = ['hari-ini' => 'Hari ini', '7-hari' => '7 hari terakhir', 'bulan-ini' => 'Bulan ini', 'bulan-lalu' => 'Bulan lalu', 'tahun-ini' => 'Tahun ini'];

    /** @return array{0: string, 1: string, 2: string} dari, sampai, preset */
    protected function period(Request $request): array
    {
        $p = $request->query('periode', 'bulan-ini');
        $today = Carbon::today();
        [$f, $t] = match ($p) {
            'hari-ini' => [$today, $today],
            '7-hari' => [$today->copy()->subDays(6), $today],
            'bulan-lalu' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'tahun-ini' => [$today->copy()->startOfYear(), $today],
            'custom' => [$this->date($request->query('dari')) ?? $today->copy()->startOfMonth(), $this->date($request->query('sampai')) ?? $today],
            default => [$today->copy()->startOfMonth(), $today],
        };
        if ($t->lt($f)) {
            [$f, $t] = [$t, $f];
        }
        if ($f->diffInDays($t) > 366) {
            $f = $t->copy()->subDays(366);
        }

        return [$f->toDateString(), $t->toDateString(), array_key_exists($p, self::PRESETS) || $p === 'custom' ? $p : 'bulan-ini'];
    }

    protected function date(?string $v): ?Carbon
    {
        try {
            return $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? Carbon::parse($v) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function index(Request $request)
    {
        [$from, $to, $preset] = $this->period($request);
        $role = $request->user()->role;
        $ops = $role->canSee('ops');
        $fin = $role->canSee('finance');
        $scope = ['ops' => $ops, 'fin' => $fin];
        if (! KhanzaReports::configured()) {
            return view('admin.management', ['configured' => false, 'from' => $from, 'to' => $to, 'preset' => $preset, 'presets' => self::PRESETS] + $scope);
        }
        $fresh = $request->boolean('segarkan');
        $r = new KhanzaReports($from, $to, $fresh);
        $connError = null;
        try {
            KhanzaReports::ping();
        } catch (Throwable $e) {
            $connError = mb_substr($e->getMessage(), 0, 240);
        }
        if ($fresh) {
            ActivityLog::record('Memuat ulang data Dasbor manajemen');
        }
        $data = [];
        if (! $connError) {
            // Hanya jalankan query untuk bagian yang boleh dilihat peran ini
            if ($ops) {
                $data += [
                    'summary' => $r->summary(), 'prev' => $r->previousSummary(), 'trend' => $r->trend(),
                    'clinics' => $r->byClinic(), 'payers' => $r->byPayer(), 'doctors' => $r->doctors(),
                    'antrolStatus' => $r->antrolStatus(), 'antrolClinic' => $r->antrolClinic(), 'antrolTimes' => $r->antrolTimes(),
                    'now' => $r->inpatientNow(), 'ind' => $r->inpatientIndicators(), 'wards' => $r->wards(), 'discharge' => $r->dischargeStatus(),
                    'diagnoses' => $r->topDiagnoses(), 'regions' => $r->regions(), 'demography' => $r->demography(),
                ];
            }
            if ($fin) {
                $data += ['payments' => $r->payments(), 'receivables' => $r->receivables(), 'billing' => $r->billingCategories()];
            }
            $data += ['support' => $r->support(), 'drugs' => $r->topDrugs()];
        }

        return view('admin.management', $data + $scope + [
            'configured' => true, 'connError' => $connError, 'errors_' => $r->errors, 'from' => $from, 'to' => $to, 'days' => $r->days,
            'preset' => $preset, 'presets' => self::PRESETS, 'loadedAt' => now(),
        ]);
    }

    /** Unduh satu laporan sebagai CSV (dibuka langsung di Excel). */
    public function export(Request $request, string $report)
    {
        $map = KhanzaReports::exports();
        abort_unless(isset($map[$report]) && KhanzaReports::configured(), 404);
        $role = $request->user()->role;
        $finance = ['pembayaran', 'piutang', 'billing'];
        $shared = ['obat'];
        $allowed = in_array($report, $finance, true) ? $role->canSee('finance') : (in_array($report, $shared, true) ? true : $role->canSee('ops'));
        abort_unless($allowed, 403, 'Laporan ini tidak termasuk hak akses Anda.');
        [$from, $to] = $this->period($request);
        $r = new KhanzaReports($from, $to);
        [$title, $method] = $map[$report];
        $rows = $r->{$method}();
        if ($report === 'obat' && ! $role->canSee('finance')) {
            $rows = array_map(fn ($x) => array_diff_key($x, ['nilai' => 1]), $rows); // tanpa nilai rupiah
        }
        abort_if(isset($r->errors) && $r->errors && ! $rows, 500, 'Laporan tidak bisa dibaca dari database Khanza.');
        ActivityLog::record('Mengunduh laporan '.$title.' ('.$from.' s/d '.$to.')');
        $name = 'laporan-'.$report.'-'.$from.'-sd-'.$to.'.csv';

        return response()->streamDownload(function () use ($rows, $title, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, [$title.' · '.$from.' s/d '.$to], ';');
            if ($rows) {
                fputcsv($out, array_keys($rows[0]), ';');
                foreach ($rows as $row) {
                    fputcsv($out, array_values($row), ';');
                }
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
