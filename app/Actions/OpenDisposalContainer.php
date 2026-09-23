<?php

namespace App\Actions;

use App\Models\DisposalContainer;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OpenDisposalContainer
{
    public function handle(int $unitId, User $user): DisposalContainer
    {
        Gate::forUser($user)->authorize(
            'create',
            DisposalContainer::class
        );

        return DB::transaction(function () use (
            $unitId,
            $user
        ): DisposalContainer {
            $unit = Unit::query()
                ->lockForUpdate()
                ->find($unitId);

            if (
                ! $unit
                || ! $unit->is_active
                || ! in_array($unit->code, ['VOT', 'RPR'], true)
            ) {
                throw ValidationException::withMessages([
                    'unit_id' =>
                        'Selecione Votuporanga ou Rio Preto, com cadastro ativo.',
                ]);
            }

            $existing = DisposalContainer::query()
                ->where('unit_id', $unit->id)
                ->where('status', 'open')
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'unit_id' =>
                        "Esta unidade já possui a caçamba #{$existing->id} aberta.",
                ]);
            }

            $container = new DisposalContainer();
            $container->unit_id = $unit->id;
            $container->status = 'open';
            $container->opened_by = $user->id;
            $container->opened_at = now();
            $container->save();

            return $container;
        });
    }
}