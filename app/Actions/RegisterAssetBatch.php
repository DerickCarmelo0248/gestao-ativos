<?php

namespace App\Actions;

use App\Models\Asset;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterAssetBatch
{
    /**
     * Recebe dados validados e o usuário autenticado.
     */
    public function handle(array $data, User $user): int
    {
        Gate::forUser($user)->authorize('create', Asset::class);

        try {
            return DB::transaction(function () use ($data, $user): int {
                $item = Item::query()
                    ->lockForUpdate()
                    ->find($data['item_id']);

                if (
                    ! $item ||
                    ! $item->is_active ||
                    $item->tracking_type !== 'individual'
                ) {
                    throw ValidationException::withMessages([
                        'item_id' => 'Selecione um item ativo com controle individual.',
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

                $start = $data['patrimony_start'];
                $end = $data['patrimony_end'];

                $batchId = (string) Str::uuid();
                $occurredAt = now();
                $quantity = 0;

                for ($number = (int) $start; $number <= (int) $end; $number++) {
                    $asset = new Asset();
                    $asset->item_id = $item->id;
                    $asset->unit_id = $unit->id;

                    $asset->patrimony = str_pad(
                        (string) $number,
                        strlen($start),
                        '0',
                        STR_PAD_LEFT
                    );

                    $asset->serial_number = null;
                    $asset->status = 'available';
                    $asset->save();

                    DB::table('asset_movements')->insert([
                        'asset_id' => $asset->id,
                        'user_id' => $user->id,
                        'unit_id' => $unit->id,
                        'batch_id' => $batchId,
                        'type' => 'entry',
                        'notes' => null,
                        'created_at' => $occurredAt,
                        'updated_at' => $occurredAt,
                    ]);

                    $quantity++;
                }

                return $quantity;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'patrimony_start' => 'Um patrimônio do intervalo já foi cadastrado. Nenhum equipamento deste lote foi salvo. Confira o intervalo e tente novamente.',
            ]);
        }
    }
}