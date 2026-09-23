<?php

namespace App\Http\Controllers;

use App\Actions\RegisterAssetExit;
use App\Http\Requests\StoreAssetExitRequest;
use App\Models\Asset;
use App\Models\Establishment;
use App\Models\Sector;
use App\Models\Technician;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssetExitController extends Controller
{
    public function create(): View
    {
        Gate::authorize('recordExit', Asset::class);

        $assets = Asset::query()
            ->where('status', 'available')
            ->whereHas('item', function ($query) {
                $query->where('is_active', true)
                    ->where('tracking_type', 'individual');
            })
            ->whereHas('unit', function ($query) {
                $query->where('is_active', true);
            })
            ->with(['item', 'unit'])
            ->orderBy('patrimony')
            ->get();

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

        return view('asset-exits.create', compact(
            'assets',
            'technicians',
            'establishments',
            'sectors'
        ));
    }

    public function store(
        StoreAssetExitRequest $request,
        RegisterAssetExit $action
    ): RedirectResponse {
        $asset = $action->handle(
            $request->validated(),
            $request->user()
        );

        $message = "Saída do patrimônio {$asset->patrimony} registrada.";

        if ($request->boolean('replacement_required')) {
            $message .= ' Pendência de reposição criada.';
        }

        return redirect()
            ->route('asset-exits.create')
            ->with('status', $message);
    }
}