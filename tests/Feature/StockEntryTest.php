<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockEntryTest extends TestCase
{
    public function test_entries_accumulate_and_keep_units_separate(): void
    {
        $connection = DB::selectOne(
            'SELECT current_database() AS banco, current_user AS usuario'
        );

        // Confere o destino antes de qualquer alteração.
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
                'name' => "Mouses {$suffix}",
            ]);

            $item = Item::create([
                'category_id' => $category->id,
                'code' => "MOUSE-{$suffix}",
                'name' => 'Mouse de teste',
                'tracking_type' => 'quantity',
            ]);

            $firstUnit = Unit::create([
                'code' => "A-{$suffix}",
                'name' => 'Unidade A',
            ]);

            $secondUnit = Unit::create([
                'code' => "B-{$suffix}",
                'name' => 'Unidade B',
            ]);

            $this->actingAs($user);

            $entries = [
                [$firstUnit->id, 20],
                [$firstUnit->id, 5],
                [$secondUnit->id, 8],
            ];

            foreach ($entries as [$unitId, $quantity]) {
                $this->post(route('stock-entries.store'), [
                    'item_id' => $item->id,
                    'unit_id' => $unitId,
                    'quantity' => $quantity,
                    'notes' => 'Entrada de teste',
                ])
                    ->assertSessionHasNoErrors()
                    ->assertRedirect(route('stock-entries.create'));
            }

            $this->assertDatabaseHas('stock_balances', [
                'item_id' => $item->id,
                'unit_id' => $firstUnit->id,
                'quantity' => 25,
            ]);

            $this->assertDatabaseHas('stock_balances', [
                'item_id' => $item->id,
                'unit_id' => $secondUnit->id,
                'quantity' => 8,
            ]);

            $this->assertSame(
                2,
                StockBalance::where('item_id', $item->id)->count()
            );

            $movements = DB::table('stock_movements')
                ->where('item_id', $item->id)
                ->orderBy('id')
                ->get();

            $this->assertCount(3, $movements);

            $this->assertSame(
                [20, 5, 8],
                $movements->pluck('quantity')->all()
            );

            $this->assertSame(
                [$firstUnit->id, $firstUnit->id, $secondUnit->id],
                $movements->pluck('unit_id')->all()
            );

            foreach ($movements as $movement) {
                $this->assertSame($user->id, $movement->user_id);
                $this->assertSame('entry', $movement->type);
                $this->assertSame('Entrada de teste', $movement->notes);
            }
        } finally {
            DB::rollBack();
        }
    }
}