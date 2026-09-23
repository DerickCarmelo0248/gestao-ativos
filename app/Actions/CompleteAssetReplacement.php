<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\AssetReplacementRequest;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompleteAssetReplacement
{
    public function handle(
        AssetReplacementRequest $replacement,
        array $data,
        User $user
    ): Asset {
        try {
            return DB::transaction(function () use (
                $replacement,
                $data,
                $user
            ): Asset {
                $pending = AssetReplacementRequest::query()
                    ->lockForUpdate()
                    ->findOrFail($replacement->id);

                Gate::forUser($user)->authorize('complete', $pending);

                $exit = AssetMovement::query()
                    ->findOrFail($pending->asset_movement_id);

                if (
                    $exit->type !== 'exit'
                    || ! $exit->replacement_required
                ) {
                    throw ValidationException::withMessages([
                        'replacement' =>
                            'Esta pendência não possui uma saída válida para reposição.',
                    ]);
                }

                $originalAsset = Asset::query()
                    ->findOrFail($exit->asset_id);

                $item = Item::query()
                    ->lockForUpdate()
                    ->findOrFail($originalAsset->item_id);

                if (
                    ! $item->is_active
                    || $item->tracking_type !== 'individual'
                ) {
                    throw ValidationException::withMessages([
                        'replacement' =>
                            'O item precisa estar ativo e ter controle individual.',
                    ]);
                }

                $unit = Unit::query()
                    ->lockForUpdate()
                    ->findOrFail($exit->unit_id);

                if (! $unit->is_active) {
                    throw ValidationException::withMessages([
                        'replacement' =>
                            'A unidade de estoque de origem está inativa.',
                    ]);
                }

                if (
                    Asset::query()
                        ->where('patrimony', $data['patrimony'])
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'patrimony' =>
                            'Este patrimônio já está cadastrado.',
                    ]);
                }

                $asset = new Asset();
                $asset->item_id = $item->id;
                $asset->unit_id = $unit->id;
                $asset->patrimony = $data['patrimony'];
                $asset->serial_number = $data['serial_number'] ?? null;
                $asset->status = 'available';
                $asset->save();

                $now = now();

                $notes = sprintf(
                    'Reposição da pendência #%s, referente à saída #%s e ao patrimônio %s.',
                    $pending->id,
                    $exit->id,
                    $originalAsset->patrimony
                );

                if (! empty($data['notes'])) {
                    $notes .= "\nObservação: ".$data['notes'];
                }

                $movementId = DB::table('asset_movements')
                    ->insertGetId([
                        'asset_id' => $asset->id,
                        'user_id' => $user->id,
                        'unit_id' => $unit->id,
                        'batch_id' => (string) Str::uuid(),
                        'type' => 'replacement',
                        'notes' => $notes,
                        'replacement_required' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                $pending->status = 'completed';
                $pending->replacement_asset_id = $asset->id;
                $pending->replacement_movement_id = $movementId;
                $pending->completed_by = $user->id;
                $pending->completed_at = $now;
                $pending->save();

                return $asset;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // A restrição do banco também protege contra cadastros simultâneos.
            if (
                Asset::query()
                    ->where('patrimony', $data['patrimony'])
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'patrimony' =>
                        'Este patrimônio já está cadastrado. Informe outro.',
                ]);
            }

            throw $exception;
        }
    }
}