<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Khanza\KhanzaReports;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

/** Dasbor manajemen: laporan dari database SIMRS Khanza, dibagi per tab dan dimuat saat tab dibuka. */
class ManagementController extends Controller
{
    public const PRESETS = ['hari-ini' => 'Hari ini', '7-hari' => '7 hari terakhir', 'bulan-ini' => 'Bulan ini', 'bulan-lalu' => 'Bulan lalu', 'tahun-ini' => 'Tahun ini'];

    /** Tab => [judul, bagian yang dibutuhkan: ops / finance / any]. */
    public const TABS = [
        'ringkasan' => ['Ringkasan', 'any'],
        'pelayanan' => ['Pelayanan', 'ops'],
        'ranap' => ['Rawat inap', 'ops'],
        'penunjang' => ['Penunjang & farmasi', 'ops'],
        'keuangan' => ['Keuangan', 'finance'],
        'bpjs' => ['Klaim BPJS (INA-CBG)', 'finance'],
    ];

    protected function scope(Request $request): array
    {
        $role = $request->user()->role;

        return ['ops' => $role->canSee('ops'), 'fin' => $role->canSee('finance')];
    }

    protected function tabsFor(array $sc): array
    {
        return array_filter(self::TABS, fn ($t) => $t[1] === 'any' || ($t[1] === 'ops' && $sc['ops']) || ($t[1] === 'finance' && $sc['fin']));
    }

    /** @return array{0: string, 1: string, 2: string} */
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

    protected function pq(string $from, string $to, string $preset): array
    {
        return ['periode' => $preset] + ($preset === 'custom' ? ['dari' => $from, 'sampai' => $to] : []);
    }

    public function index(Request $request)
    {
        [$from, $to, $preset] = $this->period($request);
        $sc = $this->scope($request);
        $base = ['from' => $from, 'to' => $to, 'preset' => $preset, 'presets' => self::PRESETS, 'tabs' => $this->tabsFor($sc), 'pq' => $this->pq($from, $to, $preset)] + $sc;
        if (! KhanzaReports::configured()) {
            return view('admin.management', $base + ['configured' => false]);
        }
        $connError = null;
        try {
            KhanzaReports::ping();
        } catch (Throwable $e) {
            $connError = mb_substr($e->getMessage(), 0, 240);
        }
        if ($request->boolean('segarkan')) {
            ActivityLog::record('Memuat ulang data Dasbor manajemen');
        }

        return view('admin.management', $base + ['configured' => true, 'connError' => $connError, 'fresh' => $request->boolean('segarkan')]);
    }

    /** Isi satu tab (HTML), dimuat lewat JavaScript saat tab dibuka. */
    public function section(Request $request, string $tab)
    {
        $sc = $this->scope($request);
        abort_unless(isset($this->tabsFor($sc)[$tab]) && KhanzaReports::configured(), 404);
        [$from, $to, $preset] = $this->period($request);
        $r = new KhanzaReports($from, $to, $request->boolean('segarkan'));
        $ops = $sc['ops'];
        $fin = $sc['fin'];
        $d = [];
        switch ($tab) {
            case 'ringkasan':
                if ($ops) {
                    $d += ['summary' => $r->summary(), 'prev' => $r->previousSummary(), 'ly' => $r->lastYear()->summary(), 'now' => $r->inpatientNow(),
                        'ind' => $r->inpatientIndicators(), 'wait' => $r->waitCompliance(), 'readm' => $r->readmission(),
                        'lowStock' => $r->lowStock(), 'expiring' => $r->expiring(), 'months' => $r->monthlyVisits()];
                }
                if ($fin) {
                    $ly = $r->lastYear();
                    $d += ['payments' => $r->payments(), 'receivables' => $r->receivables(), 'lyPayments' => $ly->payments(), 'lyReceivables' => $ly->receivables(),
                        'aging' => $r->receivableAging(), 'payables' => $r->payables(), 'revMonths' => $r->monthlyRevenue()];
                }
                $d['targets'] = SiteSettings::get('mg_targets');
                break;
            case 'pelayanan':
                $d = ['trend' => $r->trend(), 'clinics' => $r->byClinic(), 'payers' => $r->byPayer(), 'doctors' => $r->doctors(),
                    'punctuality' => $r->doctorPunctuality(), 'busy' => $r->busyHours(), 'antrolStatus' => $r->antrolStatus(),
                    'antrolClinic' => $r->antrolClinic(), 'antrolTimes' => $r->antrolTimes(), 'diagnoses' => $r->topDiagnoses(),
                    'regions' => $r->regions(), 'demography' => $r->demography(), 'referrals' => $r->referralsIn(), 'wait' => $r->waitCompliance()];
                break;
            case 'ranap':
                $d = ['now' => $r->inpatientNow(), 'ind' => $r->inpatientIndicators(), 'wards' => $r->wards(), 'discharge' => $r->dischargeStatus(),
                    'dpjp' => $r->inpatientByDoctor(), 'readm' => $r->readmission()];
                break;
            case 'penunjang':
                $d = ['support' => $r->support(), 'tat' => $r->resultTurnaround(), 'rx' => $r->prescriptionWait(), 'surg' => $r->surgeries(),
                    'labCat' => $r->labCategories(), 'drugs' => $r->topDrugs(), 'lowStock' => $r->lowStock(), 'expiring' => $r->expiring()];
                break;
            case 'keuangan':
                $d = ['payments' => $r->payments(), 'receivables' => $r->receivables(), 'aging' => $r->receivableAging(), 'paid' => $r->receivablePayments(),
                    'billing' => $r->billingCategories(), 'byClinic' => $r->revenueByClinic(), 'byDoctor' => $r->revenueByDoctor(), 'fees' => $r->doctorFees(),
                    'payables' => $r->payables(), 'expenses' => $r->expenses(), 'margin' => $r->pharmacyMargin(), 'support' => $r->support(), 'drugs' => $r->topDrugs()];
                break;
            case 'bpjs':
                $d = ['cbg' => $r->inacbg()];
                break;
        }

        return view('admin.management.tab-'.$tab, $d + $sc + [
            'errors_' => $r->errors, 'from' => $from, 'to' => $to, 'days' => $r->days, 'pq' => $this->pq($from, $to, $preset),
            'canTarget' => $ops && $fin,
        ]);
    }

    /** Rincian satu poliklinik (dokter dan diagnosa), ditampilkan dalam jendela kecil. */
    public function clinic(Request $request, string $kd)
    {
        abort_unless($this->scope($request)['ops'] && KhanzaReports::configured(), 403);
        abort_unless(preg_match('/^[A-Za-z0-9_\-]{1,10}$/', $kd), 404);
        [$from, $to] = $this->period($request);
        $r = new KhanzaReports($from, $to);

        return view('admin.management.clinic', $r->clinicDetail($kd) + ['name' => (string) $request->query('nama', $kd), 'errors_' => $r->errors]);
    }

    /** Target bulanan (hanya Komisaris). */
    public function targets(Request $request)
    {
        $sc = $this->scope($request);
        abort_unless($sc['ops'] && $sc['fin'], 403);
        $data = $request->validate([
            'kunjungan' => 'nullable|integer|min:0|max:10000000', 'pendapatan' => 'nullable|integer|min:0|max:1000000000000',
            'bor' => 'nullable|numeric|min:0|max:100',
        ]);
        SiteSettings::put('mg_targets', ['kunjungan' => (int) ($data['kunjungan'] ?? 0), 'pendapatan' => (int) ($data['pendapatan'] ?? 0), 'bor' => (float) ($data['bor'] ?? 0)]);
        ActivityLog::record('Memperbarui target Dasbor manajemen');

        return back()->with('ok', 'Target bulanan disimpan.');
    }

    /** Unduh satu laporan sebagai CSV. */
    public function export(Request $request, string $report)
    {
        $map = KhanzaReports::exports();
        abort_unless(isset($map[$report]) && KhanzaReports::configured(), 404);
        $sc = $this->scope($request);
        [$title, $method, $need] = $map[$report];
        $key = $map[$report][3] ?? null;
        $allowed = match ($need) { 'finance' => $sc['fin'], 'ops' => $sc['ops'], default => $sc['ops'] || $sc['fin'] };
        abort_unless($allowed, 403, 'Laporan ini tidak termasuk hak akses Anda.');
        [$from, $to] = $this->period($request);
        $r = new KhanzaReports($from, $to);
        $rows = $r->{$method}();
        if ($key) {
            $rows = $rows[$key] ?? [];
        } elseif ($rows && ! array_is_list($rows)) {
            $rows = [$rows];
        }
        if ($report === 'obat' && ! $sc['fin']) {
            $rows = array_map(fn ($x) => array_diff_key($x, ['nilai' => 1]), $rows);
        }
        abort_if($r->errors && ! $rows, 500, 'Laporan tidak bisa dibaca dari database Khanza.');
        ActivityLog::record('Mengunduh laporan '.$title.' ('.$from.' s/d '.$to.')');

        return response()->streamDownload(function () use ($rows, $title, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [$title.' · '.$from.' s/d '.$to], ';');
            if ($rows) {
                fputcsv($out, array_keys($rows[0]), ';');
                foreach ($rows as $row) {
                    fputcsv($out, array_values($row), ';');
                }
            }
            fclose($out);
        }, 'laporan-'.$report.'-'.$from.'-sd-'.$to.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
