<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetEditingTest extends TestCase
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

    public function test_admin_edits_identity_with_audit_and_preserves_stock_fields(): void
    {
        $this->signIn('admin');
        $asset = $this->asset();
        $original = $asset->patrimony;
        $this->get(route('assets.show', $asset))->assertOk()->assertSee('Editar equipamento');
        $this->get(route('assets.edit', $asset))->assertOk();
        $data = ['patrimony' => ' '.$original.'-NEW ', 'serial_number' => ' SN123 ', 'notes' => ' Teste ',
            'status' => 'disposed', 'unit_id' => 99999, 'item_id' => 99999];
        $this->put(route('assets.update', $asset), $data)->assertSessionHasNoErrors()->assertRedirect(route('assets.show', $asset));
        $fresh = $asset->fresh();
        $this->assertSame($original.'-NEW', $fresh->patrimony);
        $this->assertSame('SN123', $fresh->serial_number);
        $this->assertSame('Teste', $fresh->notes);
        $this->assertSame('available', $fresh->status);
        $this->assertSame($asset->unit_id, $fresh->unit_id);
        $this->assertSame($asset->item_id, $fresh->item_id);
        $audit = DB::table('asset_edits')->where('asset_id', $asset->id)->first();
        $this->assertSame($original, json_decode($audit->before, true)['patrimony']);
        $this->assertSame('SN123', json_decode($audit->after, true)['serial_number']);
        $this->assertSame(auth()->id(), $audit->user_id);
        $this->get(route('assets.show', $asset))->assertOk()->assertSee('Histórico de alterações cadastrais')->assertSee($original)->assertSee('SN123');
        $this->put(route('assets.update', $asset), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('asset_edits')->where('asset_id', $asset->id)->count());
        $this->put(route('assets.update', $asset), ['patrimony' => $fresh->patrimony, 'serial_number' => '', 'notes' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($asset->fresh()->serial_number);
    }

    public function test_duplicate_and_invalid_values_do_not_change_asset(): void
    {
        $this->signIn('admin');
        $asset = $this->asset();
        $other = $this->asset();
        foreach ([['patrimony' => $other->patrimony], ['patrimony' => ' '], ['serial_number' => str_repeat('a', 101)], ['notes' => str_repeat('a', 2001)]] as $invalid) {
            $this->putJson(route('assets.update', $asset), array_replace(['patrimony' => $asset->patrimony, 'serial_number' => null, 'notes' => null], $invalid))
                ->assertUnprocessable()->assertJsonValidationErrors(array_keys($invalid));
        }
        $this->assertSame($asset->patrimony, $asset->fresh()->patrimony);
        $this->assertSame(0, DB::table('asset_edits')->where('asset_id', $asset->id)->count());
    }

    public function test_operator_guest_and_finalized_assets_cannot_be_edited(): void
    {
        $asset = $this->asset();
        $data = ['patrimony' => $asset->patrimony, 'serial_number' => 'SN', 'notes' => null];
        $this->get(route('assets.edit', $asset))->assertRedirect(route('login'));
        $this->put(route('assets.update', $asset), $data)->assertRedirect(route('login'));
        $this->signIn('operator');
        $this->get(route('assets.show', $asset))->assertOk()->assertDontSee('Editar equipamento');
        $this->get(route('assets.edit', $asset))->assertForbidden();
        $this->put(route('assets.update', $asset), $data)->assertForbidden();
        $this->signIn('admin');
        foreach (['in_container', 'disposed'] as $status) {
            $asset->status = $status;
            $asset->save();
            $this->get(route('assets.edit', $asset))->assertForbidden();
            $this->put(route('assets.update', $asset), $data)->assertForbidden();
        }
        $this->assertNull($asset->fresh()->serial_number);
    }
}