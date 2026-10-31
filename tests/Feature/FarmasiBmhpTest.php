<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmasiBmhpTest extends TestCase
{
    use RefreshDatabase;

    protected string $file = 'storage/framework/testing/bmhp-test.json';

    protected function setUp(): void
    {
        parent::setUp();
        @mkdir(base_path('storage/framework/testing'), 0777, true);
        file_put_contents(base_path($this->file), json_encode(['items' => [
            ['name' => 'APRON ONE', 'expired_at' => null],
            ['name' => 'ABOCATH 24', 'expired_at' => '2029-06-01'],
            ['name' => 'KASA STERIL TIDAK ADA', 'expired_at' => null],
        ]]));
        Medicine::create(['code' => 'X1', 'name' => 'APRON ONE - DANVI', 'group' => 'LAINNYA', 'unit' => 'Pcs', 'is_active' => true]);
        Medicine::create(['code' => 'X2', 'name' => 'ABOCATH 24', 'group' => 'ALKES', 'unit' => 'Pcs', 'is_active' => true]);
        Medicine::create(['code' => 'X3', 'name' => 'ABOCATH 24', 'group' => 'ALKES', 'unit' => 'Pcs', 'is_active' => false]);
        Medicine::create(['code' => 'X4', 'name' => 'AMOXICILLIN 500 MG', 'group' => 'TABLET', 'unit' => 'Tablet', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(base_path($this->file));
        parent::tearDown();
    }

    public function test_listed_items_move_to_bmhp_and_others_stay(): void
    {
        $this->artisan('farmasi:import-bmhp', ['file' => $this->file, '--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, Medicine::where('kind', 'bmhp')->count());

        $this->artisan('farmasi:import-bmhp', ['file' => $this->file])->assertSuccessful();

        $this->assertEqualsCanonicalizing(['X1', 'X2', 'X3'], Medicine::where('kind', 'bmhp')->pluck('code')->all());
        $this->assertSame('ALKES', Medicine::where('code', 'X1')->value('group'));
        $this->assertSame('2029-06-01', Medicine::where('code', 'X2')->first()->expired_at->toDateString());
        $this->assertSame('obat', Medicine::where('code', 'X4')->value('kind'));
        $this->assertSame(0, Medicine::where('name', 'like', 'KASA STERIL%')->count(), 'Nama yang tidak ada di database tidak dibuat');
    }

    public function test_pages_separate_medicines_and_bmhp(): void
    {
        $this->artisan('farmasi:import-bmhp', ['file' => $this->file]);
        $user = User::create(['name' => 'Petugas Uji', 'email' => 'uji@example.test', 'password' => 'rahasia123',
            'role_id' => Role::where('name', 'Farmasi')->value('id'), 'is_active' => true]);

        $this->actingAs($user)->get(route('admin.stock', ['jenis' => 'bmhp']))->assertOk()
            ->assertSee('APRON ONE - DANVI')->assertDontSee('AMOXICILLIN 500 MG');
        $this->actingAs($user)->get(route('admin.stock.report'))->assertOk()
            ->assertSee('APRON ONE - DANVI')->assertDontSee('AMOXICILLIN 500 MG');
        $this->actingAs($user)->get(route('admin.stock', ['jenis' => 'obat']))->assertOk()
            ->assertSee('AMOXICILLIN 500 MG')->assertDontSee('APRON ONE - DANVI');

        $this->actingAs($user)->get(route('admin.crud.index', ['bmhp', 'q' => 'A']))->assertOk()->assertSee('APRON ONE - DANVI')->assertDontSee('AMOXICILLIN 500 MG');
        $this->actingAs($user)->get(route('admin.crud.index', ['medicines', 'q' => 'AMOXICILLIN 500 MG']))->assertOk()->assertSee('AMOXICILLIN 500 MG')->assertDontSee('APRON ONE - DANVI');
        $this->actingAs($user)->get(route('admin.crud.create', 'bmhp'))->assertOk()->assertSee('window.TOUR', false);
    }
}
