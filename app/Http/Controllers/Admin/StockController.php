<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Medicine;
use App\Models\StockEntry;
use App\Support\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Persediaan obat farmasi: lembar isian harian, rekap bulanan, dan kartu stok per obat. */
class StockController extends Controller
{
    /** Unit yang dipilih, diingat selama sesi supaya petugas tidak perlu memilih ulang. */
    protected function location(Request $request): string
    {
        $loc = $request->query('unit', session('stock.unit', 'gudang'));
        $loc = isset(StockEntry::LOCATIONS[$loc]) ? $loc : 'gudang';
        session(['stock.unit' => $loc]);

        return $loc;
    }

    protected function day(?string $value): Carbon
    {
        try {
            $d = $value ? Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : today();
        } catch (\Throwable) {
            $d = today();
        }

        return $d->gt(today()) ? today() : $d;
    }

    protected function month(?string $value): Carbon
    {
        try {
            $m = $value ? Carbon::createFromFormat('Y-m-d', $value.'-01')->startOfDay() : today()->startOfMonth();
        } catch (\Throwable) {
            $m = today()->startOfMonth();
        }

        return $m->gt(today()) ? today()->startOfMonth() : $m;
    }

    public function index(Request $request)
    {
        $loc = $this->location($request);
        $date = $this->day($request->query('date'));
        $opname = $request->boolean('opname');
        $usage = StockLedger::usage($loc, $date->copy()->subDays(29), $date);

        return view('admin.stock.index', [
            'loc' => $loc, 'date' => $date, 'opname' => $opname,
            'medicines' => Medicine::sorted(),
            'summary' => StockLedger::summary($loc, $date, $date),
            'entries' => StockEntry::where('location', $loc)->whereDate('date', $date)->with('user')->get()->keyBy('medicine_id'),
            'avg' => array_map(fn ($v) => $v / 30, $usage),
            // Barang keluar dari gudang hari itu = barang masuk ke farmasi sentral; ditampilkan sebagai bantuan isi.
            'fromGudang' => $loc === 'sentral'
                ? StockEntry::where('location', 'gudang')->whereDate('date', $date)->where('qty_out', '>', 0)->pluck('qty_out', 'medicine_id')->all()
                : [],
        ]);
    }

    public function save(Request $request)
    {
        $qty = 'nullable|integer|min:0|max:9999999';
        $data = $request->validate([
            'location' => 'required|in:'.implode(',', array_keys(StockEntry::LOCATIONS)),
            'date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'rows' => 'required|array|max:2000',
            'rows.*.in' => $qty, 'rows.*.out' => $qty, 'rows.*.counted' => $qty,
            'rows.*.note' => 'nullable|string|max:150',
            'rows.*.expired_at' => 'nullable|date',
        ], [
            'rows.required' => 'Belum ada angka yang diubah.',
            'date.before_or_equal' => 'Tanggal tidak boleh melewati hari ini.',
            'rows.*.*.integer' => 'Jumlah harus berupa angka bulat.',
            'rows.*.*.min' => 'Jumlah tidak boleh minus.',
        ]);
        $loc = $data['location'];
        $date = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();
        $medicines = Medicine::whereIn('id', array_keys($data['rows']))->get()->keyBy('id');
        $start = StockLedger::summary($loc, $date, $date);
        $minus = [];
        $saved = 0;

        DB::transaction(function () use ($data, $loc, $date, $medicines, $start, &$minus, &$saved) {
            foreach ($data['rows'] as $id => $row) {
                $med = $medicines[$id] ?? null;
                if (! $med) {
                    continue;
                }
                $in = (int) ($row['in'] ?? 0);
                $out = (int) ($row['out'] ?? 0);
                $counted = ($row['counted'] ?? null) === null ? null : (int) $row['counted'];
                $note = trim((string) ($row['note'] ?? '')) ?: null;
                $base = $counted ?? ($start[$id]['start'] ?? 0);
                if ($base + $in - $out < 0) {
                    $minus[] = $med->name.' (stok '.$base.')';

                    continue;
                }
                $entry = StockEntry::where(['medicine_id' => $id, 'location' => $loc])->whereDate('date', $date)->first();
                if (! $in && ! $out && $counted === null && ! $note) {
                    $entry?->delete();
                } else {
                    $values = ['qty_in' => $in, 'qty_out' => $out, 'counted' => $counted, 'note' => $note, 'user_id' => auth()->id()];
                    $entry ? $entry->update($values) : StockEntry::create($values + ['medicine_id' => $id, 'location' => $loc, 'date' => $date]);
                }
                if (! empty($row['expired_at'])) {
                    $med->update(['expired_at' => $row['expired_at']]);
                }
                $saved++;
            }
            if ($minus) {
                throw ValidationException::withMessages(['rows' => 'Jumlah keluar melebihi stok: '.implode(', ', array_slice($minus, 0, 3))
                    .(count($minus) > 3 ? ' dan '.(count($minus) - 3).' obat lain' : '')
                    .'. Periksa angkanya, atau isi Stock opname dulu bila stok fisik berbeda. Belum ada yang tersimpan.']);
            }
        });
        ActivityLog::record('Mengisi stok '.StockEntry::LOCATIONS[$loc].' tanggal '.$date->format('d/m/Y').' ('.$saved.' obat)');

        return redirect()->route('admin.stock', ['unit' => $loc, 'date' => $date->toDateString(), 'opname' => $request->boolean('opname') ?: null])
            ->with('ok', 'Tersimpan: '.$saved.' obat di '.StockEntry::LOCATIONS[$loc].', '.$date->translatedFormat('d F Y').'.');
    }

    /** @return array{0: Carbon, 1: Carbon, 2: Collection} */
    protected function reportRows(string $loc, Carbon $month): array
    {
        $from = $month->copy();
        $to = $month->copy()->endOfMonth()->startOfDay()->min(today());
        $days = (int) $from->diffInDays($to) + 1;
        $summary = StockLedger::summary($loc, $from, $to);
        $rows = Medicine::sorted()->map(function ($m) use ($summary, $days) {
            $s = $summary[$m->id] ?? ['start' => 0, 'in' => 0, 'out' => 0, 'adjust' => 0, 'end' => 0, 'counted_at' => null];
            $avg = $s['out'] / $days;

            return (object) ($s + [
                'm' => $m, 'avg' => $avg, 'safety' => StockLedger::safety($avg), 'rop' => StockLedger::rop($avg),
                'status' => StockLedger::status($s['end'], $avg), 'value' => $m->price ? $s['end'] * $m->price : null,
            ]);
        });

        return [$from, $to, $rows];
    }

    public function report(Request $request)
    {
        $loc = $this->location($request);
        [$from, $to, $rows] = $this->reportRows($loc, $this->month($request->query('month')));

        return view('admin.stock.report', ['loc' => $loc, 'from' => $from, 'to' => $to, 'rows' => $rows]);
    }

    public function export(Request $request)
    {
        $loc = $this->location($request);
        [$from, $to, $rows] = $this->reportRows($loc, $this->month($request->query('month')));
        $title = 'Persediaan obat '.StockEntry::LOCATIONS[$loc].' '.$from->translatedFormat('F Y');
        ActivityLog::record('Mengunduh rekap '.$title);

        return response()->streamDownload(function () use ($rows, $title, $from, $to) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [$title.' · '.$from->format('d/m/Y').' s/d '.$to->format('d/m/Y')], ';');
            fputcsv($out, ['Golongan', 'Nama obat', 'Satuan', 'Stok awal', 'Masuk', 'Keluar', 'Selisih SO', 'Stok akhir', 'Rata-rata pemakaian/hari', 'Safety stock', 'ROP', 'Status', 'Harga satuan', 'Nilai stok akhir', 'Tanggal ED', 'SO terakhir'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [$r->m->group, $r->m->name, $r->m->unit, $r->start, $r->in, $r->out, $r->adjust, $r->end,
                    number_format($r->avg, 2, ',', ''), $r->safety, $r->rop, $r->status[0], $r->m->price, $r->value,
                    $r->m->expired_at?->format('d/m/Y'), $r->counted_at?->format('d/m/Y')], ';');
            }
            fclose($out);
        }, 'persediaan-obat-'.$loc.'-'.$from->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Kartu stok: riwayat harian satu obat dalam satu bulan. */
    public function card(Request $request, Medicine $medicine)
    {
        $loc = $this->location($request);
        $from = $this->month($request->query('month'));
        $to = $from->copy()->endOfMonth()->startOfDay()->min(today());
        $balance = StockLedger::summary($loc, $from, $from)[$medicine->id]['start'] ?? 0;
        $start = $balance;
        $lines = StockEntry::where(['medicine_id' => $medicine->id, 'location' => $loc])
            ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->orderBy('date')->with('user')->get()
            ->map(function ($e) use (&$balance) {
                $expected = $balance;
                $balance = ($e->counted ?? $balance) + $e->qty_in - $e->qty_out;

                return (object) ['e' => $e, 'expected' => $expected, 'balance' => $balance];
            });
        $ends = collect(StockEntry::LOCATIONS)->map(fn ($n, $k) => StockLedger::summary($k, today(), today())[$medicine->id]['end'] ?? 0);

        return view('admin.stock.card', compact('medicine', 'loc', 'from', 'to', 'start', 'lines', 'ends'));
    }
}
