<?php

namespace App\Http\Controllers;

use App\Models\StockReplacementRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Actions\CompleteStockReplacement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockReplacementController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', StockReplacementRequest::class);

        $replacements = StockReplacementRequest::query()
            ->whereIn('status', ['pending', 'purchasing'])
            ->with([
                'movement.item',
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
            'stock-replacements.index',
            compact('replacements', 'statuses')
        );
    }

    public function complete(
        Request $request,
        StockReplacementRequest $replacement,
        CompleteStockReplacement $action
    ): RedirectResponse {
        Gate::authorize('complete', $replacement);

        $request->validate([
            'received' => ['required', 'accepted'],
        ], [
            'received.required' => 'Confirme que o material foi recebido.',
            'received.accepted' => 'Confirme que o material foi recebido.',
        ]);

        $action->handle($replacement, $request->user());

        return redirect()
            ->route('stock-replacements.index')
            ->with(
                'status',
                'Reposição concluída. A quantidade foi adicionada ao estoque de origem.'
            );
    }
}