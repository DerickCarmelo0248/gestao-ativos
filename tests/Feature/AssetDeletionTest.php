<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetDeletionTest extends TestCase
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

    private function movement($asset, string $type): void
    {
        DB::table('asset_movements')->insert([
            'asset_id' => $asset->id, 'unit_id' => $asset->unit_id,
            'user_id' => auth()->id(), 'batch_id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => $type, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_admin_removes_wrong_registration_and_archives_entry_and_edits(): void
    {
        $this->signIn('admin');
        $asset = $this->asset();
        $this->movement($asset, 'entry');
        DB::table('asset_edits')->insert([
            'asset_id' => $asset->id, 'user_id' => auth()->id(),
            'before' => '{"notes":null}', 'after' => '{"notes":"Correção"}', 'created_at' => now(),
        ]);
        $this->get(route('assets.index', ['item_id' => $asset->item_id]))->assertOk()->assertSee('Excluir equipamento');
        $this->delete(route('assets.destroy', $asset), ['reason' => 'Cadastro duplicado', 'confirmation' => $asset->patrimony])
            ->assertSessionHasNoErrors()->assertRedirect(route('assets.index', ['item_id' => $asset->item_id, 'unit_id' => $asset->unit_id]));
        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
        $this->assertDatabaseMissing('asset_movements', ['asset_id' => $asset->id]);
        $this->assertDatabaseMissing('asset_edits', ['asset_id' => $asset->id]);
        $audit = DB::table('asset_deletions')->where('original_asset_id', $asset->id)->first();
        $this->assertSame(auth()->id(), $audit->user_id);
        $this->assertSame('Cadastro duplicado', $audit->reason);
        $snapshot = json_decode($audit->snapshot, true);
        $this->assertSame($asset->patrimony, $snapshot['asset']['patrimony']);
        $this->assertCount(1, $snapshot['movements']);
        $this->assertCount(1, $snapshot['edits']);
        $this->get(route('assets.index', ['item_id' => $asset->item_id]))->assertOk()->assertSee('Nenhum equipamento encontrado');
        \App\Models\Asset::create($asset->only(['item_id', 'unit_id', 'patrimony']));
        $this->assertDatabaseHas('assets', ['patrimony' => $asset->patrimony]);
    }

    public function test_guest_and_operator_cannot_delete_or_see_action(): void
    {
        $asset = $this->asset();
        $data = ['reason' => 'Teste', 'confirmation' => $asset->patrimony];
        $this->delete(route('assets.destroy', $asset), $data)->assertRedirect(route('login'));
        $this->signIn('operator');
        $this->get(route('assets.show', $asset))->assertOk()->assertDontSee('Excluir equipamento');
        $this->get(route('assets.index', ['item_id' => $asset->item_id]))->assertOk()->assertDontSee('Excluir equipamento');
        $this->delete(route('assets.destroy', $asset), $data)->assertForbidden();
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }

    public function test_reason_confirmation_and_unused_status_are_required(): void
    {
        $this->signIn('admin');
        $asset = $this->asset();
        foreach ([
            ['reason' => '', 'confirmation' => $asset->patrimony],
            ['reason' => 'Teste', 'confirmation' => 'errado'],
        ] as $data) {
            $this->deleteJson(route('assets.destroy', $asset), $data)->assertUnprocessable();
        }
        foreach (['in_use', 'awaiting_disposal', 'in_container', 'disposed'] as $status) {
            $asset->status = $status;
            $asset->save();
            $this->deleteJson(route('assets.destroy', $asset), ['reason' => 'Teste', 'confirmation' => $asset->patrimony])
                ->assertUnprocessable()->assertJsonValidationErrors('asset');
        }
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
        $this->assertDatabaseMissing('asset_deletions', ['original_asset_id' => $asset->id]);
    }

    public function test_available_equipment_with_exit_or_return_history_is_preserved(): void
    {
        $this->signIn('admin');
        foreach (['exit', 'return', 'replacement', 'container_entry', 'disposal'] as $type) {
            $asset = $this->asset();
            $this->movement($asset, $type);
            $this->deleteJson(route('assets.destroy', $asset), ['reason' => 'Teste', 'confirmation' => $asset->patrimony])
                ->assertUnprocessable()->assertJsonValidationErrors('asset');
            $this->assertDatabaseHas('assets', ['id' => $asset->id]);
            $this->assertDatabaseHas('asset_movements', ['asset_id' => $asset->id, 'type' => $type]);
            $this->assertDatabaseMissing('asset_deletions', ['original_asset_id' => $asset->id]);
        }
    }
}