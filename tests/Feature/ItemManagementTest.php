<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ItemManagementTest extends TestCase
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

    private function item(): Item
    {
        return Item::create(['category_id' => $this->category()->id,
            'code' => 'TEST-'.strtoupper(bin2hex(random_bytes(6))), 'name' => 'Item teste', 'tracking_type' => 'quantity']);
    }

    public function test_admin_searches_and_updates_item_including_minimum(): void
    {
        $this->signIn('admin');
        $item = $this->item();
        $other = $this->item();
        $this->get(route('items.index', ['search' => $item->code]))->assertOk()->assertSee($item->code)->assertDontSee($other->code);
        $this->get(route('items.edit', $item))->assertOk()->assertSee($item->code);
        $data = ['category_id' => $item->category_id, 'code' => $item->code,
            'name' => 'Nome atualizado', 'description' => 'Descrição', 'tracking_type' => 'quantity', 'minimum_stock' => 8];
        $this->put(route('items.update', $item), $data)->assertSessionHasNoErrors()->assertRedirect(route('items.index'));
        $this->assertSame('Nome atualizado', $item->fresh()->name);
        $this->assertSame(8, $item->fresh()->minimum_stock);
        foreach ([['code' => $other->code], ['tracking_type' => 'individual'], ['minimum_stock' => -1], ['name' => ' ']] as $invalid) {
            $this->putJson(route('items.update', $item), array_replace($data, $invalid))
                ->assertUnprocessable()->assertJsonValidationErrors(array_keys($invalid));
        }
    }

    public function test_only_unused_items_can_be_deleted_even_when_balance_is_zero(): void
    {
        $this->signIn('admin');
        $unused = $this->item();
        $this->delete(route('items.destroy', $unused))->assertSessionHasNoErrors()->assertRedirect(route('items.index'));
        $this->assertDatabaseMissing('items', ['id' => $unused->id]);
        $used = $this->item();
        $unit = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Unidade teste']);
        DB::table('stock_balances')->insert(['item_id' => $used->id, 'unit_id' => $unit->id, 'quantity' => 0]);
        $this->deleteJson(route('items.destroy', $used))->assertUnprocessable()->assertJsonValidationErrors('item');
        $this->assertDatabaseHas('items', ['id' => $used->id]);
        $equipment = $this->item();
        $equipment->update(['tracking_type' => 'individual']);
        \App\Models\Asset::create(['item_id' => $equipment->id, 'unit_id' => $unit->id, 'patrimony' => bin2hex(random_bytes(8))]);
        $this->deleteJson(route('items.destroy', $equipment))->assertUnprocessable()->assertJsonValidationErrors('item');
    }

    public function test_guest_and_operator_cannot_manage_catalog(): void
    {
        $item = $this->item();
        foreach (['items.index', 'items.edit'] as $route) {
            $this->get(route($route, $route === 'items.edit' ? $item : []))->assertRedirect(route('login'));
        }
        $this->put(route('items.update', $item), [])->assertRedirect(route('login'));
        $this->delete(route('items.destroy', $item))->assertRedirect(route('login'));
        $this->signIn('operator');
        $this->get(route('items.index'))->assertForbidden();
        $this->get(route('items.edit', $item))->assertForbidden();
        $this->put(route('items.update', $item), [])->assertForbidden();
        $this->delete(route('items.destroy', $item))->assertForbidden();
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Consultar itens');
        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }
}