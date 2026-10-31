<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\StockEntry;
use App\Support\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FarmasiImportSoTest extends TestCase
{
    use RefreshDatabase;

    protected string $file = 'storage/framework/testing/so-test.json';

    protected function setUp(): void
    {
        parent::setUp();
        @mkdir(base_path('storage/framework/testing'), 0777, true);
        file_put_contents(base_path($this->file), json_encode(['tanggal_so' => '2026-10-30', 'items' => [
            ['code' => 'B000009084', 'name' => 'ACLAM DRY SIRUP', 'unit' => 'BOTOL', 'sentral' => 2, 'gudang' => 0, 'expired_at' => '2029-02-01'],
            ['code' => 'K001', 'name' => 'KETOROLAC INJ 30MG/ML', 'unit' => 'AMPUL', 'sentral' => 0, 'gudang' => 3228, 'expired_at' => null],
            ['code' => 'C001', 'name' => 'CLOBAZAM', 'unit' => 'CAPSUL', 'sentral' => 0, 'gudang' => 0, 'expired_at' => null],
            ['code' => 'A001', 'name' => 'ABOCATH 18 - DANVI', 'unit' => 'PCS', 'sentral' => 0, 'gudang' => 0, 'expired_at' => null],
        ]]));
    }

    protected function tearDown(): void
    {
        @unlink(base_path($this->file));
        parent::tearDown();
    }

    public function test_import_creates_medicines_and_opening_stock(): void
    {
        $this->artisan('farmasi:import-so', ['file' => $this->file])->assertSuccessful();

        $aclam = Medicine::where('code', 'B000009084')->first();
        $this->assertTrue($aclam->is_active);
        $this->assertSame('SIRUP', $aclam->group);
        $this->assertNotNull($aclam->price, 'Harga diambil dari daftar Excel lama bila namanya sama');
        $this->assertSame('2029-02-01', $aclam->expired_at->toDateString());

        $ketorolac = Medicine::where('code', 'K001')->first();
        $this->assertSame(['INJEKSI', 'Ampul', true], [$ketorolac->group, $ketorolac->unit, $ketorolac->is_active]);
        $day = Carbon::parse('2026-10-30');
        $this->assertSame(3228, StockLedger::summary('gudang', $day, $day)[$ketorolac->id]['end']);
        $this->assertSame(2, StockLedger::summary('sentral', $day, $day)[$aclam->id]['end']);

        $clobazam = Medicine::where('code', 'C001')->first();
        $this->assertSame(['NARKOTIKA', 'Kapsul', false], [$clobazam->group, $clobazam->unit, $clobazam->is_active]);
        $this->assertSame('ALKES', Medicine::where('code', 'A001')->value('group'));
        $this->assertSame(2, StockEntry::count());
    }

    public function test_import_can_be_run_again_without_duplicates(): void
    {
        $this->artisan('farmasi:import-so', ['file' => $this->file])->assertSuccessful();
        $this->artisan('farmasi:import-so', ['file' => $this->file])->assertSuccessful();

        $this->assertSame(4, Medicine::whereNotNull('code')->count());
        $this->assertSame(2, StockEntry::count());
    }

    public function test_replacing_old_list_keeps_medicines_with_history(): void
    {
        $used = Medicine::whereNull('code')->first();
        StockEntry::create(['medicine_id' => $used->id, 'location' => 'gudang', 'date' => '2026-10-31', 'counted' => 100]);

        $this->artisan('farmasi:import-so', ['file' => $this->file, '--ganti-daftar-lama' => true])->assertSuccessful();

        $this->assertSame(1, Medicine::whereNull('code')->count());
        $this->assertFalse($used->fresh()->is_active);
        $this->assertSame(1, $used->entries()->count());
    }
}
