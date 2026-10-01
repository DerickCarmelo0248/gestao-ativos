<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelInventoryTest extends TestCase
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

    public function test_model_totals_and_drill_down_respect_unit_and_status(): void
    {
        $this->signIn('operator');
        $category = $this->category();
        $one = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Unidade 1']);
        $two = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Unidade 2']);
        $item = Item::create(['category_id' => $category->id, 'name' => 'Modelo teste', 'code' => 'MOD-'.bin2hex(random_bytes(5)), 'tracking_type' => 'individual']);
        $quantity = Item::create(['category_id' => $category->id, 'name' => 'Mouse', 'code' => 'MOU-'.bin2hex(random_bytes(5)), 'tracking_type' => 'quantity']);
        foreach (['available', 'in_use', 'awaiting_disposal', 'in_container', 'disposed'] as $status) {
            $asset = \App\Models\Asset::create(['item_id' => $item->id, 'unit_id' => $one->id, 'patrimony' => bin2hex(random_bytes(8))]);
            $asset->status = $status;
            $asset->save();
        }
        $other = \App\Models\Asset::create(['item_id' => $item->id, 'unit_id' => $two->id, 'patrimony' => bin2hex(random_bytes(8))]);
        DB::table('stock_balances')->insert([
            ['item_id' => $quantity->id, 'unit_id' => $one->id, 'quantity' => 7],
            ['item_id' => $quantity->id, 'unit_id' => $two->id, 'quantity' => 9],
        ]);
        $response = $this->get(route('assets.index', ['search' => $item->code, 'unit_id' => $one->id]))->assertOk();
        $row = $response->viewData('models')->first();
        $this->assertSame(1, (int) $row->available);
        $this->assertSame(1, (int) $row->in_use);
        $this->assertSame(2, (int) $row->disposal);
        $this->assertSame(1, (int) $row->disposed);
        $response = $this->get(route('assets.index', ['search' => $quantity->code]))->assertOk();
        $this->assertSame(16, (int) $response->viewData('models')->first()->available);
        $response = $this->get(route('stock-balances.index', ['item_id' => $quantity->id, 'unit_id' => $one->id]))->assertOk();
        $this->assertSame(1, $response->viewData('balances')->total());
        $this->assertSame(7, $response->viewData('balances')->first()->quantity);
        $response = $this->get(route('assets.index', ['item_id' => $item->id, 'unit_id' => $one->id]))->assertOk()->assertDontSee($other->patrimony);
        $this->assertSame(5, $response->viewData('assets')->total());
        $response = $this->get(route('assets.index', ['patrimony' => $other->patrimony]))->assertOk()->assertSee($other->patrimony);
        $this->assertSame(1, $response->viewData('assets')->total());
    }

    public function test_guest_is_redirected_and_empty_models_are_visible(): void
    {
        $this->get(route('assets.index'))->assertRedirect(route('login'));
        $this->signIn('admin');
        $item = Item::create(['category_id' => $this->category()->id, 'name' => 'Sem estoque', 'code' => 'EMPTY-'.bin2hex(random_bytes(4)), 'tracking_type' => 'individual']);
        $response = $this->get(route('assets.index', ['search' => $item->code]))->assertOk()->assertSee('Sem estoque');
        $this->assertSame(0, (int) $response->viewData('models')->first()->available);
    }
    public function test_stock_can_be_filtered_by_tracking_type(): void
    {
        $this->signIn('operator');
        $prefix = 'FILTER-'.bin2hex(random_bytes(4));
        foreach (['individual', 'quantity'] as $type) {
            Item::create(['category_id' => $this->category()->id, 'name' => $prefix.' '.$type, 'code' => $prefix.'-'.$type, 'tracking_type' => $type]);
        }
        $this->get(route('assets.index', ['search' => $prefix]))->assertOk()
            ->assertViewHas('models', fn ($rows) => $rows->total() === 2);
        foreach (['individual', 'quantity'] as $type) {
            $this->get(route('assets.index', ['search' => $prefix, 'tracking_type' => $type]))->assertOk()
                ->assertViewHas('models', fn ($rows) => $rows->total() === 1 && $rows->first()->tracking_type === $type);
        }
        $this->getJson(route('assets.index', ['tracking_type' => 'invalid']))->assertUnprocessable();
    }}