<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockReplacementRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RegisterStockExit
{
    /**
     * Recebe dados validados pelo StoreStockExitRequest
     * e o usuário autenticado.
     */
    public function handle(array $data, User $user): StockBalance
    {
        Gate::forUser($user)->authorize(
            'recordExit',
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
                    'unit_id' => 'Selecione uma unidade de estoque ativa.',
                ]);
            }

            // Reconfere os cadastros usados na saída.
            foreach ([
                'technician_id' => 'technicians',
                'destination_establishment_id' => 'establishments',
                'destination_sector_id' => 'sectors',
            ] as $field => $table) {
                $record = DB::table($table)
                    ->where('id', $data[$field])
                    ->lockForUpdate()
                    ->first();

                if (! $record || ! $record->is_active) {
                    throw ValidationException::withMessages([
                        $field => 'O cadastro selecionado não está mais disponível.',
                    ]);
                }
            }

            $quantity = (int) $data['quantity'];

            if ($quantity < 1 || $quantity > 10000) {
                throw ValidationException::withMessages([
                    'quantity' => 'Informe uma quantidade entre 1 e 10.000.',
                ]);
            }

            $balance = StockBalance::query()
                ->where('item_id', $item->id)
                ->where('unit_id', $unit->id)
                ->lockForUpdate()
                ->first();

            if (! $balance || $balance->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Saldo insuficiente para essa saída.',
                ]);
            }

            $replacementRequired =
                (bool) $data['replacement_required'];

            $balance->quantity -= $quantity;
            $balance->save();

            $now = now();

            $movementId = DB::table('stock_movements')->insertGetId([
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'technician_id' => $data['technician_id'],
                'ticket_number' => $data['ticket_number'],
                'destination_establishment_id' =>
                    $data['destination_establishment_id'],
                'destination_sector_id' =>
                    $data['destination_sector_id'],
                'type' => 'exit',
                'quantity' => $quantity,
                'replacement_required' => $replacementRequired,
                'notes' => $data['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($replacementRequired) {
                $replacement = new StockReplacementRequest();

                $replacement->stock_movement_id = $movementId;
                $replacement->quantity = $quantity;

                $replacement->destination_establishment_id =
                    $data['destination_establishment_id'];

                $replacement->destination_sector_id =
                    $data['destination_sector_id'];

                $replacement->reason = 'Reposição solicitada na saída';
                $replacement->status = 'pending';
                $replacement->save();
            }

            return $balance;
        });
    }
}