<?php

namespace Tests\Feature;

use App\Actions\OpenDisposalContainer;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use App\Queries\LowStock;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryThresholdAndDisposalTest extends TestCase
{
    private bool $transactionStarted = false;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue(app()->environment('testing'));
        $connection = DB::selectOne('SELECT current_database() AS banco, current_user AS usuario');
        $this->assertSame('gestao_ativos_test', $connection->banco);
        $this->assertSame('gestao_test', $connection->usuario);
        $this->artisan('migrate')->assertExitCode(0);
        DB::beginTransaction();
        $this->transactionStarted = true;
        $this->admin = User::factory()->create();
        $this->admin->role = 'admin';
        $this->admin->save();
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->transactionStarted) {
                DB::rollBack();
            }
        } finally {
            parent::tearDown();
        }
    }

    private function item(string $type = 'quantity', int $minimum = 3): Item
    {
        $code = 'TEST-'.strtoupper(bin2hex(random_bytes(6)));
        $category = Category::create(['name' => $code]);
        return Item::create(['category_id' => $category->id, 'code' => $code,
            'name' => $code, 'tracking_type' => $type, 'minimum_stock' => $minimum]);
    }

    private function unit(): Unit
    {
        return Unit::create(['code' => strtoupper(bin2hex(random_bytes(3))), 'name' => 'Unidade de teste']);
    }

    private function asset(Item $item, Unit $unit, string $status = 'available'): Asset
    {
        $asset = Asset::create(['item_id' => $item->id, 'unit_id' => $unit->id,
            'patrimony' => 'TEST-'.bin2hex(random_bytes(8))]);
        $asset->status = $status;
        $asset->save();
        return $asset;
    }

    public function test_minimum_is_saved_and_invalid_values_are_rejected(): void
    {
        $item = $this->item();
        $data = ['category_id' => $item->category_id, 'code' => $item->code.'-NEW',
            'name' => 'Novo item', 'tracking_type' => 'quantity', 'minimum_stock' => 5];
        $this->get(route('items.create'))->assertOk()->assertSee('minimum_stock');
        foreach ([-1, 1.5, 'abc', 2147483648] as $minimum) {
            $this->postJson(route('items.store'), array_replace($data, ['minimum_stock' => $minimum]))
                ->assertUnprocessable()->assertJsonValidationErrors('minimum_stock');
        }
        $this->post(route('items.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('items', ['code' => $data['code'], 'minimum_stock' => 5]);
    }

    public function test_alerts_only_include_units_with_registered_stock(): void
    {
        $item = $this->item();
        $unit = $this->unit();
        $other = $this->unit();
        DB::table('stock_balances')->insert(['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 3]);
        $query = fn () => LowStock::query()->where('item_id', $item->id);
        $this->assertSame(3, (int) $query()->where('unit_id', $unit->id)->value('quantity'));
        $this->assertFalse($query()->where('unit_id', $other->id)->exists());
        $this->assertFalse(LowStock::query(true)->where('item_id', $item->id)->where('unit_id', $other->id)->exists());
        DB::table('stock_balances')->where('item_id', $item->id)->update(['quantity' => 4]);
        $this->assertFalse($query()->where('unit_id', $unit->id)->exists());
        $this->get(route('dashboard', ['search' => $item->code, 'unit_id' => $other->id]))
            ->assertOk()->assertViewHas('lowBalances', fn ($rows) => $rows->total() === 0);
        DB::table('stock_balances')->where('item_id', $item->id)->update(['quantity' => 0]);
        foreach ([false, true] as $zeroOnly) {
            $rows = LowStock::query($zeroOnly)->where('item_id', $item->id)->get();
            $this->assertCount(1, $rows);
            $this->assertSame($unit->id, $rows->first()->unit_id);
        }
        $other->is_active = false;
        $other->save();
        $this->assertFalse($query()->where('unit_id', $other->id)->exists());
        $item->is_active = false;
        $item->save();
        $this->assertFalse($query()->exists());
    }

    public function test_individual_stock_counts_only_available_equipment(): void
    {
        $item = $this->item('individual', 1);
        $unit = $this->unit();
        foreach (['available', 'in_use', 'awaiting_disposal', 'in_container', 'disposed'] as $status) {
            $this->asset($item, $unit, $status);
        }
        $query = fn () => LowStock::query()->where('item_id', $item->id)->where('unit_id', $unit->id);
        $this->assertSame(1, (int) $query()->value('quantity'));
        $this->get(route('dashboard', ['search' => $item->code, 'unit_id' => $unit->id]))
            ->assertOk()->assertSee('Repor estoque');
        $this->asset($item, $unit);
        $this->assertFalse($query()->exists());
    }

    public function test_available_and_waiting_equipment_can_be_discarded_with_origin_and_history(): void
    {
        $unit = Unit::firstOrCreate(['code' => 'VOT'], ['name' => 'Votuporanga']);
        $origin = $this->unit();
        $container = app(OpenDisposalContainer::class)->handle($unit->id, $this->admin);
        $item = $this->item('individual');
        foreach (['available', 'awaiting_disposal'] as $status) {
            $asset = $this->asset($item, $origin, $status);
            $this->get(route('disposal-containers.show', $container))->assertOk()->assertSee($asset->patrimony);
            $url = route('disposal-containers.assets.store', $container);
            $this->postJson($url, ['asset_id' => $asset->id, 'reason' => ' '])
                ->assertUnprocessable()->assertJsonValidationErrors('reason');
            $this->post($url, ['asset_id' => $asset->id, 'reason' => 'Sem conserto'])
                ->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame('in_container', $asset->fresh()->status);
            $this->assertSame($origin->id, $asset->fresh()->unit_id);
            $this->assertDatabaseHas('disposal_container_items', ['asset_id' => $asset->id,
                'origin_unit_id' => $origin->id, 'reason' => 'Sem conserto']);
            $this->assertDatabaseHas('asset_movements', ['asset_id' => $asset->id, 'type' => 'container_entry']);
            $this->postJson($url, ['asset_id' => $asset->id, 'reason' => 'Duplicado'])
                ->assertUnprocessable()->assertJsonValidationErrors('asset_id');
        }
        foreach (['in_use', 'in_container', 'disposed'] as $status) {
            $asset = $this->asset($item, $origin, $status);
            $this->postJson(route('disposal-containers.assets.store', $container),
                ['asset_id' => $asset->id, 'reason' => 'Teste'])
                ->assertUnprocessable()->assertJsonValidationErrors('asset_id');
            $this->assertSame($status, $asset->fresh()->status);
        }
    }
}
