<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\Technician;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterAssetReturn
{
    public function handle(array $data, User $user): Asset
    {
        Gate::forUser($user)->authorize('recordReturn', Asset::class);

        return DB::transaction(function () use ($data, $user): Asset {
            $asset = Asset::query()
                ->lockForUpdate()
                ->find($data['asset_id']);

            if (! $asset || $asset->status !== 'in_use') {
                throw ValidationException::withMessages([
                    'asset_id' => 'Este equipamento não está mais em uso. Confira se a devolução já foi registrada.',
                ]);
            }

            $unit = Unit::query()
                ->lockForUpdate()
                ->find($data['unit_id']);

            if (! $unit || ! $unit->is_active) {
                throw ValidationException::withMessages([
                    'unit_id' => 'Selecione um estoque ativo.',
                ]);
            }

            $technician = Technician::query()
                ->lockForUpdate()
                ->find($data['technician_id']);

            if (! $technician || ! $technician->is_active) {
                throw ValidationException::withMessages([
                    'technician_id' => 'Selecione um técnico ativo.',
                ]);
            }

            $status = $data['return_status'];

            if (! in_array($status, ['available', 'awaiting_disposal'], true)) {
                throw ValidationException::withMessages([
                    'return_status' => 'Condição inválida.',
                ]);
            }

            $notes = trim($data['notes'] ?? '');

            if ($status === 'awaiting_disposal' && $notes === '') {
                throw ValidationException::withMessages([
                    'notes' => 'Informe o motivo do encaminhamento para descarte.',
                ]);
            }

            $exit = $asset->movements()
                ->where('type', 'exit')
                ->orderByDesc('id')
                ->first();

            $condition = $status === 'available'
                ? 'Disponível para uso'
                : 'Aguardando descarte';

            $history = "Condição na devolução: {$condition}.";

            if ($exit) {
                $history .= " Referente à saída #{$exit->id}.";
            }

            if ($notes !== '') {
                $history .= "\nObservação: {$notes}";
            }

            $asset->unit_id = $unit->id;
            $asset->status = $status;
            $asset->save();

            $now = now();

            DB::table('asset_movements')->insert([
                'asset_id' => $asset->id,
                'unit_id' => $unit->id,
                'user_id' => $user->id,
                'technician_id' => $technician->id,
                'batch_id' => (string) Str::uuid(),
                'type' => 'return',
                'replacement_required' => false,
                'notes' => $history,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $asset;
        });
    }
}