<?php

namespace Tests\Feature;

use App\Models\ClosingAdjustment;
use App\Models\ClosingMapping;
use App\Models\ClosingSnapshot;
use App\Models\User;
use App\Support\ClosingBook;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

/** Closing akuntansi dari database Khanza tiruan (SQLite) dengan struktur tabel bawaan Khanza. */
class ClosingTest extends TestCase
{
    use RefreshDatabase;

    protected User $akunting;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.khanza' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'host' => 'test']]);
        DB::purge('khanza');
        $this->khanza();
        User::query()->update(['must_change_password' => false]);
        $this->akunting = User::where('email', 'akunting@rsuserenapita.id')->first();
    }

    protected function khanza(): void
    {
        $s = Schema::connection('khanza');
        $num = ['saldo_awal', 'debet', 'kredit', 'totalpiutang', 'uangmuka', 'sisapiutang', 'besar_cicilan', 'diskon_piutang', 'tidak_terbayar', 'tagihan', 'besar_bayar', 'stok_akhir', 'h_beli'];
        $tables = [
            'rekening' => ['kd_rek', 'nm_rek', 'tipe', 'balance', 'level'], 'rekeningtahun' => ['thn', 'kd_rek', 'saldo_awal'],
            'jurnal' => ['no_jurnal', 'no_bukti', 'tgl_jurnal', 'jam_jurnal', 'jenis', 'keterangan'], 'detailjurnal' => ['no_jurnal', 'kd_rek', 'debet', 'kredit'],
            'pasien' => ['no_rkm_medis', 'nm_pasien'], 'penjab' => ['kd_pj', 'png_jawab'], 'poliklinik' => ['kd_poli', 'nm_poli'],
            'reg_periksa' => ['no_rawat', 'tgl_registrasi', 'no_rkm_medis', 'kd_poli', 'kd_pj', 'stts', 'status_lanjut', 'status_bayar'],
            'piutang_pasien' => ['no_rawat', 'tgl_piutang', 'no_rkm_medis', 'status', 'totalpiutang', 'uangmuka', 'sisapiutang', 'tgltempo'],
            'bayar_piutang' => ['tgl_bayar', 'no_rkm_medis', 'besar_cicilan', 'no_rawat', 'diskon_piutang', 'tidak_terbayar'],
            'nota_jalan' => ['no_rawat', 'tanggal'], 'nota_inap' => ['no_rawat', 'tanggal'],
            'pemesanan' => ['no_faktur', 'kode_suplier', 'tgl_pesan', 'tgl_faktur', 'tgl_tempo', 'tagihan', 'status'], 'datasuplier' => ['kode_suplier', 'nama_suplier'],
            'bayar_pemesanan' => ['tgl_bayar', 'no_faktur', 'besar_bayar'],
            'riwayat_barang_medis' => ['kode_brng', 'kd_bangsal', 'tanggal', 'jam', 'stok_akhir', 'no_batch', 'no_faktur'],
            'databarang' => ['kode_brng', 'nama_brng', 'kode_sat', 'h_beli', 'kdjns'], 'jenis' => ['kdjns', 'nama'], 'bangsal' => ['kd_bangsal', 'nm_bangsal'],
            'kamar_inap' => ['no_rawat', 'tgl_masuk', 'tgl_keluar', 'stts_pulang'],
        ];
        foreach ($tables as $name => $cols) {
            $s->create($name, function (Blueprint $t) use ($cols, $num) {
                foreach ($cols as $c) {
                    in_array($c, $num, true) ? $t->decimal($c, 18, 2)->nullable() : $t->string($c)->nullable();
                }
            });
        }
        $db = DB::connection('khanza');
        $db->table('rekening')->insert(array_map(fn ($r) => array_combine(['kd_rek', 'nm_rek', 'tipe', 'balance', 'level'], $r + [4 => '1']), [
            ['111010', 'Kas Kecil', 'N', 'D'], ['112010', 'Bank Mandiri', 'N', 'D'], ['113010', 'Piutang BPJS', 'N', 'D'], ['113020', 'Piutang Pasien Umum', 'N', 'D'],
            ['115010', 'Persediaan Obat', 'N', 'D'], ['211010', 'Hutang Obat', 'N', 'K'], ['311010', 'Modal', 'M', 'K'],
            ['411010', 'Pendapatan Obat Ralan', 'R', 'K'], ['412010', 'Pendapatan Tindakan Ranap', 'R', 'K'], ['511010', 'Beban Gaji', 'R', 'D'],
        ]));
        $db->table('rekeningtahun')->insert([['thn' => '2026', 'kd_rek' => '111010', 'saldo_awal' => 10000000], ['thn' => '2026', 'kd_rek' => '311010', 'saldo_awal' => 10000000],
            ['thn' => '2025', 'kd_rek' => '111010', 'saldo_awal' => 999]]);
        $j = function (string $no, string $tgl, array $lines, ?string $bukti = null) use ($db) {
            $db->table('jurnal')->insert(['no_jurnal' => $no, 'no_bukti' => $bukti ?? 'B'.$no, 'tgl_jurnal' => $tgl, 'jam_jurnal' => '08:00:00', 'jenis' => 'U', 'keterangan' => 'Jurnal '.$no]);
            foreach ($lines as [$rek, $d, $k]) {
                $db->table('detailjurnal')->insert(['no_jurnal' => $no, 'kd_rek' => $rek, 'debet' => $d, 'kredit' => $k]);
            }
        };
        $j('J1', '2026-03-10', [['113010', 5000000, 0], ['412010', 0, 5000000]]);
        $j('J2', '2026-04-05', [['111010', 2000000, 0], ['411010', 0, 2000000]]);
        $j('J3', '2026-05-01', [['115010', 3000000, 0], ['211010', 0, 3000000]]);
        $j('J4', '2026-06-01', [['511010', 1500000, 0], ['111010', 0, 1500000]]);
        $j('J6', '2026-07-01', [['111010', 50000, 0], ['411010', 0, 40000]]);    // tidak seimbang 10.000
        // Setelah cut off (go-live 2 Nov): tagihan pasien ranap lanjutan R5 dan pembayaran supplier lewat Khanza
        $j('J5', '2026-11-04', [['113010', 4000000, 0], ['412010', 0, 4000000]], 'R5');
        $j('J7', '2026-11-05', [['211010', 500000, 0], ['112010', 0, 500000]]);

        $db->table('penjab')->insert([['kd_pj' => 'BPJ', 'png_jawab' => 'BPJS KESEHATAN'], ['kd_pj' => 'UMU', 'png_jawab' => 'UMUM']]);
        $db->table('poliklinik')->insert(['kd_poli' => 'INT', 'nm_poli' => 'Poli Penyakit Dalam']);
        $db->table('pasien')->insert([['no_rkm_medis' => '001', 'nm_pasien' => 'BUDI'], ['no_rkm_medis' => '002', 'nm_pasien' => 'ANI']]);
        $reg = fn ($no, $tgl, $pj, $lanjut, $bayar, $rm = '001') => ['no_rawat' => $no, 'tgl_registrasi' => $tgl, 'no_rkm_medis' => $rm, 'kd_poli' => 'INT', 'kd_pj' => $pj, 'stts' => 'Sudah', 'status_lanjut' => $lanjut, 'status_bayar' => $bayar];
        $db->table('reg_periksa')->insert([
            $reg('R1', '2026-03-10', 'BPJ', 'Ranap', 'Sudah Bayar'), $reg('R2', '2026-08-01', 'UMU', 'Ralan', 'Sudah Bayar'), $reg('R3', '2026-09-01', 'UMU', 'Ralan', 'Sudah Bayar', '002'),
            $reg('R4', '2026-10-30', 'UMU', 'Ralan', 'Belum Bayar', '002'), $reg('R5', '2026-10-29', 'BPJ', 'Ranap', 'Belum Bayar'), $reg('R6', '2026-10-01', 'UMU', 'Ranap', 'Sudah Bayar'),
            $reg('R7', '2026-11-02', 'UMU', 'Ralan', 'Belum Bayar'), $reg('R8', '2026-10-31', 'UMU', 'Ralan', 'Sudah Bayar', '002'),
        ]);
        $db->table('nota_inap')->insert([['no_rawat' => 'R6', 'tanggal' => '2026-10-05'], ['no_rawat' => 'R5', 'tanggal' => '2026-11-04']]);
        $db->table('nota_jalan')->insert(['no_rawat' => 'R8', 'tanggal' => '2026-11-02']); // dibayar setelah cut off
        $db->table('piutang_pasien')->insert([
            ['no_rawat' => 'R1', 'tgl_piutang' => '2026-03-10', 'no_rkm_medis' => '001', 'status' => 'Belum Lunas', 'totalpiutang' => 5000000, 'uangmuka' => 0, 'sisapiutang' => 5000000, 'tgltempo' => '2026-04-10'],
            ['no_rawat' => 'R2', 'tgl_piutang' => '2026-08-01', 'no_rkm_medis' => '001', 'status' => 'Lunas', 'totalpiutang' => 300000, 'uangmuka' => 0, 'sisapiutang' => 300000, 'tgltempo' => '2026-08-15'],
            ['no_rawat' => 'R3', 'tgl_piutang' => '2026-09-01', 'no_rkm_medis' => '002', 'status' => 'Belum Lunas', 'totalpiutang' => 200000, 'uangmuka' => 0, 'sisapiutang' => 200000, 'tgltempo' => '2026-09-15'],
        ]);
        $db->table('bayar_piutang')->insert([
            ['tgl_bayar' => '2026-09-01', 'no_rkm_medis' => '001', 'besar_cicilan' => 300000, 'no_rawat' => 'R2', 'diskon_piutang' => 0, 'tidak_terbayar' => 0],
            ['tgl_bayar' => '2026-11-05', 'no_rkm_medis' => '001', 'besar_cicilan' => 5000000, 'no_rawat' => 'R1', 'diskon_piutang' => 0, 'tidak_terbayar' => 0], // setelah cut off
        ]);
        $db->table('datasuplier')->insert(['kode_suplier' => 'S1', 'nama_suplier' => 'PT Kimia Farma']);
        $db->table('pemesanan')->insert(['no_faktur' => 'F1', 'kode_suplier' => 'S1', 'tgl_pesan' => '2026-05-01', 'tgl_faktur' => '2026-05-01', 'tgl_tempo' => '2026-06-01', 'tagihan' => 3000000, 'status' => 'Belum Dibayar']);
        $db->table('bayar_pemesanan')->insert(['tgl_bayar' => '2026-06-01', 'no_faktur' => 'F1', 'besar_bayar' => 1000000]);
        $db->table('jenis')->insert(['kdjns' => 'J1', 'nama' => 'Obat']);
        $db->table('bangsal')->insert(['kd_bangsal' => 'AP', 'nm_bangsal' => 'Apotek']);
        $db->table('databarang')->insert(['kode_brng' => 'B1', 'nama_brng' => 'Paracetamol 500', 'kode_sat' => 'TAB', 'h_beli' => 50000, 'kdjns' => 'J1']);
        $db->table('riwayat_barang_medis')->insert([
            ['kode_brng' => 'B1', 'kd_bangsal' => 'AP', 'tanggal' => '2026-05-01', 'jam' => '08:00:00', 'stok_akhir' => 100, 'no_batch' => '', 'no_faktur' => ''],
            ['kode_brng' => 'B1', 'kd_bangsal' => 'AP', 'tanggal' => '2026-10-20', 'jam' => '09:00:00', 'stok_akhir' => 60, 'no_batch' => '', 'no_faktur' => ''],
            ['kode_brng' => 'B1', 'kd_bangsal' => 'AP', 'tanggal' => '2026-11-02', 'jam' => '09:00:00', 'stok_akhir' => 40, 'no_batch' => '', 'no_faktur' => ''],
        ]);
        $db->table('kamar_inap')->insert([
            ['no_rawat' => 'R5', 'tgl_masuk' => '2026-10-29', 'tgl_keluar' => '2026-11-04', 'stts_pulang' => 'Sembuh'], // pulang setelah go-live
            ['no_rawat' => 'R6', 'tgl_masuk' => '2026-10-01', 'tgl_keluar' => '2026-10-05', 'stts_pulang' => 'Sembuh'],
        ]);
    }

    protected function snapshot(): void
    {
        $this->actingAs($this->akunting)->post(route('admin.closing.snapshot'))->assertRedirect();
        $this->actingAs($this->akunting)->post(route('admin.closing.mapping.suggest'))->assertRedirect();
    }

    public function test_access_by_role(): void
    {
        $this->actingAs($this->akunting)->get(route('admin.closing'))->assertOk()->assertSee('Daftar periksa closing');
        $this->actingAs(User::where('email', 'direktur@rsuserenapita.id')->first())->get(route('admin.closing'))->assertForbidden();
        $komisaris = User::where('email', 'komisaris@rsuserenapita.id')->first();
        $this->actingAs($komisaris)->get(route('admin.closing'))->assertOk()->assertSee('Hanya lihat');
        $this->actingAs($komisaris)->post(route('admin.closing.snapshot'))->assertForbidden();
        $this->actingAs(User::where('email', 'datapasien@rsuserenapita.id')->first())->get(route('admin.closing'))->assertForbidden();
        $this->actingAs($this->akunting)->get(route('admin.management'))->assertOk()->assertSee('Closing akuntansi')->assertSee('Panduan akunting');
        $this->actingAs($this->akunting)->get(route('admin.closing.guide'))->assertOk()->assertSee('Bab 14. Pertanyaan dan masalah umum')->assertSee('Bab 10. Ranap lanjutan');
        $this->actingAs($komisaris)->get(route('admin.closing.guide'))->assertOk();
        $this->actingAs(User::where('email', 'direktur@rsuserenapita.id')->first())->get(route('admin.closing.guide'))->assertForbidden();
    }

    public function test_snapshot_mapping_and_trial_balance(): void
    {
        $this->snapshot();
        $snap = ClosingSnapshot::latest();
        $this->assertSame('2026-11-01', $snap->cutoff->toDateString());
        $this->assertArrayHasKey('hutang_non_medis', $snap->errors);    // tabel non medis tidak ada di Khanza tiruan
        $this->assertArrayNotHasKey('piutang', $snap->errors);

        $map = ClosingMapping::pluck('coa_code', 'kd_rek')->all();
        $this->assertSame(['111010' => '1101', '112010' => '1102', '113010' => '1111', '115010' => '1120', '211010' => '2101', '311010' => '3101',
            '411010' => '4107', '412010' => '4101', '511010' => '5302'], array_intersect_key($map, array_flip(['111010', '112010', '113010', '115010', '211010', '311010', '411010', '412010', '511010'])));
        $this->assertArrayNotHasKey('113020', $map); // tidak bersaldo, tidak perlu dipetakan (112010 dipetakan karena dipakai setelah cut off)

        $book = new ClosingBook;
        $tb = $book->trialBalance();
        $this->assertEquals(10550000, $tb['rows']['1101']['akhir']);      // 10jt + 2jt − 1,5jt + 50rb; jurnal setelah cut off tidak ikut
        $this->assertEquals(-2040000, $tb['rows']['4107']['akhir']);
        $this->assertEquals(10000, $tb['total']['selisih']);               // dari jurnal J6 yang tidak seimbang
        $this->assertCount(1, $snap->rows('tidak_seimbang'));
        $this->assertSame(['jurnal' => 2, 'kunjungan' => 1], array_intersect_key($snap->rows('setelah_cutoff')[0], ['jurnal' => 1, 'kunjungan' => 1]));
        $this->assertSame(['R4', 'R8'], array_column($snap->rows('belum_ditagih'), 'no_rawat')); // R5 dirawat → ranap lanjutan
        $dirawat = $snap->rows('dirawat');
        $this->assertSame(['R5'], array_column($dirawat, 'no_rawat'));
        $this->assertFalse($dirawat[0]['masih_dirawat']);
        $this->assertSame('2026-11-04', $dirawat[0]['tgl_pulang']);

        // Ranap lanjutan: tidak masuk neraca saldo, diringkas per bulan per COA
        $co = $book->carryOver();
        $this->assertSame(0, $co['unmapped']);
        $this->assertSame(['J7'], $co['other']);
        $this->assertEquals(4000000, $co['patients'][0]['pendapatan']);
        $nov = collect($co['byCoa'])->where('kelompok', 'Ranap lanjutan')->keyBy('code');
        $this->assertEquals(4000000, $nov['1111']['debit']);
        $this->assertEquals(4000000, $nov['4101']['credit']);
        $this->assertSame('2026-11', $nov['4101']['bulan']);
        $this->assertEquals(-5000000, $tb['rows']['4101']['akhir']);   // J5 tidak ikut
        $this->actingAs($this->akunting)->get(route('admin.closing.carryover'))->assertOk()->assertSee('Pulang 2026-11-04')->assertSee('Jurnal lain setelah cut off');
        $this->actingAs($this->akunting)->get(route('admin.closing.export', 'ranap-lanjutan'))->assertOk();

        foreach (['index' => 'admin.closing', 'mapping' => 'admin.closing.mapping', 'trial' => 'admin.closing.trial', 'statements' => 'admin.closing.statements',
            'reconcile' => 'admin.closing.reconcile', 'carryover' => 'admin.closing.carryover', 'coa' => 'admin.closing.coa', 'adjustments' => 'admin.closing.adjustments'] as $route) {
            $this->actingAs($this->akunting)->get(route($route))->assertOk();
        }
        $this->actingAs($this->akunting)->get(route('admin.closing.trial', ['rinci' => 1]))->assertOk()->assertSee('Kas Kecil');
    }

    public function test_adjustments_and_reconciliation(): void
    {
        $this->snapshot();
        // Jurnal penyesuaian biasa harus seimbang
        $this->actingAs($this->akunting)->post(route('admin.closing.adjustments.store'), ['date' => '2026-10-31', 'description' => 'Tes', 'kind' => 'penyesuaian',
            'lines' => [['coa_code' => '4107', 'credit' => 10000], ['coa_code' => '1101', 'debit' => 9000]]])->assertSessionHasErrors('lines');
        // Tanggal di luar periode ditolak
        $this->actingAs($this->akunting)->post(route('admin.closing.adjustments.store'), ['date' => '2026-11-02', 'description' => 'Tes', 'kind' => 'penyesuaian',
            'lines' => [['coa_code' => '4107', 'credit' => 1], ['coa_code' => '1101', 'debit' => 1]]])->assertSessionHasErrors('date');
        // Akun header tidak boleh dipakai
        $this->actingAs($this->akunting)->post(route('admin.closing.adjustments.store'), ['date' => '2026-10-31', 'description' => 'Tes', 'kind' => 'penyesuaian',
            'lines' => [['coa_code' => '1100', 'credit' => 1], ['coa_code' => '1101', 'debit' => 1]]])->assertSessionHasErrors('lines.0.coa_code');
        // Koreksi sepihak melengkapi jurnal J6
        $this->actingAs($this->akunting)->post(route('admin.closing.adjustments.store'), ['date' => '2026-07-01', 'description' => 'Lengkapi J6', 'kind' => 'koreksi',
            'lines' => [['coa_code' => '4107', 'credit' => 10000, 'memo' => 'J6']]])->assertRedirect(route('admin.closing.adjustments'));
        // Pembayaran hutang yang belum dijurnal di Khanza
        $this->actingAs($this->akunting)->post(route('admin.closing.adjustments.store'), ['date' => '2026-06-01', 'description' => 'Bayar F1', 'kind' => 'penyesuaian',
            'lines' => [['coa_code' => '2101', 'debit' => 1000000], ['coa_code' => '1102', 'credit' => 1000000], ['coa_code' => '', 'debit' => '']]])->assertRedirect();
        $this->assertSame(['JP-0001', 'JP-0002'], ClosingAdjustment::orderBy('id')->pluck('number')->all());

        $book = new ClosingBook;
        $this->assertEquals(0, $book->trialBalance()['total']['selisih']);
        $sum = $book->balanceSummary();
        $this->assertEquals(5550000, $sum['laba']);   // 2.050.000 + 5.000.000 − 1.500.000
        $this->assertEquals(0, $sum['selisih']);

        $r = collect($book->reconciliation())->map(fn ($x) => collect($x['lines'])->keyBy('code'));
        $this->assertEquals(0, $r['piutang']['1111']['diff']);          // pelunasan setelah cut off tidak mengurangi
        $this->assertEquals(-200000, $r['piutang']['1110']['diff']);    // piutang umum R3 belum dijurnal di Khanza
        $this->assertEquals(0, $r['hutang']['2101']['diff']);           // 3jt − 1jt (setelah penyesuaian)
        $this->assertEquals(3000000, $r['persediaan']['1120']['sub']);  // 60 × 50.000 (stok per cut off)

        $open = collect($book->opening('tutup'))->keyBy('code');
        $this->assertEquals(5550000, $open['3202']['credit']);
        $this->assertFalse($open->has('4107'));
        $this->assertEquals($open->sum('debit'), $open->sum('credit'));
        $this->assertTrue(collect($book->opening('rinci'))->contains('code', '4107'));

        // Monthly P&L: koreksi J6 masuk Juli
        $this->assertEquals(50000, $book->monthlyPL()['4107']['2026-07']);
    }

    public function test_exports(): void
    {
        $this->snapshot();
        $res = $this->actingAs($this->akunting)->get(route('admin.closing.export', 'paket'));
        $res->assertOk();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($res->getFile()->getPathname()));
        $this->assertStringContainsString('Saldo awal SIMRS baru', $zip->getFromName('xl/workbook.xml'));
        $this->assertNotFalse($zip->getFromName('xl/worksheets/sheet12.xml'));
        $zip->close();
        $this->actingAs($this->akunting)->get(route('admin.closing.export', ['saldo-awal', 'mode' => 'rinci']))->assertOk();
        $this->actingAs($this->akunting)->get(route('admin.closing.export', 'tidak-ada'))->assertNotFound();
    }

    public function test_lock_and_mapping_validation(): void
    {
        $this->snapshot();
        $this->actingAs($this->akunting)->post(route('admin.closing.mapping.save'), ['map' => ['111010' => '1100']])->assertSessionHas('err');
        $this->assertSame('1101', ClosingMapping::where('kd_rek', '111010')->value('coa_code'));
        // Belum seimbang → tidak bisa dikunci
        $this->actingAs($this->akunting)->post(route('admin.closing.lock'), ['action' => 'lock'])->assertSessionHas('err');
        $this->actingAs($this->akunting)->post(route('admin.closing.adjustments.store'), ['date' => '2026-07-01', 'description' => 'Lengkapi J6', 'kind' => 'koreksi',
            'lines' => [['coa_code' => '4107', 'credit' => 10000]]]);
        $this->actingAs($this->akunting)->post(route('admin.closing.lock'), ['action' => 'lock'])->assertSessionHas('ok');
        $this->assertTrue(ClosingBook::settings()['locked']);
        $this->actingAs($this->akunting)->post(route('admin.closing.mapping.save'), ['map' => ['111010' => '1102']])->assertSessionHasErrors('locked');
        $this->actingAs($this->akunting)->post(route('admin.closing.snapshot'))->assertSessionHasErrors('locked');
        $this->actingAs($this->akunting)->post(route('admin.closing.lock'), ['action' => 'unlock'])->assertSessionHas('ok');
        $this->actingAs($this->akunting)->post(route('admin.closing.mapping.save'), ['map' => ['111010' => '1102']])->assertSessionHas('ok');
    }

    public function test_coa_import_from_template(): void
    {
        $file = new UploadedFile(base_path('tests/fixtures/coa-serenavita.xlsx'), 'COA.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->actingAs($this->akunting)->post(route('admin.closing.coa.import'), ['file' => $file])->assertSessionHas('ok');
        $book = new ClosingBook;
        $this->assertCount(103, $book->coa);
        $this->assertCount(84, $book->detailCodes());
        $this->assertSame('3100', $book->account('3201')->parent_code);
        $this->assertSame('detail', $book->account('6201')->type);
    }
}
