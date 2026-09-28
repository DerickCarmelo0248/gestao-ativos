<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnifiedMovementTest extends TestCase
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

    public function test_forms_follow_item_type_and_exit_filters_equipment(): void
    {
        $this->signIn('operator');
        $category = $this->category();
        $unit = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Unidade teste']);
        $individual = Item::create(['category_id' => $category->id, 'code' => 'IND-'.bin2hex(random_bytes(4)), 'name' => 'Individual', 'tracking_type' => 'individual']);
        $quantity = Item::create(['category_id' => $category->id, 'code' => 'QTY-'.bin2hex(random_bytes(4)), 'name' => 'Quantidade', 'tracking_type' => 'quantity']);
        $asset = \App\Models\Asset::create(['item_id' => $individual->id, 'unit_id' => $unit->id, 'patrimony' => 'PAT-'.bin2hex(random_bytes(6))]);
        foreach (['entry', 'exit'] as $movement) {
            $this->get(route('movements.'.$movement))->assertOk()->assertSee('Selecione o item');
            foreach ([$individual, $quantity] as $item) {
                $response = $this->get(route('movements.'.$movement, ['item_id' => $item->id]))->assertOk();
                $this->assertSame($item->id, $response->viewData('selectedItem')->id);
                if ($movement === 'exit' && $item->tracking_type === 'individual') {
                    $this->assertSame([$asset->id], $response->viewData('assets')->pluck('id')->all());
                } else {
                    $this->assertSame([$item->id], $response->viewData('items')->pluck('id')->all());
                }
            }
        }
        $asset->status = 'in_use';
        $asset->save();
        $this->get(route('movements.exit', ['item_id' => $individual->id]))->assertOk()
            ->assertViewHas('assets', fn ($assets) => $assets->isEmpty());
        $individual->is_active = false;
        $individual->save();
        $this->get(route('movements.entry', ['item_id' => $individual->id]))->assertNotFound();
        $this->getJson(route('movements.entry', ['item_id' => 'invalid']))->assertUnprocessable();
    }

    public function test_guest_cannot_access_unified_movements(): void
    {
        $this->get(route('movements.entry'))->assertRedirect(route('login'));
        $this->get(route('movements.exit'))->assertRedirect(route('login'));
    }
}