<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\DisposalContainer;
use App\Models\DisposalContainerItem;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AddExternalMaterialToDisposalContainer
{
    public function handle(
        DisposalContainer $container,
        array $data,
        User $user
    ): DisposalContainerItem {
        Gate::forUser($user)->authorize('addItem', $container);

        try {
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
                        'container' => 'A unidade da caçamba está indisponível.',
                    ]);
                }

                $data = Validator::make($data, [
                    'item_id' => ['required', 'integer'],
                    'origin_unit_id' => ['required', 'integer'],
                    'reason' => ['required', 'string', 'max:2000'],
                ], [
                    'required' => 'O campo :attribute é obrigatório.',
                    'integer' => 'Selecione um valor válido para :attribute.',
                    'reason.max' => 'O motivo deve ter até 2.000 caracteres.',
                ])->validate() + $data;

                $item = Item::query()
                    ->lockForUpdate()
                    ->find($data['item_id']);

                if (! $item || ! $item->is_active) {
                    throw ValidationException::withMessages([
                        'item_id' => 'Selecione um item ativo do catálogo.',
                    ]);
                }

                $origin = Unit::query()
                    ->lockForUpdate()
                    ->find($data['origin_unit_id']);

                if (! $origin || ! $origin->is_active) {
                    throw ValidationException::withMessages([
                        'origin_unit_id' => 'Selecione uma unidade de origem ativa.',
                    ]);
                }

                $individual = $item->tracking_type === 'individual';

                if (! in_array(
                    $item->tracking_type,
                    ['individual', 'quantity'],
                    true
                )) {
                    throw ValidationException::withMessages([
                        'item_id' => 'O tipo de controle do item é inválido.',
                    ]);
                }

                $details = Validator::make($data, $individual ? [
                    'patrimony' => ['required', 'string', 'max:50'],
                    'serial_number' => ['nullable', 'string', 'max:100'],
                ] : [
                    'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
                ], [
                    'patrimony.required' => 'Informe o patrimônio.',
                    'patrimony.max' => 'O patrimônio deve ter até 50 caracteres.',
                    'serial_number.max' => 'O número de série deve ter até 100 caracteres.',
                    'quantity.required' => 'Informe a quantidade.',
                    'quantity.integer' => 'A quantidade deve ser um número inteiro.',
                    'quantity.min' => 'A quantidade mínima é 1.',
                    'quantity.max' => 'Registre até 10.000 unidades por inclusão.',
                ])->validate();

                $reason = trim($data['reason']);

                if ($reason === '') {
                    throw ValidationException::withMessages([
                        'reason' => 'Informe o motivo do descarte.',
                    ]);
                }

                $asset = null;

                if ($individual) {
                    $patrimony = trim($details['patrimony']);

                    if ($patrimony === '') {
                        throw ValidationException::withMessages([
                            'patrimony' => 'Informe o patrimônio.',
                        ]);
                    }

                    if (Asset::where('patrimony', $patrimony)->exists()) {
                        throw ValidationException::withMessages([
                            'patrimony' =>
                                'Este patrimônio já existe. Use o fluxo de equipamento cadastrado.',
                        ]);
                    }

                    $asset = new Asset();
                    $asset->item_id = $item->id;
                    $asset->unit_id = $origin->id;
                    $asset->patrimony = $patrimony;
                    $asset->serial_number =
                        trim($details['serial_number'] ?? '') ?: null;
                    $asset->status = 'in_container';
                    $asset->save();
                }

                $item->load('category');
                $now = now();

                $record = new DisposalContainerItem();
                $record->disposal_container_id = $container->id;
                $record->asset_id = $asset?->id;
                $record->item_id = $item->id;
                $record->origin_unit_id = $origin->id;
                $record->added_by = $user->id;
                $record->quantity = $individual
                    ? 1
                    : (int) $details['quantity'];

                $record->registration_source = 'external';
                $record->category_name = $item->category->name;
                $record->item_code = $item->code;
                $record->item_name = $item->name;
                $record->origin_unit_name = $origin->name;
                $record->patrimony = $asset?->patrimony;
                $record->serial_number = $asset?->serial_number;
                $record->reason = $reason;
                $record->added_at = $now;
                $record->save();

                if ($asset) {
                    DB::table('asset_movements')->insert([
                        'asset_id' => $asset->id,
                        'unit_id' => $container->unit_id,
                        'user_id' => $user->id,
                        'batch_id' => (string) Str::uuid(),
                        'type' => 'container_entry',
                        'replacement_required' => false,
                        'notes' =>
                            "Cadastro direto para descarte na caçamba #{$container->id}."
                            ."\nOrigem: {$origin->name}."
                            ."\nMotivo: {$reason}",
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                return $record;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $patrimony = $data['patrimony'] ?? null;

            if (
                is_string($patrimony)
                && Asset::where('patrimony', trim($patrimony))->exists()
            ) {
                throw ValidationException::withMessages([
                    'patrimony' =>
                        'Este patrimônio já foi cadastrado. Use o fluxo de equipamento cadastrado.',
                ]);
            }

            throw $exception;
        }
    }
}