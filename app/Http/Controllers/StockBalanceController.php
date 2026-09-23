<?php

namespace App\Http\Controllers;

use App\Models\StockBalance;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Models\StockMovement;

class StockBalanceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', StockBalance::class);

        $balances = StockBalance::query()
            ->with(['item', 'unit'])
            ->orderBy('item_id')
            ->orderBy('unit_id')
            ->paginate(15);

        return view('stock-balances.index', compact('balances'));
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