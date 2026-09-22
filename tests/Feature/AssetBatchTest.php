<?php

namespace Tests\Feature;

use App\Actions\RegisterAssetBatch;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssetBatchTest extends TestCase
{
    public function test_batch_records_history_and_rolls_back_on_conflict(): void
    {
        $connection = DB::selectOne(
            'SELECT current_database() AS banco, current_user AS usuario'
        );

        $this->assertSame('testing', app()->environment());
        $this->assertSame('gestao_ativos_test', $connection->banco);
        $this->assertSame('gestao_test', $connection->usuario);

        $this->artisan('migrate')->assertExitCode(0);

        DB::beginTransaction();

        try {
            $suffix = bin2hex(random_bytes(6));

            $user = User::factory()->create();
            $user->role = 'admin';
            $user->save();

            $category = Category::create([
                'name' => "Monitores {$suffix}",
            ]);

            $item = Item::create([
                'category_id' => $category->id,
                'code' => "MON-{$suffix}",
                'name' => 'Monitor para teste',
                'tracking_type' => 'individual',
            ]);

            $unit = Unit::create([
                'code' => "T-{$suffix}",
                'name' => 'Unidade de teste',
            ]);

            $start = random_int(100000000, 900000000);

            $data = [
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'patrimony_start' => (string) $start,
                'patrimony_end' => (string) ($start + 12),
            ];

            $action = app(RegisterAssetBatch::class);

            $this->assertSame(13, $action->handle($data, $user));

            $assetIds = Asset::where('item_id', $item->id)->pluck('id');

            $this->assertCount(13, $assetIds);

            $movements = DB::table('asset_movements')
                ->whereIn('asset_id', $assetIds)
                ->get();

            $this->assertCount(13, $movements);
            $this->assertCount(1, $movements->pluck('batch_id')->unique());

            foreach ($movements as $movement) {
                $this->assertSame($user->id, $movement->user_id);
                $this->assertSame($unit->id, $movement->unit_id);
                $this->assertSame('entry', $movement->type);
            }

            $movementsBefore = DB::table('asset_movements')->count();

            // O primeiro patrimônio é novo; o segundo já existe.
            $data['patrimony_start'] = (string) ($start - 1);
            $data['patrimony_end'] = (string) $start;

            try {
                $action->handle($data, $user);

                $this->fail('O lote com patrimônio duplicado foi aceito.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey(
                    'patrimony_start',
                    $exception->errors()
                );
            }

            $this->assertDatabaseMissing('assets', [
                'patrimony' => (string) ($start - 1),
            ]);

            $this->assertSame(
                13,
                Asset::where('item_id', $item->id)->count()
            );

            $this->assertSame(
                $movementsBefore,
                DB::table('asset_movements')->count()
            );
        } finally {
            DB::rollBack();
        }
    }

    public function test_operator_can_submit_only_valid_intervals(): void
{
    $connection = DB::selectOne(
        'SELECT current_database() AS banco, current_user AS usuario'
    );

    $this->assertSame('testing', app()->environment());
    $this->assertSame('gestao_ativos_test', $connection->banco);
    $this->assertSame('gestao_test', $connection->usuario);

    $this->artisan('migrate')->assertExitCode(0);

    DB::beginTransaction();

    try {
        $suffix = bin2hex(random_bytes(6));

        $user = User::factory()->create();
        $user->role = 'operator';
        $user->save();

        $category = Category::create([
            'name' => "Categoria {$suffix}",
        ]);

        $item = Item::create([
            'category_id' => $category->id,
            'code' => "TEST-{$suffix}",
            'name' => 'Monitor de teste',
            'tracking_type' => 'individual',
        ]);

        $unit = Unit::create([
            'code' => "T-{$suffix}",
            'name' => 'Unidade de teste',
        ]);

        $this->actingAs($user);

        $data = [
            'item_id' => $item->id,
            'unit_id' => $unit->id,
            'patrimony_start' => '200',
            'patrimony_end' => '100',
        ];

        // Intervalo invertido.
        $this->postJson(route('assets.batch.store'), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('patrimony_end');

        // Intervalo com 501 equipamentos.
        $data['patrimony_start'] = '1000';
        $data['patrimony_end'] = '1500';

        $this->postJson(route('assets.batch.store'), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('patrimony_end');

        $this->assertSame(
            0,
            Asset::where('item_id', $item->id)->count()
        );

        // Entrada válida de três equipamentos.
        $start = random_int(100000000, 900000000);

        $data['patrimony_start'] = (string) $start;
        $data['patrimony_end'] = (string) ($start + 2);

        $this->post(route('assets.batch.store'), $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('assets.batch.create'))
            ->assertSessionHas(
                'status',
                '3 equipamentos cadastrados com sucesso.'
            );

        $assetIds = Asset::where('item_id', $item->id)->pluck('id');

        $this->assertCount(3, $assetIds);

        $this->assertSame(
            3,
            DB::table('asset_movements')
                ->whereIn('asset_id', $assetIds)
                ->where('user_id', $user->id)
                ->where('type', 'entry')
                ->count()
        );
    } finally {
        DB::rollBack();
    }
}

}