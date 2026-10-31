<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\Role;
use App\Models\StockEntry;
use App\Models\User;
use App\Support\SimrsStockFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FarmasiCompareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Petugas Uji', 'email' => 'uji@example.test', 'password' => 'rahasia123',
            'role_id' => Role::where('name', 'Farmasi')->value('id'), 'is_active' => true]);
        $p1 = Medicine::create(['code' => 'P1', 'name' => 'PARACETAMOL 500 MG TAB', 'group' => 'TABLET', 'unit' => 'Tablet', 'is_active' => true]);
        $p2 = Medicine::create(['code' => 'P2', 'name' => 'AMOXICILLIN 500 MG', 'group' => 'TABLET', 'unit' => 'Kapsul', 'is_active' => true]);
        $p9 = Medicine::create(['code' => 'P9', 'name' => 'VITAMIN C', 'group' => 'TABLET', 'unit' => 'Tablet', 'is_active' => true]);
        foreach ([[$p1, 100], [$p2, 35], [$p9, 7]] as [$m, $qty]) {
            StockEntry::create(['medicine_id' => $m->id, 'location' => 'sentral', 'date' => today(), 'counted' => $qty]);
        }
    }

    public function test_reader_picks_the_sheet_with_stock(): void
    {
        $read = SimrsStockFile::read(base_path('tests/fixtures/simrs-stok.xlsx'), 'simrs-stok.xlsx');

        $this->assertSame('farmasi central', $read['sheet']);
        $this->assertSame(['Gudang farmasi', 'farmasi central'], $read['sheets']);
        $this->assertSame(['code' => 'P2', 'name' => 'AMOXICILLIN 500 MG (NAMA BARU)', 'unit' => 'CAPSUL', 'stock' => 40], $read['rows'][1]);
        $this->assertSame(0, $read['rows'][4]['stock']);
    }

    public function test_upload_compares_by_code_and_can_sync_names_and_new_codes(): void
    {
        $file = new UploadedFile(base_path('tests/fixtures/simrs-stok.xlsx'), 'simrs-stok.xlsx', null, null, true);
        $this->actingAs($this->user)->post(route('admin.stock.compare.upload'), ['file' => $file, 'unit' => 'sentral', 'date' => today()->toDateString()])
            ->assertRedirect(route('admin.stock.compare'));

        $page = $this->actingAs($this->user)->get(route('admin.stock.compare'))->assertOk();
        $page->assertSee('data-status="cocok"', false)->assertSee('-5')        // P2: sistem 35, SIMRS 40
            ->assertSee('data-status="baru"', false)->assertSee('data-status="hilang"', false)   // P3 baru, P9 tidak ada di SIMRS
            ->assertSee('di sistem: AMOXICILLIN 500 MG');

        $csv = $this->actingAs($this->user)->get(route('admin.stock.compare.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('P2;"AMOXICILLIN 500 MG (NAMA BARU)";"AMOXICILLIN 500 MG";Obat;40;35;-5;Selisih', $csv);

        $this->actingAs($this->user)->post(route('admin.stock.compare.rename'))->assertSessionHas('ok');
        $this->assertSame('AMOXICILLIN 500 MG (NAMA BARU)', Medicine::where('code', 'P2')->value('name'));
        $this->assertSame(1, StockEntry::where('medicine_id', Medicine::where('code', 'P2')->value('id'))->count(), 'Riwayat stok tetap');

        $this->actingAs($this->user)->post(route('admin.stock.compare.add'))->assertSessionHas('ok');
        $new = Medicine::where('code', 'P3')->first();
        $this->assertFalse($new->is_active);
        $this->assertSame('Tablet', $new->unit);
    }

    public function test_wrong_file_shows_a_clear_error(): void
    {
        $file = UploadedFile::fake()->createWithContent('lain.csv', "nama;jumlah\nA;1\n");
        $this->actingAs($this->user)->from(route('admin.stock.compare'))
            ->post(route('admin.stock.compare.upload'), ['file' => $file, 'unit' => 'sentral', 'date' => today()->toDateString()])
            ->assertSessionHasErrors('file');
    }

    public function test_zero_stock_rows_are_hidden_until_searched_and_reactivate_when_filled(): void
    {
        $empty = Medicine::create(['code' => 'Z1', 'name' => 'DEXAMETASONE 0,5 MG', 'group' => 'TABLET', 'unit' => 'Tablet', 'is_active' => false]);

        $html = $this->actingAs($this->user)->get(route('admin.stock', ['unit' => 'sentral', 'jenis' => 'obat']))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/class="stk-row off"\s+hidden\s+data-on="0" data-id="'.$empty->id.'"/', $html);
        $this->assertStringContainsString('data-on="1" data-id="'.Medicine::where('code', 'P1')->value('id').'"', $html);

        $this->actingAs($this->user)->post(route('admin.stock.save'), [
            'location' => 'sentral', 'date' => today()->toDateString(), 'rows' => [$empty->id => ['in' => '50']],
        ])->assertSessionHasNoErrors();
        $this->assertTrue($empty->fresh()->is_active);
    }
}
