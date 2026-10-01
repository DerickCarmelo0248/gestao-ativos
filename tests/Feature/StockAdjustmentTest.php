<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
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

    public function test_admin_adjustment_records_delta_and_rejects_stale_or_invalid_requests(): void
    {
        $this->signIn('admin');
        $item = Item::create(['category_id' => $this->category()->id, 'name' => 'Mouse', 'code' => bin2hex(random_bytes(5)), 'tracking_type' => 'quantity']);
        $unit = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Teste']);
        $id = DB::table('stock_balances')->insertGetId(['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 5]);
        $url = route('stock-balances.adjust', $id);
        $this->get(route('stock-balances.show', $id))->assertOk()->assertSee('Ajustar quantidade');
        $this->put($url, ['quantity' => 8, 'expected_quantity' => 5, 'reason' => 'Inventário'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stock_balances', ['id' => $id, 'quantity' => 8]);
        $this->assertDatabaseHas('stock_movements', ['item_id' => $item->id, 'type' => 'adjustment_add', 'quantity' => 3, 'user_id' => auth()->id()]);
        $this->putJson($url, ['quantity' => 3, 'expected_quantity' => 5, 'reason' => 'Tela antiga'])->assertUnprocessable();
        $this->putJson($url, ['quantity' => -1, 'expected_quantity' => 8, 'reason' => 'Teste'])->assertUnprocessable();
        $this->putJson($url, ['quantity' => 0, 'expected_quantity' => 8, 'reason' => ' '])->assertUnprocessable();
        $this->put($url, ['quantity' => 0, 'expected_quantity' => 8, 'reason' => 'Conferência'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stock_movements', ['item_id' => $item->id, 'type' => 'adjustment_remove', 'quantity' => 8]);
        $count = DB::table('stock_movements')->where('item_id', $item->id)->count();
        $this->put($url, ['quantity' => 0, 'expected_quantity' => 0, 'reason' => 'Sem mudança'])->assertSessionHasNoErrors();
        $this->assertSame($count, DB::table('stock_movements')->where('item_id', $item->id)->count());
        $this->get(route('stock-balances.show', $id))->assertOk()->assertSee('Ajuste: redução')->assertSee('Conferência');
        $this->signIn('operator');
        $this->get(route('stock-balances.show', $id))->assertOk()->assertDontSee('Ajustar quantidade');
        $this->put($url, ['quantity' => 10, 'expected_quantity' => 0, 'reason' => 'Teste'])->assertForbidden();
        $this->assertDatabaseHas('stock_balances', ['id' => $id, 'quantity' => 0]);
    }
}