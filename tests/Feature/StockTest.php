<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\Role;
use App\Models\StockEntry;
use App\Models\User;
use App\Support\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    protected function pharmacist(): User
    {
        return User::create(['name' => 'Petugas Uji', 'email' => fake()->unique()->safeEmail(), 'password' => 'rahasia123',
            'role_id' => Role::where('name', 'Farmasi')->value('id'), 'is_active' => true]);
    }

    protected function medicine(): Medicine
    {
        return Medicine::create(['name' => 'PARACETAMOL 500 MG TABLET', 'group' => 'TABLET', 'unit' => 'Tablet', 'price' => 200]);
    }

    public function test_migration_imports_medicines_and_creates_pharmacy_role(): void
    {
        $this->assertGreaterThan(300, Medicine::count());
        $this->assertSame(['stock', 'stock-report', 'medicines', 'bmhp'], Role::where('name', 'Farmasi')->first()->permissions);
    }

    public function test_pharmacy_role_only_opens_pharmacy_menus(): void
    {
        $user = $this->pharmacist();

        $this->actingAs($user)->get(route('admin.home'))->assertRedirect(url('admin/stock'));
        $this->actingAs($user)->get(route('admin.stock'))->assertOk()->assertSee('Input stok harian')->assertSee('window.TOUR', false);
        $this->actingAs($user)->get(route('admin.stock.report'))->assertOk();
        $this->actingAs($user)->get(route('admin.crud.index', 'medicines'))->assertOk()->assertSee('window.TOUR', false);
        $this->actingAs($user)->get(route('admin.crud.create', 'medicines'))->assertOk()->assertSee('window.TOUR', false);
        $this->actingAs($user)->get(route('admin.crud.index', 'news'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_daily_in_and_out_update_the_balance(): void
    {
        $user = $this->pharmacist();
        $med = $this->medicine();
        $yesterday = today()->subDay()->toDateString();

        $this->actingAs($user)->post(route('admin.stock.save'), [
            'location' => 'gudang', 'date' => $yesterday, 'rows' => [$med->id => ['in' => '100', 'out' => '30', 'note' => 'dari PBF']],
        ])->assertRedirect()->assertSessionHas('ok');
        $this->actingAs($user)->post(route('admin.stock.save'), [
            'location' => 'gudang', 'date' => today()->toDateString(), 'rows' => [$med->id => ['out' => '20']],
        ])->assertSessionHasNoErrors();

        $today = StockLedger::summary('gudang', today(), today())[$med->id];
        $this->assertSame(70, $today['start']);
        $this->assertSame(50, $today['end']);
        $this->assertSame(0, StockLedger::summary('sentral', today(), today())[$med->id]['end'] ?? 0);
        $this->assertSame($user->id, StockEntry::first()->user_id);
    }

    public function test_stock_opname_overrides_the_balance_and_records_the_difference(): void
    {
        $user = $this->pharmacist();
        $med = $this->medicine();
        StockEntry::create(['medicine_id' => $med->id, 'location' => 'sentral', 'date' => today()->subDays(2), 'qty_in' => 50]);

        $this->actingAs($user)->post(route('admin.stock.save'), [
            'location' => 'sentral', 'date' => today()->subDay()->toDateString(), 'opname' => 1,
            'rows' => [$med->id => ['in' => '', 'out' => '5', 'counted' => '40', 'expired_at' => '2027-03-01']],
        ])->assertSessionHasNoErrors();

        $s = StockLedger::summary('sentral', today()->subDay(), today())[$med->id];
        $this->assertSame(50, $s['start']);
        $this->assertSame(-10, $s['adjust']);
        $this->assertSame(35, $s['end']);
        $this->assertSame('2027-03-01', $med->fresh()->expired_at->toDateString());
    }

    public function test_out_more_than_stock_is_rejected_and_nothing_is_saved(): void
    {
        $user = $this->pharmacist();
        $med = $this->medicine();
        $other = Medicine::create(['name' => 'AMOXICILLIN 500 MG', 'group' => 'TABLET', 'unit' => 'Kapsul']);

        $this->actingAs($user)->post(route('admin.stock.save'), [
            'location' => 'gudang', 'date' => today()->toDateString(),
            'rows' => [$other->id => ['in' => '10'], $med->id => ['out' => '5']],
        ])->assertSessionHasErrors('rows');

        $this->assertSame(0, StockEntry::count());
    }

    public function test_clearing_a_row_removes_the_entry(): void
    {
        $user = $this->pharmacist();
        $med = $this->medicine();
        StockEntry::create(['medicine_id' => $med->id, 'location' => 'gudang', 'date' => today(), 'qty_in' => 10]);

        $this->actingAs($user)->post(route('admin.stock.save'), [
            'location' => 'gudang', 'date' => today()->toDateString(), 'rows' => [$med->id => ['in' => '', 'out' => '', 'note' => '']],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, StockEntry::count());
    }

    public function test_future_dates_are_rejected(): void
    {
        $med = $this->medicine();

        $this->actingAs($this->pharmacist())->post(route('admin.stock.save'), [
            'location' => 'gudang', 'date' => today()->addDay()->toDateString(), 'rows' => [$med->id => ['in' => '1']],
        ])->assertSessionHasErrors('date');
    }

    public function test_report_card_and_export_show_the_month(): void
    {
        $user = $this->pharmacist();
        $med = $this->medicine();
        Carbon::setTestNow(today()->startOfMonth()->addDays(9));
        StockEntry::create(['medicine_id' => $med->id, 'location' => 'gudang', 'date' => today()->subDay(), 'qty_in' => 100, 'qty_out' => 40, 'note' => 'ke farmasi']);

        $this->actingAs($user)->get(route('admin.stock.report', ['unit' => 'gudang']))->assertOk()->assertSee($med->name);
        $this->actingAs($user)->get(route('admin.stock.card', [$med, 'unit' => 'gudang']))->assertOk()->assertSee('ke farmasi');
        $csv = $this->actingAs($user)->get(route('admin.stock.export', ['unit' => 'gudang']))->assertOk()->streamedContent();
        $this->assertStringContainsString('"PARACETAMOL 500 MG TABLET";Tablet;0;100;40;0;60;4,00;28;40;Aman;200;12000', $csv);
        $this->assertLessThan(strpos($csv, 'TABLET;'), strpos($csv, 'INJEKSI;'));
        Carbon::setTestNow();
    }
}
