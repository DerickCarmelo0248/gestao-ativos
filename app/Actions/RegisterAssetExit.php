<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\AssetReplacementRequest;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterAssetExit
{
    /**
     * Recebe dados validados pelo StoreAssetExitRequest
     * e o usuário autenticado.
     */
    public function handle(array $data, User $user): Asset
    {
        Gate::forUser($user)->authorize('recordExit', Asset::class);

        return DB::transaction(function () use ($data, $user): Asset {
            $asset = Asset::query()
                ->lockForUpdate()
                ->find($data['asset_id']);

            if (! $asset || $asset->status !== 'available') {
                throw ValidationException::withMessages([
                    'asset_id' => 'Este equipamento não está mais disponível para saída.',
                ]);
            }

            $item = Item::query()
                ->lockForUpdate()
                ->findOrFail($asset->item_id);

            if (
                ! $item->is_active ||
                $item->tracking_type !== 'individual'
            ) {
                throw ValidationException::withMessages([
                    'asset_id' => 'O item do equipamento precisa estar ativo e ter controle individual.',
                ]);
            }

            $unit = Unit::query()
                ->lockForUpdate()
                ->findOrFail($asset->unit_id);

            if (! $unit->is_active) {
                throw ValidationException::withMessages([
                    'asset_id' => 'A unidade de estoque de origem está desativada.',
                ]);
            }

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

            $replacementRequired =
                (bool) $data['replacement_required'];

            $asset->status = 'in_use';
            $asset->save();

            $now = now();

            $movementId = DB::table('asset_movements')->insertGetId([
                'asset_id' => $asset->id,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'technician_id' => $data['technician_id'],
                'ticket_number' => $data['ticket_number'],
                'destination_establishment_id' =>
                    $data['destination_establishment_id'],
                'destination_sector_id' =>
                    $data['destination_sector_id'],
                'replacement_required' => $replacementRequired,
                'batch_id' => (string) Str::uuid(),
                'type' => 'exit',
                'notes' => $data['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($replacementRequired) {
                $replacement = new AssetReplacementRequest();

                $replacement->asset_movement_id = $movementId;

                $replacement->destination_establishment_id =
                    $data['destination_establishment_id'];

                $replacement->destination_sector_id =
                    $data['destination_sector_id'];

                $replacement->status = 'pending';
                $replacement->save();
            }

            return $asset;
        });
    }
}