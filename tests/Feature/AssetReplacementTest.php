<?php

namespace Tests\Feature;

use App\Actions\CompleteAssetReplacement;
use App\Models\Asset;
use App\Models\AssetReplacementRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssetReplacementTest extends TestCase
{
    public function test_replacement_rejects_duplicate_patrimony_and_cannot_be_completed_twice(): void
    {
        // Confere o banco antes de executar qualquer gravação.
        $connection = DB::selectOne(
            'SELECT current_database() AS banco, current_user AS usuario'
        );

        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('gestao_ativos_test', $connection->banco);
        $this->assertSame('gestao_test', $connection->usuario);

        $this->artisan('migrate')->assertExitCode(0);

        DB::beginTransaction();

        try {
            $suffix = bin2hex(random_bytes(6));
            $now = now();

            $user = User::factory()->create();
            $user->role = 'operator';
            $user->save();

            $categoryId = DB::table('categories')->insertGetId([
                'name' => "Categoria {$suffix}",
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $itemId = DB::table('items')->insertGetId([
                'category_id' => $categoryId,
                'code' => "MON-{$suffix}",
                'name' => "Monitor {$suffix}",
                'tracking_type' => 'individual',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $unitId = DB::table('units')->insertGetId([
                'code' => "U-{$suffix}",
                'name' => "Estoque {$suffix}",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $establishmentId = DB::table('establishments')->insertGetId([
                'code' => (string) random_int(100000000, 999999999),
                'name' => "Estabelecimento {$suffix}",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sectorId = DB::table('sectors')->insertGetId([
                'name' => "Setor {$suffix}",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $technicianId = DB::table('technicians')->insertGetId([
                'name' => "Técnico {$suffix}",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $oldPatrimony = "ANTIGO-{$suffix}";
            $newPatrimony = "NOVO-{$suffix}";

            $originalAssetId = DB::table('assets')->insertGetId([
                'item_id' => $itemId,
                'unit_id' => $unitId,
                'patrimony' => $oldPatrimony,
                'status' => 'in_use',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $exitId = DB::table('asset_movements')->insertGetId([
                'asset_id' => $originalAssetId,
                'user_id' => $user->id,
                'unit_id' => $unitId,
                'batch_id' => (string) Str::uuid(),
                'type' => 'exit',
                'technician_id' => $technicianId,
                'ticket_number' => 'TESTE-REPOSICAO',
                'destination_establishment_id' => $establishmentId,
                'destination_sector_id' => $sectorId,
                'replacement_required' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $replacementId = DB::table('asset_replacement_requests')
                ->insertGetId([
                    'asset_movement_id' => $exitId,
                    'destination_establishment_id' => $establishmentId,
                    'destination_sector_id' => $sectorId,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            $replacement = AssetReplacementRequest::findOrFail(
                $replacementId
            );

            $action = app(CompleteAssetReplacement::class);

            $assetCount = DB::table('assets')->count();
            $movementCount = DB::table('asset_movements')->count();

            // 1. Um patrimônio existente não pode ser reutilizado.
            try {
                $action->handle($replacement, [
                    'patrimony' => $oldPatrimony,
                    'received' => '1',
                ], $user);

                $this->fail('Um patrimônio duplicado foi aceito.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey(
                    'patrimony',
                    $exception->errors()
                );
            }

            $replacement->refresh();

            $this->assertSame('pending', $replacement->status);
            $this->assertNull($replacement->replacement_asset_id);
            $this->assertNull($replacement->replacement_movement_id);
            $this->assertSame($assetCount, DB::table('assets')->count());
            $this->assertSame(
                $movementCount,
                DB::table('asset_movements')->count()
            );

            // 2. O novo equipamento entra no estoque de origem.
            $asset = $action->handle($replacement, [
                'patrimony' => $newPatrimony,
                'serial_number' => "SERIE-{$suffix}",
                'notes' => 'Equipamento recebido para reposição.',
                'received' => '1',
            ], $user);

            $replacement->refresh();

            $this->assertSame('available', $asset->status);
            $this->assertEquals($itemId, $asset->item_id);
            $this->assertEquals($unitId, $asset->unit_id);
            $this->assertSame($newPatrimony, $asset->patrimony);
            $this->assertSame("SERIE-{$suffix}", $asset->serial_number);

            $this->assertSame('completed', $replacement->status);
            $this->assertEquals(
                $asset->id,
                $replacement->replacement_asset_id
            );
            $this->assertEquals(
                $user->id,
                $replacement->completed_by
            );
            $this->assertNotNull($replacement->completed_at);

            $this->assertDatabaseHas('asset_movements', [
                'id' => $replacement->replacement_movement_id,
                'asset_id' => $asset->id,
                'unit_id' => $unitId,
                'user_id' => $user->id,
                'type' => 'replacement',
                'replacement_required' => false,
            ]);

            // O equipamento antigo e o responsável pelo custo permanecem.
            $this->assertSame(
                'in_use',
                Asset::findOrFail($originalAssetId)->status
            );

            $this->assertEquals(
                $establishmentId,
                $replacement->destination_establishment_id
            );
            $this->assertEquals(
                $sectorId,
                $replacement->destination_sector_id
            );

            $this->assertDatabaseHas('asset_movements', [
                'id' => $exitId,
                'asset_id' => $originalAssetId,
                'type' => 'exit',
                'destination_establishment_id' => $establishmentId,
                'destination_sector_id' => $sectorId,
            ]);

            // 3. A mesma pendência não pode gerar outro equipamento.
            try {
                $action->handle($replacement, [
                    'patrimony' => "EXTRA-{$suffix}",
                    'received' => '1',
                ], $user);

                $this->fail('A pendência foi concluída duas vezes.');
            } catch (AuthorizationException $exception) {
                $this->assertSame('completed', $replacement->fresh()->status);
            }

            $this->assertDatabaseMissing('assets', [
                'patrimony' => "EXTRA-{$suffix}",
            ]);

            $this->assertSame(
                $assetCount + 1,
                DB::table('assets')->count()
            );
            $this->assertSame(
                $movementCount + 1,
                DB::table('asset_movements')->count()
            );
        } finally {
            DB::rollBack();
        }
    }
}