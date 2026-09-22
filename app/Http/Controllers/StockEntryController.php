<?php

namespace App\Http\Controllers;

use App\Actions\RegisterStockEntry;
use App\Http\Requests\StoreStockEntryRequest;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockEntryController extends Controller
{
    public function create(): View
    {
        Gate::authorize('recordEntry', StockBalance::class);

        $items = Item::query()
            ->where('is_active', true)
            ->where('tracking_type', 'quantity')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $units = Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('stock-entries.create', compact('items', 'units'));
    }

    public function store(
        StoreStockEntryRequest $request,
        RegisterStockEntry $action
    ): RedirectResponse {
        $balance = $action->handle(
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('stock-entries.create')
            ->with(
                'status',
                "Entrada registrada. Saldo após esta operação: {$balance->quantity} unidades."
            );
    }
}