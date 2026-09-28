<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Http\Requests\UpdateAssetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class AssetController extends Controller
{
    public function edit(Asset $asset): View
    {
        Gate::authorize('update', $asset);
        $asset->load(['item', 'unit']);
        return view('assets.edit', compact('asset'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $asset) {
                $locked = Asset::query()->lockForUpdate()->findOrFail($asset->id);
                Gate::authorize('update', $locked);
                $before = $locked->only(['patrimony', 'serial_number', 'notes']);
                foreach ($request->validated() as $field => $value) {
                    $locked->{$field} = $value;
                }
                if (! $locked->isDirty()) {
                    return;
                }
                $locked->save();
                DB::table('asset_edits')->insert([
                    'asset_id' => $locked->id, 'user_id' => $request->user()->id,
                    'before' => json_encode($before, JSON_THROW_ON_ERROR),
                    'after' => json_encode($locked->only(['patrimony', 'serial_number', 'notes']), JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['patrimony' => 'Já existe um equipamento com esse patrimônio.']);
        }
        return redirect()->route('assets.show', $asset)->with('status', 'Dados do equipamento atualizados com sucesso.');
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Asset::class);

        $filters = $request->validate([
            'patrimony' => ['nullable', 'string', 'max:50'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ]);

        $query = Asset::query()
            ->with(['item', 'unit']);

        $patrimony = trim($filters['patrimony'] ?? '');

        if ($patrimony !== '') {
            $query->where('patrimony', $patrimony);
        }

        if (! empty($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }

        $assets = $query
            ->orderBy('patrimony')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $units = Unit::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = [
            'available' => 'Disponível',
            'in_use' => 'Em uso',
            'awaiting_disposal' => 'Aguardando descarte',
            'in_container' => 'Na caçamba',
            'disposed' => 'Descartado',
        ];

        return view(
            'assets.index',
            compact('assets', 'units', 'statuses', 'filters')
        );
    }

    public function show(Asset $asset): View
{
    Gate::authorize('view', $asset);

    $asset->load(['item', 'unit']);

    $edits = DB::table('asset_edits as e')->join('users as u', 'u.id', '=', 'e.user_id')
        ->where('e.asset_id', $asset->id)->select('e.*', 'u.name as user_name')
        ->orderByDesc('e.id')->paginate(10, ['*'], 'edits_page');

    $movements = $asset->movements()
        ->with([
    'user',
    'unit',
    'destinationEstablishment',
    'destinationSector',
    'legacyDestinationUnit',
])
        ->orderByDesc('created_at')
        ->orderByDesc('id')
        ->paginate(15);

    $statuses = [
        'available' => 'Disponível',
        'in_use' => 'Em uso',
        'awaiting_disposal' => 'Aguardando descarte',
        'in_container' => 'Na caçamba',
        'disposed' => 'Descartado',
    ];

    $movementTypes = [
        'entry' => 'Entrada',
        'exit' => 'Saída',
        'replacement' => 'Reposição',
        'return' => 'Devolução',
        'container_entry' => 'Entrada na caçamba',
        'disposal' => 'Descarte concluído',
    ];

    return view('assets.show', compact(
        'asset',
        'movements',
        'statuses',
        'movementTypes',
        'edits'
    ));
}
}
