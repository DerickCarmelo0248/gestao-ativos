<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssetController extends Controller
{
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
        'movementTypes'
    ));
}
}