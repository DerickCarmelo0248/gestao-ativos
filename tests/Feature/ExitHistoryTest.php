<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExitHistoryTest extends TestCase
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

    public function test_history_combines_only_exits_with_filters_and_local_dates(): void
    {
        $this->signIn('operator');
        $item = Item::create(['category_id' => $this->category()->id, 'name' => 'Teste histórico', 'code' => 'HIST-'.bin2hex(random_bytes(5)), 'tracking_type' => 'individual']);
        $unit = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Unidade teste']);
        $asset = \App\Models\Asset::create(['item_id' => $item->id, 'unit_id' => $unit->id, 'patrimony' => bin2hex(random_bytes(8))]);
        $base = ['unit_id' => $unit->id, 'user_id' => auth()->id(), 'created_at' => '2026-09-29 03:00:00+00', 'updated_at' => now()];
        DB::table('asset_movements')->insert($base + ['asset_id' => $asset->id, 'batch_id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'exit']);
        DB::table('stock_movements')->insert($base + ['item_id' => $item->id, 'quantity' => 4, 'type' => 'exit']);
        DB::table('stock_movements')->insert($base + ['item_id' => $item->id, 'quantity' => 9, 'type' => 'entry']);
        $filters = ['unit_id' => $unit->id, 'from' => '2026-09-29', 'to' => '2026-09-29'];
        $response = $this->get(route('movements.exit-history', $filters))->assertOk()->assertSee($asset->patrimony)->assertSee($item->name);
        $this->assertSame(2, $response->viewData('exits')->total());
        $this->get(route('movements.exit-history', $filters + ['kind' => 'stock']))->assertOk()->assertViewHas('exits', fn ($rows) => $rows->total() === 1 && $rows->first()->kind === 'stock');
        $this->get(route('movements.exit-history', $filters + ['search' => $asset->patrimony]))->assertOk()->assertViewHas('exits', fn ($rows) => $rows->total() === 1 && $rows->first()->kind === 'asset');
        $this->get(route('movements.exit-history', ['unit_id' => $unit->id, 'to' => '2026-09-28']))->assertOk()->assertViewHas('exits', fn ($rows) => $rows->total() === 0);
        $this->getJson(route('movements.exit-history', ['from' => '2026-09-30', 'to' => '2026-09-29']))->assertUnprocessable();
    }

    public function test_guest_cannot_read_history(): void
    {
        $this->get(route('movements.exit-history'))->assertRedirect(route('login'));
    }
}