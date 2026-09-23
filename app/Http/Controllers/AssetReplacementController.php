<?php

namespace App\Http\Controllers;

use App\Models\AssetReplacementRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Actions\CompleteAssetReplacement;
use App\Http\Requests\CompleteAssetReplacementRequest;
use Illuminate\Http\RedirectResponse;

class AssetReplacementController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', AssetReplacementRequest::class);

        $replacements = AssetReplacementRequest::query()
            ->whereIn('status', ['pending', 'purchasing'])
            ->with([
                'movement.asset.item',
                'movement.unit',
                'movement.technician',
                'destinationEstablishment',
                'destinationSector',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(15);

        $statuses = [
            'pending' => 'Pendente',
            'purchasing' => 'Em compra',
        ];

        return view(
            'asset-replacements.index',
            compact('replacements', 'statuses')
        );
    }

    public function edit(AssetReplacementRequest $replacement): View
{
    Gate::authorize('complete', $replacement);

    $replacement->load([
        'movement.asset.item',
        'movement.unit',
    ]);

    return view('asset-replacements.edit', compact('replacement'));
}

public function complete(
    CompleteAssetReplacementRequest $request,
    AssetReplacementRequest $replacement,
    CompleteAssetReplacement $action
): RedirectResponse {
    $asset = $action->handle(
        $replacement,
        $request->validated(),
        $request->user()
    );

    return redirect()
        ->route('asset-replacements.index')
        ->with(
            'status',
            "Reposição concluída. Patrimônio {$asset->patrimony} disponível no estoque de origem."
        );
}
}