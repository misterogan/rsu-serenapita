<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClosingAccount;
use App\Models\ClosingAdjustment;
use App\Models\ClosingMapping;
use App\Models\ClosingSnapshot;
use App\Services\Khanza\KhanzaClosing;
use App\Services\Khanza\KhanzaReports;
use App\Support\ClosingBook;
use App\Support\XlsxReader;
use App\Support\XlsxWriter as X;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Closing akuntansi: merapikan data SIMRS Khanza sampai tanggal cut off untuk tutup buku.
 *
 * Alur: ambil data Khanza (disalin ke database website) → petakan rekening Khanza ke COA baru →
 * periksa neraca saldo dan rekonsiliasi → buat jurnal penyesuaian → unduh saldo awal untuk SIMRS baru → kunci.
 * Melihat: peran dengan hak keuangan (Akunting, Komisaris). Mengubah: hanya peran dengan hak management:closing (Akunting).
 */
class ClosingController extends Controller
{
    public const SECTIONS = [
        'index' => 'Ringkasan', 'coa' => 'COA baru', 'mapping' => 'Pemetaan akun', 'trial' => 'Neraca saldo',
        'statements' => 'Laporan keuangan', 'reconcile' => 'Rekonsiliasi', 'carryover' => 'Ranap lanjutan', 'adjustments' => 'Jurnal penyesuaian',
    ];

    protected function book(Request $request): ClosingBook
    {
        abort_unless($request->user()->role?->canSee('finance'), 403, 'Closing akuntansi hanya untuk bagian keuangan.');

        return new ClosingBook;
    }

    protected function canEdit(Request $request): bool
    {
        return (bool) $request->user()->role?->canEditClosing();
    }

    /** Pastikan pengguna boleh mengubah dan closing belum dikunci. */
    protected function editable(Request $request, ClosingBook $book, bool $evenLocked = false): void
    {
        abort_unless($this->canEdit($request), 403, 'Peran Anda hanya bisa melihat closing.');
        if (! $evenLocked && $book->locked()) {
            throw ValidationException::withMessages(['locked' => 'Closing sudah dikunci. Buka kunci di halaman Ringkasan untuk mengubah.']);
        }
    }

    protected function view(string $name, Request $request, ClosingBook $book, array $data = [])
    {
        return view('admin.closing.'.$name, $data + ['book' => $book, 'canEdit' => $this->canEdit($request), 'section' => $name, 'sections' => self::SECTIONS]);
    }

    /* ---------------- Ringkasan ---------------- */

    public function index(Request $request)
    {
        $book = $this->book($request);

        return $this->view('index', $request, $book, [
            'checklist' => $book->checklist(), 'summary' => $book->snap ? $book->balanceSummary() : null,
            'configured' => KhanzaReports::configured(), 'openings' => KhanzaClosing::OPENINGS,
        ]);
    }

    public function settings(Request $request)
    {
        $book = $this->book($request);
        $this->editable($request, $book);
        $data = $request->validate([
            'cutoff' => 'required|date_format:Y-m-d',
            'year_start' => 'required|date_format:Y-m-d|before_or_equal:cutoff',
            'opening' => 'required|in:'.implode(',', array_keys(KhanzaClosing::OPENINGS)),
        ], [], ['cutoff' => 'tanggal cut off', 'year_start' => 'awal tahun buku', 'opening' => 'sumber saldo awal']);
        if (Carbon::parse($data['year_start'])->diffInDays(Carbon::parse($data['cutoff'])) > 366) {
            throw ValidationException::withMessages(['year_start' => 'Rentang awal tahun buku sampai cut off paling lama satu tahun.']);
        }
        ClosingBook::saveSettings($data);
        ActivityLog::record("Closing: pengaturan cut off {$data['cutoff']}, awal tahun {$data['year_start']}, saldo awal {$data['opening']}");

        return back()->with('ok', 'Pengaturan closing disimpan.'.($book->snap ? ' Ambil ulang data Khanza agar angka sesuai pengaturan baru.' : ''));
    }

    /** Ambil data dari Khanza dan simpan sebagai salinan (snapshot). */
    public function snapshot(Request $request)
    {
        $book = $this->book($request);
        $this->editable($request, $book);
        if (! KhanzaReports::configured()) {
            return back()->with('err', 'Koneksi database Khanza belum diatur. Minta tim IT mengisi KHANZA_DB_* di file .env (lihat database/khanza/README.txt).');
        }
        try {
            KhanzaReports::ping();
        } catch (Throwable $e) {
            return back()->with('err', 'Tidak bisa terhubung ke database Khanza: '.mb_substr($e->getMessage(), 0, 200));
        }
        $s = $book->settings;
        $khanza = new KhanzaClosing($s['year_start'], $s['cutoff'], $s['opening']);
        $data = $khanza->collect();
        if (! $data['akun']) {
            return back()->with('err', 'Daftar rekening Khanza tidak bisa dibaca: '.implode(' · ', $khanza->errors));
        }
        DB::transaction(function () use ($s, $khanza, $data, $request) {
            $snap = ClosingSnapshot::create(['cutoff' => $s['cutoff'], 'year_start' => $s['year_start'], 'opening' => $s['opening'],
                'errors' => $khanza->errors, 'user_id' => $request->user()->id, 'user_name' => $request->user()->name]);
            foreach ($data as $dataset => $rows) {
                $snap->store($dataset, $rows);
            }
            // Simpan 3 salinan terakhir saja
            $old = ClosingSnapshot::query()->where('id', '<', $snap->id)->orderByDesc('id')->skip(2)->take(100)->pluck('id');
            DB::table('closing_snapshot_data')->whereIn('snapshot_id', $old)->delete();
            ClosingSnapshot::query()->whereIn('id', $old)->delete();
        });
        ActivityLog::record("Closing: mengambil data Khanza s.d. {$s['cutoff']}");
        $msg = 'Data Khanza s.d. '.Carbon::parse($s['cutoff'])->translatedFormat('d F Y').' berhasil diambil: '.count($data['akun']).' rekening, '.count($data['mutasi']).' baris mutasi.';

        return back()->with($khanza->errors ? 'err' : 'ok', $msg.($khanza->errors ? ' Sebagian gagal dibaca: '.implode(', ', array_keys($khanza->errors)).'.' : ''));
    }

    public function lock(Request $request)
    {
        $book = $this->book($request);
        $this->editable($request, $book, true);
        if ($request->input('action') === 'unlock') {
            ClosingBook::saveSettings(['locked' => false, 'locked_at' => null, 'locked_by' => null]);
            ActivityLog::record('Closing: membuka kunci');

            return back()->with('ok', 'Kunci closing dibuka. Data bisa diubah lagi.');
        }
        $tb = $book->trialBalance()['total'];
        $unmapped = collect($book->khanzaAccounts())->where('aktif', true)->whereNull('coa')->count();
        if (! $book->snapshotCurrent() || $unmapped || abs($tb['selisih']) >= 1) {
            return back()->with('err', 'Belum bisa dikunci: pastikan data Khanza terbaru sudah diambil, semua rekening dipetakan, dan neraca saldo seimbang.');
        }
        ClosingBook::saveSettings(['locked' => true, 'locked_at' => now()->toDateTimeString(), 'locked_by' => $request->user()->name]);
        ActivityLog::record('Closing: mengunci closing per '.$book->settings['cutoff']);

        return back()->with('ok', 'Closing dikunci. Angka tidak bisa berubah sampai kunci dibuka.');
    }

    /* ---------------- COA ---------------- */

    public function coa(Request $request)
    {
        $book = $this->book($request);
        $used = ClosingMapping::query()->whereNotNull('coa_code')->selectRaw('coa_code, COUNT(*) AS n')->groupBy('coa_code')->pluck('n', 'coa_code')->all();

        return $this->view('coa', $request, $book, ['used' => $used]);
    }

    public function importCoa(Request $request)
    {
        $book = $this->book($request);
        $this->editable($request, $book);
        $request->validate(['file' => 'required|file|max:5120|mimes:xlsx'], [], ['file' => 'file COA']);
        try {
            $rows = $this->parseCoa($request->file('file')->getRealPath());
        } catch (Throwable $e) {
            return back()->with('err', $e->getMessage());
        }
        DB::transaction(function () use ($rows) {
            ClosingAccount::query()->delete();
            $now = now();
            foreach (array_chunk($rows, 200) as $chunk) {
                ClosingAccount::insert(array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk));
            }
        });
        $codes = array_column(array_filter($rows, fn ($r) => $r['type'] === 'detail'), 'code');
        $orphan = ClosingMapping::query()->whereNotNull('coa_code')->whereNotIn('coa_code', $codes)->count();
        ActivityLog::record('Closing: mengunggah COA baru ('.count($rows).' akun)');

        return redirect()->route('admin.closing.coa')->with($orphan ? 'err' : 'ok', count($rows).' akun COA dimuat.'
            .($orphan ? " {$orphan} pemetaan memakai kode yang tidak ada lagi di COA ini; periksa menu Pemetaan akun." : ''));
    }

    /**
     * Baca file template COA (kolom ID, Account Number, Account Name, Company, Parent Account, Is Group/Type Akun, Kategori Akun).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseCoa(string $path): array
    {
        foreach (XlsxReader::sheets($path) as $rows) {
            foreach ($rows as $i => $row) {
                $head = array_map(fn ($v) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $v))), $row);
                $col = fn (string $needle) => collect($head)->search(fn ($h) => str_starts_with($h, $needle));
                $code = $col('account number');
                $name = $col('account name');
                if ($code === false || $name === false) {
                    continue;
                }
                $id = $col('id');
                $parent = $col('parent');
                $type = $col('is group');
                $cat = $col('kategori');
                $company = $col('company');
                $body = array_filter(array_slice($rows, $i + 1), fn ($r) => trim((string) ($r[$code] ?? '')) !== '');
                $ids = [];
                foreach ($body as $r) {
                    $ids[trim((string) ($id !== false ? $r[$id] ?? '' : ''))] = trim((string) $r[$code]);
                }
                $out = [];
                $seen = [];
                foreach (array_values($body) as $n => $r) {
                    $c = trim((string) $r[$code]);
                    if (isset($seen[$c])) {
                        throw new \RuntimeException("Kode akun {$c} muncul lebih dari sekali. Account Number harus unik.");
                    }
                    $seen[$c] = true;
                    $p = $parent !== false ? trim((string) ($r[$parent] ?? '')) : '';
                    $t = strtolower((string) ($type !== false ? $r[$type] ?? '' : ''));
                    $out[] = [
                        'code' => $c, 'name' => mb_substr(trim((string) $r[$name]), 0, 150),
                        'parent_code' => $p === '' ? null : ($ids[$p] ?? (preg_match('/^\S+/', $p, $m) && in_array($m[0], $ids, true) ? $m[0] : $p)),
                        'type' => str_contains($t, 'child') || str_contains($t, 'detail') ? 'detail' : (str_contains($t, 'sub') ? 'sub' : 'header'),
                        'category' => mb_substr(trim((string) ($cat !== false ? $r[$cat] ?? '' : '')), 0, 30) ?: 'Lain-lain',
                        'company' => $company !== false ? (mb_substr(trim((string) ($r[$company] ?? '')), 0, 80) ?: null) : null,
                        'sort' => $n + 1,
                    ];
                }
                if (! $out) {
                    throw new \RuntimeException('Template COA tidak berisi akun.');
                }
                $known = array_column($out, 'code');
                foreach ($out as $a) {
                    if ($a['parent_code'] !== null && ! in_array($a['parent_code'], $known, true)) {
                        throw new \RuntimeException("Parent akun {$a['code']} ({$a['parent_code']}) tidak ditemukan. Parent harus ada di daftar.");
                    }
                }

                return $out;
            }
        }
        throw new \RuntimeException('Kolom "Account Number" dan "Account Name" tidak ditemukan. Gunakan format template COA.');
    }

    /* ---------------- Pemetaan ---------------- */

    public function mapping(Request $request)
    {
        $book = $this->book($request);
        $f = $request->query('f', 'aktif');
        $q = mb_strtolower(trim((string) $request->query('q', '')));
        $rows = collect($book->khanzaAccounts())->filter(fn ($r) => match ($f) {
            'belum' => $r['aktif'] && ! $r['coa'],
            'beda' => $r['beda_tipe'] || $r['coa_invalid'],
            'semua' => true,
            default => $r['aktif'],
        })->filter(fn ($r) => $q === '' || str_contains(mb_strtolower($r['kd_rek'].' '.$r['nm_rek'].' '.$r['coa']), $q));

        return $this->view('mapping', $request, $book, ['rows' => $rows, 'f' => $f, 'q' => $q,
            'suggestible' => collect($book->khanzaAccounts())->filter(fn ($r) => $r['aktif'] && ! $r['coa'] && $r['saran'])->count()]);
    }

    public function saveMapping(Request $request)
    {
        $book = $this->book($request);
        $this->editable($request, $book);
        $valid = $book->detailCodes();
        $known = array_keys($book->khanzaAccounts());
        $input = (array) $request->input('map', []);
        $bad = [];
        $n = 0;
        foreach ($input as $kd => $code) {
            $kd = (string) $kd;
            $code = trim((string) $code);
            if (! in_array($kd, array_map('strval', $known), true)) {
                continue;
            }
            if ($code !== '' && ! in_array($code, $valid, true)) {
                $bad[] = "{$kd} → {$code}";

                continue;
            }
            $current = ClosingMapping::query()->where('kd_rek', $kd)->value('coa_code');
            if ((string) $current === $code) {
                continue;
            }
            ClosingMapping::updateOrCreate(['kd_rek' => $kd], ['coa_code' => $code === '' ? null : $code, 'user_name' => $request->user()->name]);
            $n++;
        }
        if ($n) {
            ActivityLog::record("Closing: mengubah {$n} pemetaan akun");
        }
        if ($bad) {
            return back()->withInput()->with('err', count($bad).' pemetaan tidak disimpan karena kode bukan akun detail COA: '.implode(', ', array_slice($bad, 0, 8)).(count($bad) > 8 ? ', …' : ''));
        }

        return back()->with('ok', $n ? "{$n} pemetaan disimpan." : 'Tidak ada perubahan.');
    }

    public function applySuggestions(Request $request)
    {
        $book = $this->book($request);
        $this->editable($request, $book);
        $n = 0;
        foreach ($book->khanzaAccounts() as $kd => $r) {
            if ($r['aktif'] && ! $r['coa'] && $r['saran']) {
                ClosingMapping::updateOrCreate(['kd_rek' => (string) $kd], ['coa_code' => $r['saran'], 'user_name' => $request->user()->name.' (saran)']);
                $n++;
            }
        }
        ActivityLog::record("Closing: menerapkan {$n} saran pemetaan");

        return back()->with('ok', "{$n} saran pemetaan diterapkan. Periksa kembali sebelum closing dikunci.");
    }

    /* ---------------- Laporan ---------------- */

    public function trial(Request $request)
    {
        $book = $this->book($request);

        return $this->view('trial', $request, $book, ['tb' => $book->trialBalance(), 'kh' => $book->khanzaAccounts(), 'detail' => $request->boolean('rinci')]);
    }

    public function statements(Request $request)
    {
        $book = $this->book($request);

        return $this->view('statements', $request, $book, [
            'neraca' => $book->statement(ClosingAccount::BALANCE_SHEET), 'lr' => $book->statement(['Pendapatan', 'Beban', 'Lain-lain']),
            'summary' => $book->balanceSummary(), 'monthly' => $book->monthlyPL(), 'months' => $book->months(),
        ]);
    }

    public function reconcile(Request $request)
    {
        $book = $this->book($request);

        return $this->view('reconcile', $request, $book, ['recon' => $book->reconciliation()]);
    }

    /** Pasien rawat inap yang tetap dilayani di Khanza setelah go-live SIMRS baru, beserta jurnalnya. */
    public function carryover(Request $request)
    {
        $book = $this->book($request);

        return $this->view('carryover', $request, $book, ['co' => $book->carryOver()]);
    }

    /* ---------------- Jurnal penyesuaian ---------------- */

    public function adjustments(Request $request)
    {
        $book = $this->book($request);

        return $this->view('adjustments', $request, $book, ['items' => ClosingAdjustment::query()->with('lines')->orderBy('date')->orderBy('id')->get()]);
    }

    public function createAdjustment(Request $request)
    {
        $book = $this->book($request);
        $this->editable($request, $book);
        $lines = collect((array) $request->query('lines', []))->map(fn ($l) => [
            'coa_code' => (string) ($l['coa'] ?? ''), 'debit' => (float) ($l['debit'] ?? 0), 'credit' => (float) ($l['credit'] ?? 0), 'memo' => (string) ($l['memo'] ?? ''),
        ])->take(50)->values()->all();

        return $this->view('adjustment-form', $request, $book, ['adj' => new ClosingAdjustment([
            'number' => ClosingAdjustment::nextNumber(), 'date' => $request->query('date') ?: $book->settings['cutoff'], 'description' => (string) $request->query('desc', ''),
            'kind' => $request->query('kind') === 'koreksi' ? 'koreksi' : 'penyesuaian',
        ]), 'lines' => $lines ?: [[], []]]);
    }

    public function editAdjustment(Request $request, ClosingAdjustment $adjustment)
    {
        $book = $this->book($request);
        $this->editable($request, $book);

        return $this->view('adjustment-form', $request, $book, ['adj' => $adjustment, 'lines' => $adjustment->lines->toArray()]);
    }

    public function storeAdjustment(Request $request)
    {
        $book = $this->book($request);
        $this->editable($request, $book);

        return $this->saveAdjustment($request, $book, new ClosingAdjustment);
    }

    public function updateAdjustment(Request $request, ClosingAdjustment $adjustment)
    {
        $book = $this->book($request);
        $this->editable($request, $book);

        return $this->saveAdjustment($request, $book, $adjustment);
    }

    protected function saveAdjustment(Request $request, ClosingBook $book, ClosingAdjustment $adj)
    {
        $s = $book->settings;
        $data = $request->validate([
            'date' => "required|date_format:Y-m-d|after_or_equal:{$s['year_start']}|before_or_equal:{$s['cutoff']}",
            'description' => 'required|string|max:250',
            'kind' => 'required|in:'.implode(',', array_keys(ClosingAdjustment::KINDS)),
            'lines' => 'required|array|min:1|max:100',
            'lines.*.coa_code' => 'nullable|string|max:30',
            'lines.*.debit' => 'nullable|numeric|min:0|max:999999999999',
            'lines.*.credit' => 'nullable|numeric|min:0|max:999999999999',
            'lines.*.memo' => 'nullable|string|max:200',
        ], ['date.after_or_equal' => 'Tanggal jurnal harus di antara awal tahun buku dan tanggal cut off.', 'date.before_or_equal' => 'Tanggal jurnal harus di antara awal tahun buku dan tanggal cut off.'],
            ['date' => 'tanggal', 'description' => 'keterangan', 'lines' => 'baris jurnal']);
        $valid = $book->detailCodes();
        $lines = [];
        foreach ($data['lines'] as $i => $l) {
            $d = round((float) ($l['debit'] ?? 0), 2);
            $c = round((float) ($l['credit'] ?? 0), 2);
            $code = trim((string) ($l['coa_code'] ?? ''));
            if ($code === '' && $d == 0 && $c == 0) {
                continue; // baris kosong
            }
            $row = $i + 1;
            if (! in_array($code, $valid, true)) {
                throw ValidationException::withMessages(["lines.{$i}.coa_code" => "Baris {$row}: akun \"{$code}\" bukan akun detail COA."]);
            }
            if (($d > 0) === ($c > 0)) {
                throw ValidationException::withMessages(["lines.{$i}.debit" => "Baris {$row}: isi salah satu, debet atau kredit."]);
            }
            $lines[] = ['coa_code' => $code, 'debit' => $d, 'credit' => $c, 'memo' => ($l['memo'] ?? null) ?: null];
        }
        $td = round(array_sum(array_column($lines, 'debit')), 2);
        $tc = round(array_sum(array_column($lines, 'credit')), 2);
        $correction = $data['kind'] === 'koreksi';
        if (count($lines) < ($correction ? 1 : 2)) {
            throw ValidationException::withMessages(['lines' => $correction ? 'Isi minimal satu baris.' : 'Jurnal minimal dua baris (debet dan kredit).']);
        }
        if (! $correction && abs($td - $tc) >= 0.01) {
            throw ValidationException::withMessages(['lines' => 'Jurnal tidak seimbang: debet '.number_format($td, 2, ',', '.').' ≠ kredit '.number_format($tc, 2, ',', '.').'.']);
        }
        $isNew = ! $adj->exists;
        DB::transaction(function () use ($adj, $data, $lines, $request) {
            $adj->fill(['date' => $data['date'], 'description' => $data['description'], 'kind' => $data['kind']]);
            if (! $adj->exists) {
                $adj->fill(['number' => ClosingAdjustment::nextNumber(), 'user_id' => $request->user()->id, 'user_name' => $request->user()->name]);
            }
            $adj->save();
            $adj->lines()->delete();
            $adj->lines()->createMany($lines);
        });
        ActivityLog::record(($isNew ? 'Closing: membuat jurnal penyesuaian ' : 'Closing: mengubah jurnal penyesuaian ').$adj->number);

        return redirect()->route('admin.closing.adjustments')->with('ok', "Jurnal {$adj->number} disimpan.");
    }

    public function destroyAdjustment(Request $request, ClosingAdjustment $adjustment)
    {
        $book = $this->book($request);
        $this->editable($request, $book);
        $adjustment->delete();
        ActivityLog::record('Closing: menghapus jurnal penyesuaian '.$adjustment->number);

        return back()->with('ok', "Jurnal {$adjustment->number} dihapus.");
    }

    /* ---------------- Unduhan Excel ---------------- */

    public function export(Request $request, string $sheet)
    {
        $book = $this->book($request);
        $mode = $request->query('mode') === 'rinci' ? 'rinci' : 'tutup';
        $all = $this->sheets($book, $mode);
        abort_unless($sheet === 'paket' || isset($all[$sheet]), 404);
        $pick = $sheet === 'paket' ? $all : [$sheet => $all[$sheet]];
        $path = X::book(array_values($pick));
        ActivityLog::record('Closing: mengunduh '.($sheet === 'paket' ? 'paket closing' : $all[$sheet][0]));
        $name = ($sheet === 'paket' ? 'paket-closing' : $sheet).'-khanza-sd-'.$book->settings['cutoff'].'.xlsx';

        return response()->download($path, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend();
    }

    /** @return array<string, array{0: string, 1: string[], 2: array, 3: iterable}> */
    protected function sheets(ClosingBook $book, string $mode): array
    {
        $s = $book->settings;
        $period = 'Data SIMRS Khanza '.Carbon::parse($s['year_start'])->translatedFormat('d M Y').' s.d. '.Carbon::parse($s['cutoff'])->translatedFormat('d M Y');
        $stamp = 'Dibuat '.now()->translatedFormat('d M Y H:i').($book->locked() ? ' · closing DIKUNCI' : ' · closing BELUM dikunci (angka masih bisa berubah)');
        $snap = $book->snap;
        $money = fn (string $t, int $w = 16) => [$t, X::DEC, $w];
        $tb = $book->trialBalance();
        $kh = $book->khanzaAccounts();
        $openDate = Carbon::parse($s['cutoff'])->addDay()->translatedFormat('d F Y');
        $acc = fn ($code) => $book->account($code);

        $sheets = [];
        $sheets['saldo-awal'] = ['Saldo awal SIMRS baru', ['Saldo awal untuk SIMRS baru per '.$openDate, $period.' · '.($mode === 'tutup' ? 'laba rugi ditutup ke akun '.config('closing.current_earnings') : 'saldo laba rugi dibawa per akun'), $stamp],
            [['Account Number', X::TEXT, 14], ['Account Name', X::TEXT, 42], ['Parent Account', X::TEXT, 14], ['Kategori Akun', X::TEXT, 13], $money('Debit'), $money('Kredit')],
            array_map(fn ($r) => [$r['code'], $r['name'], $r['parent'], $r['category'], $r['debit'], $r['credit']], $book->opening($mode))];
        $sheets['neraca-saldo'] = ['Neraca saldo', ['Neraca saldo per akun COA baru', $period, $stamp],
            [['Kode', X::TEXT, 10], ['Nama akun', X::TEXT, 40], ['Kategori', X::TEXT, 12], $money('Saldo awal (D−K)'), $money('Mutasi debet'), $money('Mutasi kredit'),
                $money('Saldo Khanza debet'), $money('Saldo Khanza kredit'), $money('Penyesuaian debet'), $money('Penyesuaian kredit'), $money('Saldo akhir debet'), $money('Saldo akhir kredit'), ['Rekening Khanza', X::TEXT, 40]],
            array_merge(array_map(fn ($r) => [$r['code'] ?: '—', $r['name'], $r['category'], $r['awal'], $r['debet'], $r['kredit'], max(0, $r['khanza']), max(0, -$r['khanza']),
                $r['adj_d'], $r['adj_k'], max(0, $r['akhir']), max(0, -$r['akhir']), implode(', ', $r['sumber'])], array_values($tb['rows'])),
                [['', 'TOTAL', '', $tb['total']['awal'], $tb['total']['debet'], $tb['total']['kredit'], $tb['total']['khanza_d'], $tb['total']['khanza_k'], $tb['total']['adj_d'], $tb['total']['adj_k'], $tb['total']['akhir_d'], $tb['total']['akhir_k'], '']])];
        $st = fn (array $lines) => array_map(fn ($l) => [$l['code'], str_repeat('    ', $l['depth']).$l['name'], $l['type'] === 'detail' ? '' : 'Jumlah', $l['amount']], $lines);
        $sum = $book->balanceSummary();
        $sheets['neraca'] = ['Neraca', ['Neraca per '.Carbon::parse($s['cutoff'])->translatedFormat('d F Y'), $period, $stamp],
            [['Kode', X::TEXT, 10], ['Akun', X::TEXT, 48], ['', X::TEXT, 8], $money('Jumlah', 18)],
            array_merge($st($book->statement(ClosingAccount::BALANCE_SHEET)), [['', 'Laba (rugi) tahun berjalan s.d. cut off (belum ditutup)', '', $sum['laba']],
                ['', 'Total kewajiban + ekuitas + laba berjalan', '', round($sum['kewajiban'] + $sum['ekuitas'] + $sum['laba'], 2)], ['', 'Selisih neraca', '', $sum['selisih']]])];
        $sheets['laba-rugi'] = ['Laba rugi', ['Laba rugi '.Carbon::parse($s['year_start'])->translatedFormat('d M Y').' s.d. '.Carbon::parse($s['cutoff'])->translatedFormat('d M Y'), $period, $stamp],
            [['Kode', X::TEXT, 10], ['Akun', X::TEXT, 48], ['', X::TEXT, 8], $money('Jumlah', 18)],
            array_merge($st($book->statement(['Pendapatan', 'Beban', 'Lain-lain'])), [['', 'LABA (RUGI) BERSIH', '', $sum['laba']]])];
        $months = $book->months();
        $monthly = $book->monthlyPL();
        $sheets['laba-rugi-bulanan'] = ['Laba rugi per bulan', ['Laba rugi per bulan per akun COA (nilai normal: pendapatan positif, beban positif)', $period, $stamp],
            array_merge([['Kode', X::TEXT, 10], ['Nama akun', X::TEXT, 38], ['Kategori', X::TEXT, 12]], array_map(fn ($m) => $money(Carbon::parse($m.'-01')->translatedFormat('M Y'), 15), $months), [$money('Total', 17)]),
            collect($book->coa)->filter(fn ($a) => $a->isDetail() && isset($monthly[$a->code]))->map(fn ($a) => array_merge([$a->code, $a->name, $a->category],
                array_map(fn ($m) => $monthly[$a->code][$m] ?? 0.0, $months), [round(array_sum($monthly[$a->code]), 2)]))->values()->all()];
        $sheets['pemetaan'] = ['Pemetaan akun', ['Pemetaan rekening Khanza ke COA baru', $period, $stamp],
            [['Kode Khanza', X::TEXT, 14], ['Nama rekening Khanza', X::TEXT, 40], ['Tipe', X::TEXT, 6], ['Balance', X::TEXT, 8], $money('Saldo awal (D−K)'), $money('Debet'), $money('Kredit'), $money('Saldo akhir (D−K)'), ['Kode COA', X::TEXT, 10], ['Nama akun COA', X::TEXT, 36], ['Catatan', X::TEXT, 30]],
            array_map(fn ($r) => [$r['kd_rek'], $r['nm_rek'], $r['tipe'], $r['balance'], $r['awal'], $r['debet'], $r['kredit'], $r['akhir'], $r['coa'], $acc($r['coa'])?->name,
                trim(($r['terdaftar'] ? '' : 'Tidak ada di tabel rekening. ').($r['coa'] ? '' : ($r['aktif'] ? 'BELUM DIPETAKAN. ' : '')).($r['beda_tipe'] ? 'Jenis rekening berbeda dengan kategori COA.' : ''))],
                array_values(array_filter($kh, fn ($r) => $r['aktif'] || $r['coa'])))];
        $adjRows = [];
        foreach (ClosingAdjustment::query()->with('lines')->orderBy('date')->orderBy('id')->get() as $a) {
            foreach ($a->lines as $l) {
                $adjRows[] = [$a->number, $a->date->toDateString(), $a->isCorrection() ? 'Koreksi' : 'Penyesuaian', $a->description, $l->coa_code, $acc($l->coa_code)?->name, $l->debit, $l->credit, $l->memo, $a->user_name];
            }
        }
        $sheets['penyesuaian'] = ['Jurnal penyesuaian', ['Jurnal penyesuaian closing', $period, $stamp],
            [['No. jurnal', X::TEXT, 10], ['Tanggal', X::TEXT, 11], ['Jenis', X::TEXT, 12], ['Keterangan', X::TEXT, 40], ['Kode COA', X::TEXT, 10], ['Nama akun', X::TEXT, 34], $money('Debet'), $money('Kredit'), ['Memo', X::TEXT, 30], ['Dibuat oleh', X::TEXT, 20]], $adjRows];

        $rows = fn (string $ds) => $snap ? $snap->rows($ds) : [];
        $piutangAcc = fn ($r) => ClosingBook::ruleAccount($r['penjamin'], config('closing.piutang_accounts'));
        $sheets['piutang'] = ['Rincian piutang', ['Piutang pasien belum lunas per cut off', $period, $stamp],
            [['No. rawat', X::TEXT, 20], ['No. RM', X::TEXT, 10], ['Pasien', X::TEXT, 28], ['Penjamin', X::TEXT, 22], ['Akun COA', X::TEXT, 9], ['Tgl piutang', X::TEXT, 11], ['Jatuh tempo', X::TEXT, 11], $money('Piutang'), $money('Uang muka'), $money('Dibayar s.d. cut off'), $money('Sisa')],
            array_map(fn ($r) => [$r['no_rawat'], $r['no_rkm_medis'], $r['pasien'], $r['penjamin'], $piutangAcc($r), $r['tgl_piutang'], $r['jatuh_tempo'], $r['piutang'], $r['uang_muka'], $r['dibayar'], $r['sisa']], $rows('piutang'))];
        $sheets['hutang'] = ['Rincian hutang', ['Hutang supplier belum lunas per cut off', $period, $stamp],
            [['Jenis', X::TEXT, 10], ['No. faktur', X::TEXT, 18], ['Supplier', X::TEXT, 30], ['Akun COA', X::TEXT, 9], ['Tgl terima', X::TEXT, 11], ['Tgl faktur', X::TEXT, 11], ['Jatuh tempo', X::TEXT, 11], $money('Tagihan'), $money('Dibayar s.d. cut off'), $money('Sisa')],
            array_map(fn ($r) => [$r['jenis'], $r['no_faktur'], $r['supplier'], config('closing.payable_accounts')[$r['jenis']] ?? '', $r['tgl_terima'], $r['tgl_faktur'], $r['jatuh_tempo'], $r['tagihan'], $r['dibayar'], $r['sisa']], $rows('hutang'))];
        $sheets['persediaan'] = ['Persediaan', ['Persediaan obat/BHP per cut off (stok × harga beli Khanza, perkiraan)', $period, $stamp],
            [['Depo', X::TEXT, 20], ['Kode', X::TEXT, 14], ['Nama barang', X::TEXT, 36], ['Jenis', X::TEXT, 16], ['Akun COA', X::TEXT, 9], ['Satuan', X::TEXT, 8], ['Stok', X::DEC, 10], $money('Harga beli', 14), $money('Nilai')],
            array_map(fn ($r) => [$r['depo'], $r['kode_brng'], $r['nama'], $r['jenis'], ClosingBook::ruleAccount($r['jenis'].' '.$r['nama'], config('closing.stock_accounts')), $r['satuan'], (float) $r['stok'], $r['harga_beli'], $r['nilai']], $rows('persediaan'))];
        $sheets['belum-ditagih'] = ['Belum ditagih', ['Kunjungan s.d. cut off yang belum dibayar/ditagih, dan pasien yang masih dirawat saat cut off', $period, $stamp],
            [['Keterangan', X::TEXT, 16], ['No. rawat', X::TEXT, 20], ['Tanggal', X::TEXT, 11], ['No. RM', X::TEXT, 10], ['Pasien', X::TEXT, 28], ['Layanan', X::TEXT, 9], ['Poli', X::TEXT, 20], ['Penjamin', X::TEXT, 20], ['Status', X::TEXT, 12]],
            array_merge(array_map(fn ($r) => ['Dirawat saat cut off', $r['no_rawat'], $r['tgl_masuk'], $r['no_rkm_medis'], $r['pasien'], 'Ranap', '', $r['penjamin'],
                ($r['masih_dirawat'] ?? true) ? 'Masih dirawat' : 'Pulang '.$r['tgl_pulang']], $rows('dirawat')),
                array_map(fn ($r) => ['Belum ditagih', $r['no_rawat'], $r['tgl_registrasi'], $r['no_rkm_medis'], $r['pasien'], $r['layanan'], $r['poli'], $r['penjamin'], $r['status_periksa']], $rows('belum_ditagih')))];
        $co = $book->carryOver();
        $after = 'Transaksi Khanza setelah cut off '.Carbon::parse($s['cutoff'])->translatedFormat('d M Y').' · TIDAK termasuk saldo awal; catat sebagai transaksi bulan berjalan di SIMRS baru';
        $sheets['ranap-lanjutan'] = ['Ranap lanjutan per bulan', ['Ringkasan jurnal Khanza setelah cut off per bulan per akun COA (bisa diinput sebagai jurnal memorial per bulan di SIMRS baru)', $after, $stamp],
            [['Kelompok', X::TEXT, 15], ['Bulan', X::TEXT, 9], ['Kode COA', X::TEXT, 10], ['Nama akun', X::TEXT, 40], $money('Debit'), $money('Kredit')],
            array_map(fn ($r) => [$r['kelompok'], $r['bulan'], $r['code'], $r['name'], $r['debit'], $r['credit']], $co['byCoa'])];
        $sheets['ranap-lanjutan-rinci'] = ['Ranap lanjutan rinci', ['Rincian jurnal Khanza setelah cut off', $after, $stamp],
            [['Kelompok', X::TEXT, 15], ['Tanggal', X::TEXT, 11], ['No. jurnal', X::TEXT, 18], ['No. bukti', X::TEXT, 20], ['No. rawat', X::TEXT, 20], ['Keterangan', X::TEXT, 44],
                ['Kode Khanza', X::TEXT, 12], ['Rekening Khanza', X::TEXT, 30], ['Kode COA', X::TEXT, 10], ['Nama akun COA', X::TEXT, 32], $money('Debet'), $money('Kredit')],
            array_map(fn ($r) => [$r['kelompok'], $r['tgl_jurnal'], $r['no_jurnal'], $r['no_bukti'], $r['no_rawat'], $r['keterangan'], $r['kd_rek'], $r['nm_rek'], $r['coa'], $r['coa_name'], $r['debet'], $r['kredit']], $co['lines'])];
        $sheets['tidak-seimbang'] = ['Jurnal tidak seimbang', ['Jurnal Khanza dengan total debet ≠ kredit', $period, $stamp],
            [['No. jurnal', X::TEXT, 18], ['No. bukti', X::TEXT, 20], ['Tanggal', X::TEXT, 11], ['Keterangan', X::TEXT, 50], $money('Debet'), $money('Kredit'), $money('Selisih')],
            array_map(fn ($r) => [$r['no_jurnal'], $r['no_bukti'], $r['tgl_jurnal'], $r['keterangan'], (float) $r['debet'], (float) $r['kredit'], round((float) $r['debet'] - (float) $r['kredit'], 2)], $rows('tidak_seimbang'))];
        $sheets['coa'] = ['COA', ['COA baru yang dipakai closing'],
            [['Account Number', X::TEXT, 14], ['Account Name', X::TEXT, 42], ['Company', X::TEXT, 14], ['Parent Account', X::TEXT, 14], ['Is Group/Type Akun', X::TEXT, 22], ['Kategori Akun', X::TEXT, 13]],
            $book->coa->map(fn ($a) => [$a->code, $a->name, $a->company, $a->parent_code, ClosingAccount::TYPES[$a->type] ?? $a->type, $a->category])->values()->all()];

        return $sheets;
    }
}
