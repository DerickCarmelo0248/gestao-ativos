<?php

namespace App\Http\Controllers;

use App\Models\StockBalance;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Models\StockMovement;

class StockBalanceController extends Controller
{
    public function adjust(\Illuminate\Http\Request $request, StockBalance $stockBalance): \Illuminate\Http\RedirectResponse
    {
        Gate::authorize('adjust', $stockBalance);
        if (is_string($request->input('reason'))) {
            $request->merge(['reason' => trim($request->input('reason'))]);
        }
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'expected_quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'reason' => ['required', 'string', 'max:2000'],
        ], ['reason.required' => 'Informe o motivo do ajuste.', 'quantity.min' => 'O saldo não pode ser negativo.']);
        \Illuminate\Support\Facades\DB::transaction(function () use ($stockBalance, $data, $request) {
            $balance = StockBalance::query()->lockForUpdate()->findOrFail($stockBalance->id);
            Gate::authorize('adjust', $balance);
            $before = $balance->quantity;
            if ($before !== (int) $data['expected_quantity']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['quantity' => 'O saldo mudou desde que você abriu a tela. Atualize a página e confira antes de ajustar.']);
            }
            $after = (int) $data['quantity'];
            if ($before === $after) {
                return;
            }
            $balance->quantity = $after;
            $balance->save();
            \Illuminate\Support\Facades\DB::table('stock_movements')->insert([
                'item_id' => $balance->item_id, 'unit_id' => $balance->unit_id,
                'user_id' => $request->user()->id,
                'type' => $after > $before ? 'adjustment_add' : 'adjustment_remove',
                'quantity' => abs($after - $before),
                'notes' => "Ajuste de saldo: {$before} → {$after}. Motivo: ".$data['reason'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return redirect()->route('stock-balances.show', $stockBalance)->with('status', 'Saldo conferido e salvo com sucesso.');
    }

    public function index(\Illuminate\Http\Request $request): View
    {
        Gate::authorize('viewAny', StockBalance::class);

        $filters = $request->validate([
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ]);
        $selectedItem = ! empty($filters['item_id']) ? \App\Models\Item::findOrFail($filters['item_id']) : null;
        $balances = StockBalance::query()
            ->when($selectedItem, fn ($q) => $q->where('item_id', $selectedItem->id))
            ->when(! empty($filters['unit_id']), fn ($q) => $q->where('unit_id', $filters['unit_id']))
            ->with(['item', 'unit'])
            ->orderBy('item_id')
            ->orderBy('unit_id')
            ->paginate(15)->withQueryString();

        return view('stock-balances.index', compact('balances', 'selectedItem'));
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
            'adjustment_add' => 'Ajuste: acréscimo',
            'adjustment_remove' => 'Ajuste: redução',
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
