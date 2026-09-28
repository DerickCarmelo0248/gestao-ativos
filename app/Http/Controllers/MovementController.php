<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Item;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MovementController extends Controller
{
    public function entry(Request $request): View
    {
        return $this->form($request, 'entry');
    }

    public function exit(Request $request): View
    {
        return $this->form($request, 'exit');
    }

    private function form(Request $request, string $movement): View
    {
        Gate::authorize($movement === 'entry' ? 'create' : 'recordExit', Asset::class);
        Gate::authorize($movement === 'entry' ? 'recordEntry' : 'recordExit', StockBalance::class);
        $data = $request->validate(['item_id' => ['nullable', 'integer', 'exists:items,id']]);
        $catalogItems = Item::where('is_active', true)->orderBy('name')->get();
        $selectedItem = isset($data['item_id'])
            ? Item::where('is_active', true)->findOrFail($data['item_id']) : null;
        $shared = compact('catalogItems', 'selectedItem', 'movement');
        if (! $selectedItem) {
            return view('movements.choose', $shared);
        }
        $individual = $selectedItem->tracking_type === 'individual';
        $controller = $movement === 'entry'
            ? ($individual ? AssetBatchController::class : StockEntryController::class)
            : ($individual ? AssetExitController::class : StockExitController::class);
        $view = app($controller)->create();
        $viewData = $view->getData();
        if (isset($viewData['assets'])) {
            $view->with('assets', $viewData['assets']->where('item_id', $selectedItem->id));
        } else {
            $view->with('items', collect([$selectedItem]));
        }
        return $view->with($shared);
    }
}
