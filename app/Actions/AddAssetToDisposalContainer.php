<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\DisposalContainer;
use App\Models\DisposalContainerItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AddAssetToDisposalContainer
{
    public function handle(
        DisposalContainer $container,
        array $data,
        User $user
    ): DisposalContainerItem {
        return DB::transaction(function () use (
            $container,
            $data,
            $user
        ): DisposalContainerItem {
            $container = DisposalContainer::query()
                ->lockForUpdate()
                ->findOrFail($container->id);

            Gate::forUser($user)->authorize('addItem', $container);

            $container->load('unit');

            if (
                ! $container->unit->is_active
                || ! in_array(
                    $container->unit->code,
                    ['VOT', 'RPR'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'container' =>
                        'A caçamba precisa pertencer a Votuporanga ou Rio Preto, com unidade ativa.',
                ]);
            }

            $asset = Asset::query()
                ->lockForUpdate()
                ->find($data['asset_id']);

            if (! $asset || $asset->status !== 'awaiting_disposal') {
                throw ValidationException::withMessages([
                    'asset_id' =>
                        'Selecione um equipamento aguardando descarte.',
                ]);
            }

            $alreadyIncluded = DisposalContainerItem::query()
                ->where('asset_id', $asset->id)
                ->exists();

            if ($alreadyIncluded) {
                throw ValidationException::withMessages([
                    'asset_id' =>
                        'Este equipamento já foi incluído em uma caçamba.',
                ]);
            }

            $reason = trim($data['reason'] ?? '');

            if ($reason === '' || mb_strlen($reason) > 2000) {
                throw ValidationException::withMessages([
                    'reason' =>
                        'Informe um motivo com até 2.000 caracteres.',
                ]);
            }

            $asset->load(['item.category', 'unit']);

            if ($asset->item->tracking_type !== 'individual') {
                throw ValidationException::withMessages([
                    'asset_id' =>
                        'O equipamento precisa ter controle individual.',
                ]);
            }

            $now = now();

            $containerItem = new DisposalContainerItem();
            $containerItem->disposal_container_id = $container->id;
            $containerItem->asset_id = $asset->id;
            $containerItem->item_id = $asset->item_id;
            $containerItem->origin_unit_id = $asset->unit_id;
            $containerItem->added_by = $user->id;
            $containerItem->quantity = 1;

            $containerItem->category_name = $asset->item->category->name;
            $containerItem->item_code = $asset->item->code;
            $containerItem->item_name = $asset->item->name;
            $containerItem->origin_unit_name = $asset->unit->name;
            $containerItem->patrimony = $asset->patrimony;
            $containerItem->serial_number = $asset->serial_number;

            $containerItem->reason = $reason;
            $containerItem->added_at = $now;
            $containerItem->save();

            $asset->status = 'in_container';
            $asset->save();

            DB::table('asset_movements')->insert([
                'asset_id' => $asset->id,
                'unit_id' => $container->unit_id,
                'user_id' => $user->id,
                'batch_id' => (string) Str::uuid(),
                'type' => 'container_entry',
                'replacement_required' => false,
                'notes' =>
                    "Incluído na caçamba #{$container->id}"
                    ." — {$container->unit->name}."
                    ."\nOrigem: {$containerItem->origin_unit_name}."
                    ."\nMotivo: {$reason}",
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $containerItem;
        });
    }
}