<?php

namespace App\Http\Controllers;

use App\Actions\RegisterStockExit;
use App\Http\Requests\StoreStockExitRequest;
use App\Models\Establishment;
use App\Models\Item;
use App\Models\Sector;
use App\Models\StockBalance;
use App\Models\Technician;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockExitController extends Controller
{
    public function create(): View
    {
        Gate::authorize('recordExit', StockBalance::class);

        $items = Item::query()
            ->where('is_active', true)
            ->where('tracking_type', 'quantity')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $units = Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $technicians = Technician::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $establishments = Establishment::query()
            ->where('is_active', true)
            ->orderByRaw('CAST(code AS INTEGER)')
            ->get(['id', 'code', 'name']);

        $sectors = Sector::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('stock-exits.create', compact(
            'items',
            'units',
            'technicians',
            'establishments',
            'sectors'
        ));
    }

    public function store(
        StoreStockExitRequest $request,
        RegisterStockExit $action
    ): RedirectResponse {
        $balance = $action->handle(
            $request->validated(),
            $request->user()
        );

        $message = "Saída registrada. Saldo após esta operação: {$balance->quantity} unidades.";

        if ($request->boolean('replacement_required')) {
            $message .= ' Pendência de reposição criada.';
        }

        return redirect()
            ->route('stock-exits.create')
            ->with('status', $message);
    }
}