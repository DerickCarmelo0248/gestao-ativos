<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\DisposalContainer;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CloseDisposalContainer
{
    public function handle(
        DisposalContainer $container,
        User $user
    ): DisposalContainer {
        return DB::transaction(function () use (
            $container,
            $user
        ): DisposalContainer {
            $container = DisposalContainer::query()
                ->lockForUpdate()
                ->findOrFail($container->id);

            Gate::forUser($user)->authorize('close', $container);

            $unit = Unit::query()
                ->lockForUpdate()
                ->findOrFail($container->unit_id);

            if (
                ! $unit->is_active
                || ! in_array($unit->code, ['VOT', 'RPR'], true)
            ) {
                throw ValidationException::withMessages([
                    'container' =>
                        'A unidade da caçamba precisa estar ativa e ser Votuporanga ou Rio Preto.',
                ]);
            }

            $items = $container->items()
                ->orderBy('id')
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'container' =>
                        'Não é possível encerrar uma caçamba vazia.',
                ]);
            }

            $assetIds = $items
                ->pluck('asset_id')
                ->filter(fn ($id) => $id !== null)
                ->unique()
                ->values();

            $assets = Asset::query()
                ->whereIn('id', $assetIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if (
                $assets->count() !== $assetIds->count()
                || $assets->contains(
                    fn (Asset $asset) => $asset->status !== 'in_container'
                )
            ) {
                throw ValidationException::withMessages([
                    'container' =>
                        'Há um equipamento com situação incompatível. Confira os materiais antes de encerrar.',
                ]);
            }

            $now = now();
            $batchId = (string) Str::uuid();

            foreach ($assets as $asset) {
                $asset->status = 'disposed';
                $asset->save();

                DB::table('asset_movements')->insert([
                    'asset_id' => $asset->id,
                    'unit_id' => $unit->id,
                    'user_id' => $user->id,
                    'batch_id' => $batchId,
                    'type' => 'disposal',
                    'replacement_required' => false,
                    'notes' =>
                        "Descarte concluído na caçamba #{$container->id}"
                        ." — {$unit->name}.",
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $container->status = 'closed';
            $container->closed_by = $user->id;
            $container->closed_at = $now;
            $container->save();

            $next = new DisposalContainer();
            $next->unit_id = $unit->id;
            $next->status = 'open';
            $next->opened_by = $user->id;
            $next->opened_at = $now;
            $next->save();

            return $next;
        });
    }
}