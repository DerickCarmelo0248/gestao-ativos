<?php

namespace App\Http\Controllers;

use App\Models\StockBalance;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Models\StockMovement;

class StockBalanceController extends Controller
{
    public function index(\Illuminate\Http\Request $request): View
    {
        Gate::authorize('viewAny', StockBalance::class);

        $filters = $request->validate([
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ]);
        $selectedItem = ! empty($filters['item_id']) ? \App\Models\Item::findOrFail($filters['item_id']) : null;
        $balances = StockBalance::query()
            ->when($selectedItem, fn ($q) => $q->where('item_id', $selectedItem->id))
            ->when(! empty($filters['unit_id']), fn ($q) => $q->where('unit_id', $filters['unit_id']))
            ->with(['item', 'unit'])
            ->orderBy('item_id')
            ->orderBy('unit_id')
            ->paginate(15)->withQueryString();

        return view('stock-balances.index', compact('balances', 'selectedItem'));
    }

    public function show(StockBalance $stockBalance): View
    {
        Gate::authorize('view', $stockBalance);

        $stockBalance->load(['item', 'unit']);

        $movements = StockMovement::query()
            ->where('item_id', $stockBalance->item_id)
            ->where('unit_id', $stockBalance->unit_id)
            ->with([
                'user',
                'destinationEstablishment',
                'destinationSector',
                'legacyDestinationUnit',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        $movementTypes = [
            'entry' => 'Entrada',
            'exit' => 'Saída',
            'replacement' => 'Reposição',
        ];

        return view('stock-balances.show', compact(
            'stockBalance',
            'movements',
            'movementTypes'
        ));
    }
}
