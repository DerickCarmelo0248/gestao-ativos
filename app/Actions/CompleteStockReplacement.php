<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReplacementRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CompleteStockReplacement
{
    public function handle(
        StockReplacementRequest $replacement,
        User $user
    ): void {
        DB::transaction(function () use ($replacement, $user): void {
            // Recarrega e bloqueia a pendência antes de verificar sua situação.
            $pending = StockReplacementRequest::query()
                ->lockForUpdate()
                ->findOrFail($replacement->id);

            Gate::forUser($user)->authorize('complete', $pending);

            if ($pending->replacement_movement_id !== null) {
                throw ValidationException::withMessages([
                    'replacement' => 'Esta pendência já possui uma reposição registrada.',
                ]);
            }

            $original = StockMovement::query()
                ->findOrFail($pending->stock_movement_id);

            $quantity = (int) $pending->quantity;

            if (
                $original->type !== 'exit' ||
                ! $original->replacement_required ||
                $quantity < 1 ||
                $quantity !== (int) $original->quantity
            ) {
                throw ValidationException::withMessages([
                    'replacement' => 'A pendência não corresponde a uma saída válida para reposição.',
                ]);
            }

            $item = Item::query()
                ->lockForUpdate()
                ->findOrFail($original->item_id);

            if (
                ! $item->is_active ||
                $item->tracking_type !== 'quantity'
            ) {
                throw ValidationException::withMessages([
                    'replacement' => 'O item precisa estar ativo e ter controle por quantidade.',
                ]);
            }

            $unit = Unit::query()
                ->lockForUpdate()
                ->findOrFail($original->unit_id);

            if (! $unit->is_active) {
                throw ValidationException::withMessages([
                    'replacement' => 'A unidade de estoque de origem está desativada.',
                ]);
            }

            $balance = StockBalance::query()
                ->where('item_id', $item->id)
                ->where('unit_id', $unit->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                throw ValidationException::withMessages([
                    'replacement' => 'O saldo vinculado à saída não foi encontrado. Verifique o histórico antes de continuar.',
                ]);
            }

            $newQuantity = $balance->quantity + $quantity;

            if ($newQuantity > 2147483647) {
                throw ValidationException::withMessages([
                    'replacement' => 'A reposição ultrapassa o limite de saldo suportado.',
                ]);
            }

            $balance->quantity = $newQuantity;
            $balance->save();

            $now = now();

            $movementId = DB::table('stock_movements')->insertGetId([
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'type' => 'replacement',
                'quantity' => $quantity,
                'replacement_required' => false,
                'notes' => "Recebimento da reposição #{$pending->id}, vinculada à saída #{$original->id}.",
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $pending->replacement_movement_id = $movementId;
            $pending->completed_by = $user->id;
            $pending->completed_at = $now;
            $pending->status = 'completed';
            $pending->save();
        });
    }
}