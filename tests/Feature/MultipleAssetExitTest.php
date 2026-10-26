<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultipleAssetExitTest extends TestCase
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

    private function data(array $ids): array
    {
        $suffix = bin2hex(random_bytes(6));
        $common = ['is_active' => true, 'created_at' => now(), 'updated_at' => now()];
        return [
            'asset_ids' => $ids, 'ticket_number' => 'CH-123', 'replacement_required' => 1,
            'technician_id' => DB::table('technicians')->insertGetId($common + ['name' => 'Tec '.$suffix]),
            'destination_sector_id' => DB::table('sectors')->insertGetId($common + ['name' => 'Setor '.$suffix]),
            'destination_establishment_id' => DB::table('establishments')->insertGetId($common + ['name' => 'Destino '.$suffix, 'code' => (string) random_int(10000000, 99999999)]),
        ];
    }

    public function test_operator_records_multiple_exits_and_individual_replacements(): void
    {
        $this->signIn('operator');
        $one = $this->asset();
        $two = $this->asset();
        $data = $this->data([$one->id, $two->id]);
        $this->get(route('asset-exits.create'))->assertOk()->assertSee('asset_ids[]', false)->assertSee('Selecionar resultados');
        $this->post(route('asset-exits.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertStringContainsString($one->patrimony, session('email_draft.body'));
        $this->assertStringContainsString($two->patrimony, session('email_draft.body'));
        $this->assertStringContainsString('CH-123', session('email_draft.body'));
        $this->assertStringContainsString('R$ [preencher]', session('email_draft.body'));
        $this->assertSame('in_use', $one->fresh()->status);
        $this->assertSame('in_use', $two->fresh()->status);
        $movements = DB::table('asset_movements')->whereIn('asset_id', [$one->id, $two->id])->get();
        $this->assertCount(2, $movements);
        $this->assertCount(1, $movements->pluck('batch_id')->unique());
        foreach ($movements as $movement) {
            $this->assertSame('CH-123', $movement->ticket_number);
            $this->assertSame($data['technician_id'], $movement->technician_id);
            $this->assertSame($data['destination_establishment_id'], $movement->destination_establishment_id);
            $this->assertDatabaseHas('asset_replacement_requests', ['asset_movement_id' => $movement->id, 'status' => 'pending']);
        }
    }

    public function test_invoice_groups_same_model_and_keeps_other_models_separate(): void
    {
        $this->signIn('operator');
        $one = $this->asset();
        $two = $this->asset();
        $two->item_id = $one->item_id;
        $two->save();
        $three = $this->asset();
        $data = $this->data([$one->id, $two->id, $three->id]);
        $this->post(route('asset-exits.store'), $data)->assertSessionHasNoErrors();
        $body = session('email_draft.body');
        $this->assertStringContainsString("Patrimônio: {$one->patrimony}, {$two->patrimony}\nValor: R$ [preencher]", $body);
        $this->assertStringContainsString("Patrimônio: {$three->patrimony}\nValor: R$ [preencher]", $body);
        $this->assertSame(2, substr_count($body, 'Equipamento: '));
        $this->assertSame(2, substr_count($body, 'Valor: R$ [preencher]'));
    }

    public function test_invalid_selection_and_guest_are_rejected(): void
    {
        $asset = $this->asset();
        $data = $this->data([$asset->id]);
        $this->post(route('asset-exits.store'), $data)->assertRedirect(route('login'));
        $this->signIn('operator');
        foreach ([[], [$asset->id, $asset->id], [999999999]] as $ids) {
            $this->postJson(route('asset-exits.store'), array_replace($data, ['asset_ids' => $ids]))->assertUnprocessable();
        }
        $this->assertSame('available', $asset->fresh()->status);
        $this->assertDatabaseMissing('asset_movements', ['asset_id' => $asset->id]);
    }

    public function test_failure_on_later_asset_rolls_back_entire_batch(): void
    {
        $this->signIn('admin');
        $one = $this->asset();
        $two = $this->asset();
        $two->status = 'in_use';
        $two->save();
        $data = $this->data([$one->id, $two->id]);
        $before = DB::table('asset_replacement_requests')->count();
        try {
            app(\App\Actions\RegisterAssetExit::class)->handleMany($data, auth()->user());
            $this->fail('Batch should have failed');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('asset_id', $exception->errors());
        }
        $this->assertSame('available', $one->fresh()->status);
        $this->assertDatabaseMissing('asset_movements', ['asset_id' => $one->id]);
        $this->assertSame($before, DB::table('asset_replacement_requests')->count());
    }
}