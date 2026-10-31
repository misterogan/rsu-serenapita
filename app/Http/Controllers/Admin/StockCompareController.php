<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\FarmasiImportSoCommand;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Medicine;
use App\Models\StockEntry;
use App\Support\SimrsStockFile;
use App\Support\StockCompare;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Cocokkan SIMRS: unggah ekspor stok dari SIMRS, lalu bandingkan per kode dengan stok di sistem ini.
 * Isi file disimpan sementara per petugas, sehingga hasil bisa diunduh atau dipakai memperbarui nama tanpa unggah ulang.
 */
class StockCompareController extends Controller
{
    protected function key(): string
    {
        return 'stock-compare.'.auth()->id();
    }

    /** @return array{0: ?array, 1: array} data unggahan dan hasil perbandingan */
    protected function result(): array
    {
        $data = Cache::get($this->key());
        if (! $data) {
            return [null, []];
        }

        return [$data, StockCompare::run($data['rows'], $data['unit'], Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay())];
    }

    public function index()
    {
        [$data, $rows] = $this->result();

        return view('admin.stock.compare', compact('data', 'rows'));
    }

    public function upload(Request $request)
    {
        $v = $request->validate([
            'file' => 'required|file|max:10240|extensions:xlsx,csv',
            'unit' => 'required|in:'.implode(',', array_keys(StockEntry::LOCATIONS)),
            'date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'sheet' => 'nullable|string|max:60',
        ], ['file.extensions' => 'File harus berformat .xlsx atau .csv hasil ekspor SIMRS.']);
        try {
            $read = SimrsStockFile::read($request->file('file')->getRealPath(), $request->file('file')->getClientOriginalName(), $v['sheet'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }
        Cache::put($this->key(), [
            'unit' => $v['unit'], 'date' => $v['date'], 'file' => $request->file('file')->getClientOriginalName(),
            'sheet' => $read['sheet'], 'sheets' => $read['sheets'], 'rows' => $read['rows'], 'at' => now()->format('d/m/Y H.i'),
        ], now()->addHours(4));
        ActivityLog::record('Mencocokkan stok '.StockEntry::LOCATIONS[$v['unit']].' dengan SIMRS ('.$request->file('file')->getClientOriginalName().')');

        return redirect()->route('admin.stock.compare');
    }

    public function export()
    {
        [$data, $rows] = $this->result();
        abort_unless($data, 404);
        $name = 'cocok-simrs-'.$data['unit'].'-'.$data['date'].'.csv';

        return response()->streamDownload(function () use ($data, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Cocokkan SIMRS · '.StockEntry::LOCATIONS[$data['unit']].' · stok sistem per '.$data['date'].' · '.$data['file'].' ('.$data['sheet'].')'], ';');
            fputcsv($out, ['Kode SIMRS', 'Nama di SIMRS', 'Nama di sistem', 'Jenis', 'Stok SIMRS', 'Stok sistem', 'Selisih (sistem - SIMRS)', 'Status'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [$r->code, $r->simrsName, $r->med?->name, $r->med ? Medicine::KINDS[$r->med->kind] : '', $r->simrs, $r->system, $r->diff, StockCompare::STATUS[$r->status][0]], ';');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Samakan nama barang di sistem dengan nama di SIMRS (dicocokkan lewat kode). */
    public function rename()
    {
        [$data, $rows] = $this->result();
        abort_unless($data, 404);
        $n = 0;
        foreach ($rows as $r) {
            if ($r->renamed && $r->simrsName !== '') {
                $r->med->update(['name' => $r->simrsName]);
                $n++;
            }
        }
        ActivityLog::record('Menyamakan '.$n.' nama barang farmasi dengan SIMRS');

        return back()->with('ok', $n.' nama barang disamakan dengan SIMRS. Stok dan riwayatnya tidak berubah.');
    }

    /** Tambahkan kode SIMRS yang belum ada sebagai barang baru (nonaktif, stok 0). */
    public function add()
    {
        [$data, $rows] = $this->result();
        abort_unless($data, 404);
        $n = 0;
        foreach ($rows as $r) {
            if (StockCompare::addable($r)) {
                $unit = Medicine::normalizeUnit(collect($data['rows'])->firstWhere('code', $r->code)['unit'] ?? '');
                Medicine::create(['code' => $r->code, 'kind' => 'obat', 'name' => $r->simrsName, 'unit' => $unit,
                    'group' => FarmasiImportSoCommand::guessGroup($r->simrsName, $unit), 'is_active' => false]);
                $n++;
            }
        }
        ActivityLog::record('Menambahkan '.$n.' barang farmasi baru dari SIMRS');

        return back()->with('ok', $n.' barang baru ditambahkan dengan stok 0. Isi stoknya lewat Stock opname bila memang ada fisiknya.');
    }

    public function clear()
    {
        Cache::forget($this->key());

        return redirect()->route('admin.stock.compare');
    }
}
