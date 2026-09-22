<?php

namespace App\Http\Controllers;

use App\Models\StockBalance;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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
}