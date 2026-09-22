<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RegisterStockEntry
{
    /**
     * Recebe dados validados e o usuário autenticado.
     */
    public function handle(array $data, User $user): StockBalance
    {
        Gate::forUser($user)->authorize(
            'recordEntry',
            StockBalance::class
        );

        return DB::transaction(function () use ($data, $user) {
            $item = Item::query()
                ->lockForUpdate()
                ->find($data['item_id']);

            if (
                ! $item ||
                ! $item->is_active ||
                $item->tracking_type !== 'quantity'
            ) {
                throw ValidationException::withMessages([
                    'item_id' => 'Selecione um item ativo com controle por quantidade.',
                ]);
            }

            $unit = Unit::query()
                ->lockForUpdate()
                ->find($data['unit_id']);

            if (! $unit || ! $unit->is_active) {
                throw ValidationException::withMessages([
                    'unit_id' => 'Selecione uma unidade ativa.',
                ]);
            }

            $balance = StockBalance::query()
                ->where('item_id', $item->id)
                ->where('unit_id', $unit->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = new StockBalance();
                $balance->item_id = $item->id;
                $balance->unit_id = $unit->id;
                $balance->quantity = 0;
            }

            $quantity = (int) $data['quantity'];

            if ($quantity < 1 || $quantity > 10000) {
                throw ValidationException::withMessages([
                    'quantity' => 'Informe uma quantidade entre 1 e 10.000.',
                ]);
            }

            $newQuantity = $balance->quantity + $quantity;

            if ($newQuantity > 2147483647) {
                throw ValidationException::withMessages([
                    'quantity' => 'A entrada ultrapassa o limite de saldo suportado.',
                ]);
            }

            $balance->quantity = $newQuantity;
            $balance->save();

            DB::table('stock_movements')->insert([
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'type' => 'entry',
                'quantity' => $quantity,
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
                'destination_establishment_id' => $data['destination_establishment_id'] ?? null,
                'destination_sector_id' => $data['destination_sector_id'] ?? null,
            ]);

            return $balance;
        });
    }
}