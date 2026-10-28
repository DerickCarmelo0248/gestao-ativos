<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryTransferTest extends TestCase
{
    private bool $transactionStarted = false;

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

    private function signIn(string $role): void
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();
        $this->actingAs($user);
    }

    private function category(): Category
    {
        return Category::create(['name' => 'Categoria '.bin2hex(random_bytes(8))]);
    }

    private function asset(): \App\Models\Asset
    {
        $item = Item::create(['category_id' => $this->category()->id,
            'code' => 'EDIT-'.bin2hex(random_bytes(6)), 'name' => 'Monitor', 'tracking_type' => 'individual']);
        $unit = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Unidade teste']);
        return \App\Models\Asset::create(['item_id' => $item->id, 'unit_id' => $unit->id,
            'patrimony' => 'PAT-'.bin2hex(random_bytes(8))]);
    }

    public function test_quantity_transfer_conserves_stock_and_records_history(): void
    {
        $this->signIn('operator');
        $asset = $this->asset();
        $item = $asset->item;
        $item->tracking_type = 'quantity'; $item->save();
        $to = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Destino']);
        DB::table('stock_balances')->insert(['item_id' => $item->id, 'unit_id' => $asset->unit_id, 'quantity' => 10]);
        $data = ['item_id' => $item->id, 'from_unit_id' => $asset->unit_id, 'to_unit_id' => $to->id, 'quantity' => 4];
        $this->post(route('transfers.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('stock_balances', ['item_id' => $item->id, 'unit_id' => $asset->unit_id, 'quantity' => 6]);
        $this->assertDatabaseHas('stock_balances', ['item_id' => $item->id, 'unit_id' => $to->id, 'quantity' => 4]);
        $this->assertDatabaseHas('inventory_transfers', ['item_id' => $item->id, 'quantity' => 4, 'user_id' => auth()->id()]);
        $this->get(route('transfers.index'))->assertOk()->assertSee('Destino');
        $this->postJson(route('transfers.store'), array_replace($data, ['quantity' => 7]))->assertUnprocessable();
        $this->postJson(route('transfers.store'), array_replace($data, ['to_unit_id' => $asset->unit_id]))->assertUnprocessable();
        $this->assertSame(1, DB::table('inventory_transfers')->where('item_id', $item->id)->count());
        $this->assertSame(2, DB::table('stock_movements')->where('item_id', $item->id)->count());
    }

    public function test_assets_must_be_available_in_origin_and_transfer_is_atomic(): void
    {
        $this->signIn('admin');
        $one = $this->asset();
        $two = \App\Models\Asset::create(['item_id' => $one->item_id, 'unit_id' => $one->unit_id, 'patrimony' => 'T-'.bin2hex(random_bytes(8))]);
        $two->status = 'in_use'; $two->save();
        $to = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Destino']);
        $data = ['item_id' => $one->item_id, 'from_unit_id' => $one->unit_id, 'to_unit_id' => $to->id, 'asset_ids' => [$one->id, $two->id]];
        $this->postJson(route('transfers.store'), $data)->assertUnprocessable();
        $this->assertSame($one->unit_id, $one->fresh()->unit_id);
        $this->assertDatabaseMissing('inventory_transfers', ['asset_id' => $one->id]);
        $two->status = 'available'; $two->save();
        $this->post(route('transfers.store'), $data)->assertSessionHasNoErrors();
        foreach ([$one, $two] as $asset) {
            $this->assertSame($to->id, $asset->fresh()->unit_id);
            $this->assertSame('available', $asset->fresh()->status);
            $this->assertSame(2, $asset->movements()->count());
        }
        $this->postJson(route('transfers.store'), $data)->assertUnprocessable();
    }

    public function test_guest_cannot_transfer(): void
    {
        $this->get(route('transfers.index'))->assertRedirect(route('login'));
        $this->post(route('transfers.store'), [])->assertRedirect(route('login'));
    }
}