<?php

namespace App\Http\Controllers;

use App\Actions\OpenDisposalContainer;
use App\Models\DisposalContainer;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Actions\AddAssetToDisposalContainer;
use App\Models\Asset;
use App\Actions\AddExternalMaterialToDisposalContainer;
use App\Models\Item;
use App\Actions\CloseDisposalContainer;

class DisposalContainerController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DisposalContainer::class);

        $containers = DisposalContainer::query()
            ->with(['unit', 'openedBy', 'closedBy'])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->paginate(15);

        $openUnitIds = DisposalContainer::query()
            ->where('status', 'open')
            ->pluck('unit_id');

        $units = Unit::query()
            ->where('is_active', true)
            ->whereIn('code', ['VOT', 'RPR'])
            ->whereNotIn('id', $openUnitIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('disposal-containers.index', compact(
            'containers',
            'units'
        ));
    }

    public function store(
        Request $request,
        OpenDisposalContainer $action
    ): RedirectResponse {
        Gate::authorize('create', DisposalContainer::class);

        $data = $request->validate([
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')
                    ->where('is_active', true)
                    ->whereIn('code', ['VOT', 'RPR']),
            ],
        ], [
            'unit_id.required' => 'Selecione a unidade da caçamba.',
            'unit_id.integer' => 'Selecione uma unidade válida.',
            'unit_id.exists' =>
                'Selecione Votuporanga ou Rio Preto, com cadastro ativo.',
        ]);

        $container = $action->handle(
            (int) $data['unit_id'],
            $request->user()
        );

        return redirect()
            ->route('disposal-containers.index')
            ->with(
                'status',
                "Caçamba #{$container->id} aberta com sucesso."
            );
    }

    public function show(DisposalContainer $container): View
{
    Gate::authorize('view', $container);

    $container->load('unit');

    $items = $container->items()
        ->with('addedBy')
        ->orderByDesc('id')
        ->paginate(20);

    $assets = Asset::query()
        ->where('status', 'awaiting_disposal')
        ->whereHas('item', function ($query) {
            $query->where('tracking_type', 'individual');
        })
        ->with(['item', 'unit'])
        ->orderBy('patrimony')
        ->get();

        $catalogItems = Item::query()
    ->where('is_active', true)
    ->whereIn('tracking_type', ['individual', 'quantity'])
    ->orderBy('name')
    ->get(['id', 'code', 'name', 'tracking_type']);

$originUnits = Unit::query()
    ->where('is_active', true)
    ->orderBy('name')
    ->get(['id', 'name']);

    return view('disposal-containers.show', compact(
    'container',
    'items',
    'assets',
    'catalogItems',
    'originUnits'
));
}

public function addAsset(
    Request $request,
    DisposalContainer $container,
    AddAssetToDisposalContainer $action
): RedirectResponse {
    Gate::authorize('addItem', $container);

    if (is_string($request->input('reason'))) {
        $request->merge([
            'reason' => trim($request->input('reason')),
        ]);
    }

    $data = $request->validate([
        'asset_id' => [
            'required',
            'integer',
            Rule::exists('assets', 'id')
                ->where('status', 'awaiting_disposal'),
        ],
        'reason' => [
            'required',
            'string',
            'max:2000',
        ],
    ], [
        'asset_id.required' => 'Selecione o equipamento.',
        'asset_id.integer' => 'Selecione um equipamento válido.',
        'asset_id.exists' =>
            'Selecione um equipamento aguardando descarte.',
        'reason.required' => 'Informe o motivo do descarte.',
        'reason.string' => 'O motivo deve ser um texto.',
        'reason.max' => 'O motivo deve ter até 2.000 caracteres.',
    ]);

    $item = $action->handle(
        $container,
        $data,
        $request->user()
    );

    return redirect()
        ->route('disposal-containers.show', $container)
        ->with(
            'status',
            "Patrimônio {$item->patrimony} incluído na caçamba."
        );
}

public function addExternal(
    Request $request,
    DisposalContainer $container,
    AddExternalMaterialToDisposalContainer $action
): RedirectResponse {
    Gate::authorize('addItem', $container);

    $normalized = [];

    foreach (['patrimony', 'serial_number', 'reason'] as $field) {
        $value = $request->input($field);

        if (is_string($value)) {
            $normalized[$field] = trim($value);
        }
    }

    $request->merge($normalized);

    $data = $request->validate([
        'item_id' => [
            'required',
            'integer',
            Rule::exists('items', 'id')
                ->where('is_active', true)
                ->whereIn('tracking_type', ['individual', 'quantity']),
        ],
        'origin_unit_id' => [
            'required',
            'integer',
            Rule::exists('units', 'id')->where('is_active', true),
        ],
        'patrimony' => ['nullable', 'string', 'max:50'],
        'serial_number' => ['nullable', 'string', 'max:100'],
        'quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
        'reason' => ['required', 'string', 'max:2000'],
    ], [
        'required' => 'O campo :attribute é obrigatório.',
        'integer' => 'Informe um número inteiro para :attribute.',
        'item_id.exists' => 'Selecione um item ativo do catálogo.',
        'origin_unit_id.exists' => 'Selecione uma unidade de origem ativa.',
        'patrimony.max' => 'O patrimônio deve ter até 50 caracteres.',
        'serial_number.max' => 'O número de série deve ter até 100 caracteres.',
        'quantity.min' => 'A quantidade mínima é 1.',
        'quantity.max' => 'Registre até 10.000 unidades por inclusão.',
        'reason.max' => 'O motivo deve ter até 2.000 caracteres.',
    ], [
        'item_id' => 'item',
        'origin_unit_id' => 'unidade de origem',
        'quantity' => 'quantidade',
        'reason' => 'motivo',
    ]);

    $record = $action->handle(
        $container,
        $data,
        $request->user()
    );

    return redirect()
        ->route('disposal-containers.show', $container)
        ->with(
            'status',
            "{$record->quantity} unidade(s) de {$record->item_name} incluída(s) diretamente na caçamba."
        );
}

public function close(
    Request $request,
    DisposalContainer $container,
    CloseDisposalContainer $action
): RedirectResponse {
    Gate::authorize('close', $container);

    $request->validate([
        'removal_confirmed' => ['required', 'accepted'],
    ], [
        'removal_confirmed.required' =>
            'Confirme que os materiais foram retirados.',
        'removal_confirmed.accepted' =>
            'Confirme que os materiais foram retirados.',
    ]);

    $next = $action->handle(
        $container,
        $request->user()
    );

    return redirect()
        ->route('disposal-containers.show', $container)
        ->with(
            'status',
            "Caçamba #{$container->id} encerrada. "
            ."A caçamba #{$next->id} foi aberta na mesma unidade."
        );
}
}