<?php

namespace App\Http\Controllers;

use App\Actions\RegisterAssetBatch;
use App\Http\Requests\StoreAssetBatchRequest;
use App\Models\Asset;
use App\Models\Item;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssetBatchController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Asset::class);

        $items = Item::query()
            ->where('is_active', true)
            ->where('tracking_type', 'individual')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $units = Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('assets.batch-create', compact('items', 'units'));
    }

    public function store(
        StoreAssetBatchRequest $request,
        RegisterAssetBatch $action
    ): RedirectResponse {
        $quantity = $action->handle(
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('assets.batch.create')
            ->with(
                'status',
                "{$quantity} equipamentos cadastrados com sucesso."
            );
    }
}