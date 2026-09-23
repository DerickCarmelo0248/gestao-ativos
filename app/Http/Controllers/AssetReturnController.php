<?php

namespace App\Http\Controllers;

use App\Actions\RegisterAssetReturn;
use App\Http\Requests\StoreAssetReturnRequest;
use App\Models\Asset;
use App\Models\Technician;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssetReturnController extends Controller
{
    public function create(): View
    {
        Gate::authorize('recordReturn', Asset::class);

        $assets = Asset::query()
            ->where('status', 'in_use')
            ->with('item')
            ->orderBy('patrimony')
            ->get();

        $units = Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $technicians = Technician::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('asset-returns.create', compact(
            'assets',
            'units',
            'technicians'
        ));
    }

    public function store(
        StoreAssetReturnRequest $request,
        RegisterAssetReturn $action
    ): RedirectResponse {
        $asset = $action->handle(
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('asset-returns.create')
            ->with(
                'status',
                "Devolução do patrimônio {$asset->patrimony} registrada."
            );
    }
}