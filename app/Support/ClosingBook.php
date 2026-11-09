<?php

namespace App\Support;

use App\Models\ClosingAccount;
use App\Models\ClosingAdjustment;
use App\Models\ClosingMapping;
use App\Models\ClosingSnapshot;
use App\Models\Setting;
use App\Services\Khanza\KhanzaClosing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Perhitungan closing dari salinan data Khanza (snapshot) + pemetaan akun + jurnal penyesuaian.
 *
 * Semua saldo internal bertanda debet − kredit. Untuk tampilan, akun bersaldo normal kredit
 * (Kewajiban, Ekuitas, Pendapatan) dibalik tandanya lewat normal().
 */
class ClosingBook
{
    public array $settings;

    public ?ClosingSnapshot $snap;

    /** @var Collection<string, ClosingAccount> */
    public Collection $coa;

    /** @var array<string, string> kd_rek => kode COA */
    public array $map;

    protected ?array $khanzaCache = null;

    protected ?array $tbCache = null;

    public function __construct()
    {
        $this->settings = self::settings();
        $this->snap = ClosingSnapshot::latest();
        $this->coa = ClosingAccount::query()->orderBy('sort')->orderBy('code')->get()->keyBy('code');
        $this->map = ClosingMapping::query()->whereNotNull('coa_code')->pluck('coa_code', 'kd_rek')->all();
    }

    /* ---------------- Pengaturan ---------------- */

    public static function settings(): array
    {
        return Setting::get('closing', [
            'cutoff' => config('closing.cutoff'), 'year_start' => config('closing.year_start'), 'opening' => 'rekeningtahun',
            'locked' => false, 'locked_at' => null, 'locked_by' => null,
        ]);
    }

    public static function saveSettings(array $values): void
    {
        Setting::put('closing', array_replace(self::settings(), $values));
    }

    public function locked(): bool
    {
        return (bool) ($this->settings['locked'] ?? false);
    }

    /** Snapshot masih sesuai pengaturan (cut off, awal tahun, sumber saldo awal)? */
    public function snapshotCurrent(): bool
    {
        return $this->snap
            && $this->snap->cutoff->toDateString() === $this->settings['cutoff']
            && $this->snap->year_start->toDateString() === $this->settings['year_start']
            && $this->snap->opening === $this->settings['opening'];
    }

    /** @return string[] bulan YYYY-MM dari awal tahun sampai cut off */
    public function months(): array
    {
        $out = [];
        $m = Carbon::parse($this->settings['year_start'])->startOfMonth();
        $end = Carbon::parse($this->settings['cutoff'])->startOfMonth();
        while ($m->lte($end) && count($out) < 24) {
            $out[] = $m->format('Y-m');
            $m->addMonth();
        }

        return $out;
    }

    /* ---------------- COA ---------------- */

    /** @return string[] kode akun detail (boleh dipakai transaksi) */
    public function detailCodes(): array
    {
        return $this->coa->filter->isDetail()->keys()->map(fn ($k) => (string) $k)->values()->all();
    }

    public function account(?string $code): ?ClosingAccount
    {
        return $code === null ? null : $this->coa->get($code);
    }

    public static function normal(float $signed, ?string $category): float
    {
        return ClosingAccount::isCreditNormal($category) ? -$signed : $signed;
    }

    /* ---------------- Rekening Khanza ---------------- */

    /**
     * Rekening Khanza dengan saldo per cut off dan pemetaannya.
     *
     * @return array<string, array<string, mixed>>
     */
    public function khanzaAccounts(): array
    {
        if ($this->khanzaCache !== null) {
            return $this->khanzaCache;
        }
        if (! $this->snap) {
            return $this->khanzaCache = [];
        }
        $mov = [];
        foreach ($this->snap->rows('mutasi') as $m) {
            $mov[$m['kd_rek']]['debet'] = ($mov[$m['kd_rek']]['debet'] ?? 0) + $m['debet'];
            $mov[$m['kd_rek']]['kredit'] = ($mov[$m['kd_rek']]['kredit'] ?? 0) + $m['kredit'];
        }
        $carry = [];
        foreach ($this->snap->rows('lanjutan') as $l) {
            $carry[$l['kd_rek']] = true;
        }
        $valid = $this->detailCodes();
        $out = [];
        foreach ($this->snap->rows('akun') as $a) {
            $kd = $a['kd_rek'];
            $d = round($mov[$kd]['debet'] ?? 0, 2);
            $k = round($mov[$kd]['kredit'] ?? 0, 2);
            $coa = $this->map[$kd] ?? null;
            $acc = $coa ? $this->coa->get($coa) : null;
            $plKhanza = $a['tipe'] === 'R';
            $out[$kd] = $a + [
                'debet' => $d, 'kredit' => $k, 'akhir' => round($a['awal'] + $d - $k, 2),
                // Rekening yang hanya dipakai jurnal setelah cut off (ranap lanjutan) juga perlu dipetakan
                'aktif' => abs($a['awal']) >= 0.01 || $d >= 0.01 || $k >= 0.01 || isset($carry[$kd]),
                'lanjutan' => isset($carry[$kd]),
                'coa' => $acc && $acc->isDetail() ? $coa : null, 'coa_raw' => $coa, 'coa_invalid' => $coa && (! $acc || ! $acc->isDetail()),
                'saran' => $kd === KhanzaClosing::PRIOR_PL ? (in_array(config('closing.retained_earnings'), $valid, true) ? config('closing.retained_earnings') : null) : CoaSuggest::guess($a['nm_rek'], $valid),
                // Rekening laba rugi Khanza dipetakan ke akun neraca, atau sebaliknya
                'beda_tipe' => $acc && in_array($a['tipe'], ['N', 'M', 'R'], true) && $plKhanza === ClosingAccount::isBalanceSheet($acc->category),
            ];
        }

        return $this->khanzaCache = $out;
    }

    /* ---------------- Penyesuaian ---------------- */

    /** @return array<string, array{0: float, 1: float}> kode COA => [debet, kredit] */
    public function adjustmentTotals(): array
    {
        $out = [];
        foreach (ClosingAdjustment::query()->with('lines')->get() as $adj) {
            foreach ($adj->lines as $l) {
                $out[$l->coa_code][0] = ($out[$l->coa_code][0] ?? 0) + $l->debit;
                $out[$l->coa_code][1] = ($out[$l->coa_code][1] ?? 0) + $l->credit;
            }
        }

        return $out;
    }

    /* ---------------- Neraca saldo ---------------- */

    /**
     * Neraca saldo per akun detail COA baru. Baris dengan code '' = rekening Khanza yang belum dipetakan.
     *
     * @return array{rows: array<string, array<string, mixed>>, total: array<string, float>}
     */
    public function trialBalance(): array
    {
        if ($this->tbCache !== null) {
            return $this->tbCache;
        }
        $rows = [];
        $blank = fn ($code, $name, $cat) => ['code' => $code, 'name' => $name, 'category' => $cat, 'awal' => 0.0, 'debet' => 0.0, 'kredit' => 0.0, 'khanza' => 0.0,
            'adj_d' => 0.0, 'adj_k' => 0.0, 'akhir' => 0.0, 'sumber' => []];
        foreach ($this->coa->filter->isDetail() as $code => $a) {
            $rows[(string) $code] = $blank((string) $code, $a->name, $a->category);
        }
        foreach ($this->khanzaAccounts() as $kd => $k) {
            if (abs($k['awal']) < 0.01 && $k['debet'] < 0.01 && $k['kredit'] < 0.01) {
                continue;
            }
            $code = $k['coa'] ?? '';
            if (! isset($rows[$code])) {
                $rows[$code] = $blank('', 'Belum dipetakan', null);
            }
            foreach (['awal', 'debet', 'kredit'] as $f) {
                $rows[$code][$f] += $k[$f];
            }
            $rows[$code]['khanza'] += $k['akhir'];
            $rows[$code]['sumber'][] = $kd;
        }
        foreach ($this->adjustmentTotals() as $code => [$d, $c]) {
            $code = (string) $code;
            if (! isset($rows[$code])) {
                $rows[$code] = $blank($code, 'Akun tidak ada di COA', null);
            }
            $rows[$code]['adj_d'] += $d;
            $rows[$code]['adj_k'] += $c;
        }
        $total = ['awal' => 0.0, 'debet' => 0.0, 'kredit' => 0.0, 'khanza_d' => 0.0, 'khanza_k' => 0.0, 'adj_d' => 0.0, 'adj_k' => 0.0, 'akhir_d' => 0.0, 'akhir_k' => 0.0];
        foreach ($rows as &$r) {
            foreach (['awal', 'debet', 'kredit', 'khanza', 'adj_d', 'adj_k'] as $f) {
                $r[$f] = round($r[$f], 2);
            }
            $r['akhir'] = round($r['khanza'] + $r['adj_d'] - $r['adj_k'], 2);
            $total['awal'] += $r['awal'];
            $total['debet'] += $r['debet'];
            $total['kredit'] += $r['kredit'];
            $total['khanza_d'] += max(0, $r['khanza']);
            $total['khanza_k'] += max(0, -$r['khanza']);
            $total['adj_d'] += $r['adj_d'];
            $total['adj_k'] += $r['adj_k'];
            $total['akhir_d'] += max(0, $r['akhir']);
            $total['akhir_k'] += max(0, -$r['akhir']);
        }
        unset($r);
        $total = array_map(fn ($v) => round($v, 2), $total);
        $total['selisih'] = round($total['akhir_d'] - $total['akhir_k'], 2);
        $total['selisih_khanza'] = round($total['khanza_d'] - $total['khanza_k'], 2);
        // Urutkan sesuai COA; baris tanpa kode di akhir
        $order = array_flip(array_map('strval', $this->coa->keys()->all()));
        uksort($rows, fn ($a, $b) => ($order[$a] ?? PHP_INT_MAX) <=> ($order[$b] ?? PHP_INT_MAX) ?: strcmp($a, $b));

        return $this->tbCache = ['rows' => $rows, 'total' => $total];
    }

    /** Saldo akhir (setelah penyesuaian) per kode COA detail. */
    public function finals(): array
    {
        return array_map(fn ($r) => $r['akhir'], $this->trialBalance()['rows']);
    }

    /* ---------------- Laporan ---------------- */

    /** Laba (rugi) bersih dari saldo akun laba rugi (positif = laba). */
    public function netIncome(?array $finals = null): float
    {
        $finals ??= $this->finals();
        $sum = 0.0;
        foreach ($finals as $code => $v) {
            $acc = $this->coa->get($code);
            if ($acc && ! ClosingAccount::isBalanceSheet($acc->category)) {
                $sum += $v;
            }
        }

        return round(-$sum, 2);
    }

    /**
     * Susunan laporan berjenjang (header → sub → detail) untuk kategori tertentu.
     *
     * @param  string[]  $categories
     * @return array<int, array{code: string, name: string, type: string, depth: int, amount: float, category: string}>
     */
    public function statement(array $categories, ?array $finals = null): array
    {
        $finals ??= $this->finals();
        $children = [];
        foreach ($this->coa as $code => $a) {
            $children[(string) $a->parent_code][] = (string) $code;
        }
        $lines = [];
        $walk = function (string $code, int $depth) use (&$walk, &$lines, $children, $finals): float {
            $a = $this->coa->get($code);
            $idx = count($lines);
            $lines[] = ['code' => $code, 'name' => $a->name, 'type' => $a->type, 'depth' => $depth, 'amount' => 0.0, 'category' => $a->category];
            $sum = $a->isDetail() ? self::normal($finals[$code] ?? 0.0, $a->category) : 0.0;
            foreach ($children[$code] ?? [] as $child) {
                $sum += $walk($child, $depth + 1);
            }
            $lines[$idx]['amount'] = round($sum, 2);

            return $sum;
        };
        foreach ($children[''] ?? [] as $root) {
            if (in_array($this->coa->get($root)->category, $categories, true)) {
                $walk($root, 0);
            }
        }

        return $lines;
    }

    /** @return array{aset: float, kewajiban: float, ekuitas: float, laba: float, belum: float, selisih: float} */
    public function balanceSummary(): array
    {
        $f = $this->finals();
        $sum = fn (string $cat) => round(array_sum(array_map(fn ($code) => self::normal($f[$code] ?? 0, $cat),
            $this->coa->filter(fn ($a) => $a->isDetail() && $a->category === $cat)->keys()->map(fn ($k) => (string) $k)->all())), 2);
        $aset = $sum('Aset');
        $kew = $sum('Kewajiban');
        $eku = $sum('Ekuitas');
        $laba = $this->netIncome($f);
        $belum = round($f[''] ?? 0, 2);

        return ['aset' => $aset, 'kewajiban' => $kew, 'ekuitas' => $eku, 'laba' => $laba, 'belum' => $belum,
            'selisih' => round($aset - $kew - $eku - $laba, 2)];
    }

    /**
     * Laba rugi per bulan per akun COA (dari mutasi Khanza; penyesuaian dicatat pada bulan tanggal jurnalnya).
     *
     * @return array<string, array<string, float>> kode COA => bulan => nilai normal
     */
    public function monthlyPL(): array
    {
        $out = [];
        if (! $this->snap) {
            return $out;
        }
        $add = function (string $code, string $month, float $signed) use (&$out) {
            $acc = $this->coa->get($code);
            if ($acc && ! ClosingAccount::isBalanceSheet($acc->category)) {
                $out[$code][$month] = round(($out[$code][$month] ?? 0) + self::normal($signed, $acc->category), 2);
            }
        };
        foreach ($this->snap->rows('mutasi') as $m) {
            if ($code = $this->map[$m['kd_rek']] ?? null) {
                $add($code, $m['bulan'], $m['debet'] - $m['kredit']);
            }
        }
        foreach (ClosingAdjustment::query()->with('lines')->get() as $adj) {
            foreach ($adj->lines as $l) {
                $add($l->coa_code, $adj->date->format('Y-m'), $l->debit - $l->credit);
            }
        }

        return $out;
    }

    /* ---------------- Rekonsiliasi ---------------- */

    public static function ruleAccount(string $text, array $rules): string
    {
        $t = strtoupper($text);
        foreach ($rules as [$needle, $code]) {
            if ($needle === '' || str_contains($t, $needle)) {
                return $code;
            }
        }

        return (string) end($rules)[1];
    }

    /**
     * Cocokkan saldo buku besar (setelah penyesuaian) dengan rincian Khanza per cut off.
     *
     * @return array<string, array{title: string, lines: array, gl: float, sub: float, diff: float, available: bool}>
     */
    public function reconciliation(): array
    {
        $f = $this->finals();
        $out = [];
        $groups = [
            'piutang' => ['Piutang pasien', fn ($r) => self::ruleAccount($r['penjamin'], config('closing.piutang_accounts')), 'sisa'],
            'hutang' => ['Hutang supplier', fn ($r) => config('closing.payable_accounts')[$r['jenis']] ?? '2103', 'sisa'],
            'persediaan' => ['Persediaan farmasi (perkiraan harga beli)', fn ($r) => self::ruleAccount($r['jenis'].' '.$r['nama'], config('closing.stock_accounts')), 'nilai'],
        ];
        foreach ($groups as $key => [$title, $accountOf, $field]) {
            $rows = $this->snap?->rows($key) ?? [];
            $sub = [];
            foreach ($rows as $r) {
                $code = $accountOf($r);
                $sub[$code] = ($sub[$code] ?? 0) + (float) $r[$field];
            }
            $lines = [];
            foreach (array_unique(array_merge(config('closing.recon.'.$key), array_keys($sub))) as $code) {
                $acc = $this->coa->get($code);
                $gl = round(self::normal($f[$code] ?? 0, $acc?->category ?? ($key === 'hutang' ? 'Kewajiban' : 'Aset')), 2);
                $s = round($sub[$code] ?? 0, 2);
                $lines[] = ['code' => (string) $code, 'name' => $acc?->name ?? '(tidak ada di COA)', 'category' => $acc?->category, 'gl' => $gl, 'sub' => $s, 'diff' => round($gl - $s, 2)];
            }
            $gl = round(array_sum(array_column($lines, 'gl')), 2);
            $s = round(array_sum(array_column($lines, 'sub')), 2);
            $out[$key] = ['title' => $title, 'lines' => $lines, 'gl' => $gl, 'sub' => $s, 'diff' => round($gl - $s, 2),
                'available' => (bool) $this->snap?->has($key) && ! isset($this->snap->errors[$key === 'hutang' ? 'hutang_medis' : $key]), 'count' => count($rows)];
        }

        return $out;
    }

    /* ---------------- Saldo awal SIMRS baru ---------------- */

    /**
     * Saldo awal per akun detail untuk SIMRS baru (per hari setelah cut off).
     * mode "tutup": saldo laba rugi digabung ke akun Laba Tahun Berjalan; mode "rinci": tiap akun laba rugi ikut dibawa.
     *
     * @return array<int, array{code: string, name: string, parent: ?string, category: ?string, debit: float, credit: float}>
     */
    public function opening(string $mode = 'tutup'): array
    {
        $f = $this->finals();
        $amounts = [];
        $earn = (string) config('closing.current_earnings');
        foreach ($f as $code => $v) {
            $acc = $this->coa->get($code);
            if ($mode === 'tutup' && $acc && ! ClosingAccount::isBalanceSheet($acc->category)) {
                $amounts[$earn] = ($amounts[$earn] ?? 0) + $v;
            } else {
                $amounts[$code] = ($amounts[$code] ?? 0) + $v;
            }
        }
        $order = array_flip(array_map('strval', $this->coa->keys()->all()));
        uksort($amounts, fn ($a, $b) => ($order[$a] ?? PHP_INT_MAX) <=> ($order[$b] ?? PHP_INT_MAX));
        $out = [];
        foreach ($amounts as $code => $v) {
            $v = round($v, 2);
            if (abs($v) < 0.01) {
                continue;
            }
            $acc = $this->coa->get($code);
            $out[] = ['code' => (string) $code, 'name' => $acc?->name ?? 'BELUM DIPETAKAN', 'parent' => $acc?->parent_code, 'category' => $acc?->category,
                'debit' => $v > 0 ? $v : 0.0, 'credit' => $v < 0 ? -$v : 0.0];
        }

        return $out;
    }

    /* ---------------- Ranap lanjutan (setelah go-live) ---------------- */

    /**
     * Transaksi Khanza setelah cut off. Tidak masuk neraca saldo / saldo awal; diunduh terpisah untuk dicatat
     * sebagai transaksi bulan berjalan di SIMRS baru.
     *
     * @return array{patients: array, lines: array, byCoa: array, total: array, other: array, unmapped: int, open: int}
     */
    public function carryOver(): array
    {
        $rows = $this->snap?->rows('lanjutan') ?? [];
        $kh = $this->khanzaAccounts();
        $lines = [];
        $byPatient = [];
        $byCoa = [];
        $total = ['Ranap lanjutan' => [0.0, 0.0], 'Lainnya' => [0.0, 0.0]];
        $unmapped = 0;
        foreach ($rows as $r) {
            $coa = $this->map[$r['kd_rek']] ?? null;
            $acc = $coa ? $this->coa->get($coa) : null;
            $coa = $acc && $acc->isDetail() ? (string) $coa : null;
            $unmapped += $coa ? 0 : 1;
            $lines[] = $r + ['nm_rek' => $kh[$r['kd_rek']]['nm_rek'] ?? '', 'coa' => $coa, 'coa_name' => $acc?->name, 'category' => $acc?->category];
            $total[$r['kelompok']][0] += $r['debet'];
            $total[$r['kelompok']][1] += $r['kredit'];
            if ($r['no_rawat']) {
                $byPatient[$r['no_rawat']]['jurnal'][$r['no_jurnal']] = true;
                // Nilai pendapatan yang diakui setelah cut off untuk pasien ini
                if ($acc && $acc->category === 'Pendapatan') {
                    $byPatient[$r['no_rawat']]['pendapatan'] = ($byPatient[$r['no_rawat']]['pendapatan'] ?? 0) + $r['kredit'] - $r['debet'];
                }
            }
            $key = $r['kelompok'].'|'.substr($r['tgl_jurnal'], 0, 7).'|'.($coa ?? '');
            $byCoa[$key] ??= ['kelompok' => $r['kelompok'], 'bulan' => substr($r['tgl_jurnal'], 0, 7), 'code' => $coa ?? '', 'name' => $acc?->name ?? 'BELUM DIPETAKAN', 'debit' => 0.0, 'credit' => 0.0];
            $byCoa[$key]['debit'] += $r['debet'];
            $byCoa[$key]['credit'] += $r['kredit'];
        }
        // Ringkasan per bulan per akun ditulis bersih (net) per sisi, seperti satu jurnal memorial per bulan
        $byCoa = array_values(array_map(function ($x) {
            $net = round($x['debit'] - $x['credit'], 2);

            return ['debit' => max(0, $net), 'credit' => max(0, -$net)] + $x;
        }, $byCoa));
        $order = array_flip(array_map('strval', $this->coa->keys()->all()));
        usort($byCoa, fn ($a, $b) => [$a['kelompok'] !== 'Ranap lanjutan', $a['bulan'], $order[$a['code']] ?? PHP_INT_MAX] <=> [$b['kelompok'] !== 'Ranap lanjutan', $b['bulan'], $order[$b['code']] ?? PHP_INT_MAX]);
        $patients = array_map(fn ($p) => $p + ['jurnal' => count($byPatient[$p['no_rawat']]['jurnal'] ?? []), 'pendapatan' => round($byPatient[$p['no_rawat']]['pendapatan'] ?? 0, 2)],
            $this->snap?->rows('dirawat') ?? []);

        return ['patients' => $patients, 'lines' => $lines, 'byCoa' => $byCoa, 'unmapped' => $unmapped,
            'total' => array_map(fn ($t) => [round($t[0], 2), round($t[1], 2)], $total),
            'other' => array_values(array_unique(array_column(array_filter($rows, fn ($r) => $r['kelompok'] === 'Lainnya'), 'no_jurnal'))),
            'open' => count(array_filter($patients, fn ($p) => $p['masih_dirawat'] ?? false))];
    }

    /* ---------------- Daftar periksa ---------------- */

    /** @return array<int, array{label: string, status: string, detail: string, link: ?string}> status: ok | warn | bad | info */
    public function checklist(): array
    {
        $items = [];
        $add = function (string $label, string $status, string $detail, ?string $link = null) use (&$items) {
            $items[] = compact('label', 'status', 'detail', 'link');
        };
        $details = count($this->detailCodes());
        $add('COA baru dimuat', $details ? 'ok' : 'bad', $details ? "{$details} akun detail siap dipakai." : 'Belum ada akun. Unggah template COA.', 'coa');

        if (! $this->snap) {
            $add('Data Khanza diambil', 'bad', 'Belum ada data. Tekan "Ambil data dari Khanza".', null);

            return $items;
        }
        $err = count($this->snap->errors ?? []);
        $add('Data Khanza diambil', $this->snapshotCurrent() ? ($err ? 'warn' : 'ok') : 'bad',
            ($this->snapshotCurrent() ? 'Diambil '.$this->snap->created_at->translatedFormat('d M Y H:i').' oleh '.$this->snap->user_name.'.' : 'Pengaturan berubah sejak data diambil. Ambil ulang data.')
            .($err ? " {$err} bagian gagal dibaca (lihat detail di bawah)." : ''));

        $after = $this->snap->rows('setelah_cutoff')[0] ?? null;
        if ($after && $after['kunjungan'] > 0) {
            $add('Tidak ada pendaftaran baru di Khanza setelah cut off', 'warn', "Masih ada {$after['kunjungan']} kunjungan baru terdaftar di Khanza setelah cut off (terakhir {$after['kunjungan_terakhir']}). Pasien baru seharusnya didaftarkan di SIMRS baru.");
        }
        $co = $this->carryOver();
        if ($co['patients'] || $co['lines']) {
            $add('Ranap lanjutan di Khanza', $co['open'] ? 'info' : 'ok', count($co['patients']).' pasien masih dirawat saat cut off, '
                .($co['open'] ? "{$co['open']} di antaranya belum pulang. Ambil ulang data setelah semuanya pulang." : 'semuanya sudah pulang.')
                .' Transaksinya setelah cut off ('.Fmt::rp($co['total']['Ranap lanjutan'][0]).') tidak masuk saldo awal; diunduh terpisah untuk dicatat di SIMRS baru.', 'carryover');
            if ($co['other']) {
                $add('Jurnal Khanza lain setelah cut off', 'warn', count($co['other']).' jurnal setelah cut off tidak terkait pasien ranap lanjutan (misalnya pelunasan piutang atau pembayaran supplier lewat Khanza). Periksa agar tidak dicatat dua kali di SIMRS baru.', 'carryover#lainnya');
            }
            if ($co['unmapped']) {
                $add('Rekening ranap lanjutan dipetakan', 'bad', "{$co['unmapped']} baris jurnal setelah cut off memakai rekening yang belum dipetakan.", 'mapping?f=belum');
            }
        }
        $unb = count($this->snap->rows('tidak_seimbang'));
        $add('Jurnal Khanza seimbang', $unb ? 'bad' : 'ok', $unb ? "{$unb} jurnal Khanza total debet ≠ kredit. Lengkapi lewat jurnal koreksi (menu Rekonsiliasi)." : 'Semua jurnal seimbang.', 'reconcile#jurnal');

        $kh = collect($this->khanzaAccounts())->where('aktif', true);
        $unmapped = $kh->whereNull('coa')->count();
        $add('Semua rekening Khanza dipetakan', $unmapped ? 'bad' : 'ok', $unmapped ? "{$unmapped} rekening bersaldo/bermutasi belum dipetakan ke COA baru." : 'Semua rekening aktif sudah dipetakan ('.$kh->count().' rekening).', 'mapping');
        $mismatch = $kh->where('beda_tipe', true)->count();
        $add('Jenis rekening sesuai', $mismatch ? 'warn' : 'ok', $mismatch ? "{$mismatch} rekening laba rugi Khanza dipetakan ke akun neraca (atau sebaliknya). Periksa kembali." : 'Rekening neraca → akun neraca, rekening laba rugi → akun laba rugi.', 'mapping?f=beda');

        $tb = $this->trialBalance()['total'];
        $add('Neraca saldo seimbang', abs($tb['selisih']) < 1 ? 'ok' : 'bad', abs($tb['selisih']) < 1
            ? 'Total debet = total kredit '.Fmt::rp($tb['akhir_d']).'.'
            : 'Selisih '.Fmt::rp($tb['selisih']).' (data Khanza '.Fmt::rp($tb['selisih_khanza']).'). Biasanya karena saldo awal tahun di Khanza tidak seimbang atau jurnal tidak seimbang.', 'trial');

        foreach ($this->reconciliation() as $key => $r) {
            if (! $r['available']) {
                $add('Rekonsiliasi '.strtolower($r['title']), 'warn', 'Rincian tidak bisa dibaca dari Khanza.', 'reconcile#'.$key);

                continue;
            }
            $ok = abs($r['diff']) < 1;
            $add('Rekonsiliasi '.strtolower($r['title']), $ok ? 'ok' : ($key === 'persediaan' ? 'info' : 'warn'),
                'Buku besar '.Fmt::rp($r['gl']).' · rincian '.Fmt::rp($r['sub']).($ok ? ' · cocok.' : ' · selisih '.Fmt::rp($r['diff']).'.'), 'reconcile#'.$key);
        }
        $unbilled = count($this->snap->rows('belum_ditagih'));
        $inpatient = count($this->snap->rows('dirawat'));
        $add('Pasien belum ditagih per cut off', $unbilled + $inpatient ? 'info' : 'ok', $unbilled + $inpatient
            ? "{$unbilled} kunjungan belum ditagih dan {$inpatient} pasien masih dirawat saat cut off. Tentukan apakah pendapatannya diakui di Khanza (penyesuaian) atau di SIMRS baru."
            : 'Tidak ada.', 'reconcile#belum');
        $add('Kunci closing', $this->locked() ? 'ok' : 'info', $this->locked()
            ? 'Dikunci '.Carbon::parse($this->settings['locked_at'])->translatedFormat('d M Y H:i').' oleh '.$this->settings['locked_by'].'.'
            : 'Setelah semua beres dan saldo awal diunggah ke SIMRS baru, kunci closing agar angka tidak berubah lagi.');

        return $items;
    }
}
