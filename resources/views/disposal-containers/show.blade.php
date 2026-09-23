@extends('layouts.app')
@section('title', 'Caçambas de descarte')
@section('content')
<div class="module-page">

    
        <a href="{{ route('disposal-containers.index') }}">
            Voltar às caçambas
        </a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Caçamba #{{ $container->id }}</h1></div>

        <p>
            <strong>Unidade:</strong>
            {{ $container->unit->name }}
        </p>

        <p>
            <strong>Situação:</strong>
            {{ $container->status === 'open' ? 'Aberta' : 'Encerrada' }}
        </p>

        

        

        @can('addItem', $container)
            <h2>Adicionar equipamento com patrimônio</h2>

            <p>
                Selecione um equipamento aguardando descarte.
                A unidade de origem será preservada no registro.
            </p>

            @if ($assets->isEmpty())
                <p>Nenhum equipamento aguardando descarte.</p>
            @else
                <form
                    id="add-asset-form"
                    method="POST"
                    action="{{ route('disposal-containers.assets.store', $container) }}"
                >
                    @csrf

                    <p>
                        <label for="asset_id">Equipamento</label><br>

                        <select id="asset_id" name="asset_id" required>
                            <option value="">Selecione</option>

                            @foreach ($assets as $asset)
                                <option
                                    value="{{ $asset->id }}"
                                    @selected(old('asset_id') == $asset->id)
                                >
                                    {{ $asset->patrimony }}
                                    — {{ $asset->item->name }}
                                    — Origem: {{ $asset->unit->name }}
                                </option>
                            @endforeach
                        </select>
                    </p>

                    <p>
                        <label for="reason">Motivo do descarte</label><br>

                        <textarea
                            id="reason"
                            name="reason"
                            rows="4"
                            maxlength="2000"
                            required
                        >{{ old('reason') }}</textarea>
                    </p>

                    <button id="add-button" type="submit">
                        Adicionar à caçamba
                    </button>
                </form>
            @endif
        @endcan

@can('addItem', $container)
    <hr>

    <h2>Material não cadastrado no estoque</h2>

    <p>
        Use para materiais que não fazem parte do saldo atual do estoque.
        Esta inclusão não altera o saldo disponível.
    </p>

    @if ($catalogItems->isEmpty() || $originUnits->isEmpty())
        <p>
            É necessário ter um item ativo no catálogo
            e uma unidade de origem ativa.
        </p>
    @else
        <form
            id="external-material-form"
            method="POST"
            action="{{ route('disposal-containers.external.store', $container) }}"
        >
            @csrf

            <p>
                <label for="external_item_id">Item/modelo</label><br>

                <select
                    id="external_item_id"
                    name="item_id"
                    required
                >
                    <option value="">Selecione</option>

                    @foreach ($catalogItems as $catalogItem)
                        <option
                            value="{{ $catalogItem->id }}"
                            data-tracking="{{ $catalogItem->tracking_type }}"
                            @selected(old('item_id') == $catalogItem->id)
                        >
                            {{ $catalogItem->code }}
                            — {{ $catalogItem->name }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p>
                <label for="external_origin_unit_id">
                    Unidade de origem
                </label><br>

                <select
                    id="external_origin_unit_id"
                    name="origin_unit_id"
                    required
                >
                    <option value="">Selecione</option>

                    @foreach ($originUnits as $originUnit)
                        <option
                            value="{{ $originUnit->id }}"
                            @selected(old('origin_unit_id') == $originUnit->id)
                        >
                            {{ $originUnit->name }}
                        </option>
                    @endforeach
                </select>
            </p>

            <fieldset id="external-individual-fields" hidden disabled>
                <legend>Equipamento com patrimônio</legend>

                <p>Cada inclusão registra um equipamento.</p>

                <p>
                    <label for="external_patrimony">Patrimônio</label><br>

                    <input
                        id="external_patrimony"
                        name="patrimony"
                        type="text"
                        maxlength="50"
                        value="{{ old('patrimony') }}"
                    >
                </p>

                <p>
                    <label for="external_serial_number">
                        Número de série (opcional)
                    </label><br>

                    <input
                        id="external_serial_number"
                        name="serial_number"
                        type="text"
                        maxlength="100"
                        value="{{ old('serial_number') }}"
                    >
                </p>
            </fieldset>

            <fieldset id="external-quantity-fields" hidden disabled>
                <legend>Material sem patrimônio</legend>

                <p>
                    <label for="external_quantity">Quantidade</label><br>

                    <input
                        id="external_quantity"
                        name="quantity"
                        type="number"
                        min="1"
                        max="10000"
                        step="1"
                        value="{{ old('quantity') }}"
                    >
                </p>
            </fieldset>

            <p>
                <label for="external_reason">Motivo do descarte</label><br>

                <textarea
                    id="external_reason"
                    name="reason"
                    rows="4"
                    maxlength="2000"
                    required
                >{{ old('reason') }}</textarea>
            </p>

            <button id="external-submit-button" type="submit">
                Adicionar material à caçamba
            </button>
        </form>

        <script>
            (() => {
                const form = document.getElementById('external-material-form');
                const item = document.getElementById('external_item_id');
                const individualFields = document.getElementById(
                    'external-individual-fields'
                );
                const quantityFields = document.getElementById(
                    'external-quantity-fields'
                );
                const patrimony = document.getElementById('external_patrimony');
                const quantity = document.getElementById('external_quantity');

                function updateFields() {
                    const tracking =
                        item.selectedOptions[0]?.dataset.tracking;

                    const individual = tracking === 'individual';
                    const byQuantity = tracking === 'quantity';

                    individualFields.hidden = !individual;
                    individualFields.disabled = !individual;
                    patrimony.required = individual;

                    quantityFields.hidden = !byQuantity;
                    quantityFields.disabled = !byQuantity;
                    quantity.required = byQuantity;
                }

                item.addEventListener('change', updateFields);
                updateFields();

                form.addEventListener('submit', () => {
                    const button = document.getElementById(
                        'external-submit-button'
                    );

                    button.disabled = true;
                    button.textContent = 'Adicionando…';
                });
            })();
        </script>
    @endif
@endcan

        <h2>Materiais incluídos</h2>

        <div class="table-wrapper" tabindex="0" role="region" aria-label="Tabela de registros"><table>
            <thead>
                <tr>
                    <th scope="col">Data</th>
                    <th scope="col">Categoria</th>
                    <th scope="col">Item</th>
                    <th scope="col">Patrimônio</th>
                    <th scope="col">Número de série</th>
                    <th scope="col">Quantidade</th>
                    <th scope="col">Origem</th>
                    <th scope="col">Cadastro</th>
                    <th scope="col">Incluído por</th>
                    <th scope="col">Motivo</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>
                            {{ $item->added_at->copy()
                                ->timezone('America/Sao_Paulo')
                                ->format('d/m/Y') }}
                        </td>

                        <td>{{ $item->category_name }}</td>

                        <td>
                            {{ $item->item_code }} — {{ $item->item_name }}
                        </td>

                        <td>{{ $item->patrimony ?? '—' }}</td>
                        <td>{{ $item->serial_number ?? '—' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->origin_unit_name }}</td>
                        <td>
                            {{ $item->registration_source === 'external'
                                ? 'Direto para descarte'
                                : 'Equipamento cadastrado' }}
                        </td>
                        <td>{{ $item->addedBy->name }}</td>
                        <td>{{ $item->reason }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            Nenhum material incluído nesta caçamba.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table></div>

        @if ($items->hasPages())
            <nav aria-label="Paginação dos materiais">
                @if (! $items->onFirstPage())
                    <a href="{{ $items->previousPageUrl() }}">Anterior</a>
                @endif

                <span>
                    Página {{ $items->currentPage() }}
                    de {{ $items->lastPage() }}
                </span>

                @if ($items->hasMorePages())
                    <a href="{{ $items->nextPageUrl() }}">Próxima</a>
                @endif
            </nav>
        @endif

        @can('close', $container)
    <hr>

    <section>
        <h2>Encerrar descarte</h2>

        @if ($items->total() > 0)
            <p>
                Confirme somente após a retirada dos materiais.
                Todos os equipamentos desta caçamba serão marcados
                como descartados.
            </p>

            <p>
                Esta caçamba será encerrada e uma nova será aberta
                automaticamente em {{ $container->unit->name }}.
            </p>

            <form
                id="close-container-form"
                method="POST"
                action="{{ route('disposal-containers.close', $container) }}"
            >
                @csrf

                <p>
                    <label>
                        <input
                            type="checkbox"
                            name="removal_confirmed"
                            value="1"
                            required
                        >
                        Confirmo que os materiais da caçamba
                        #{{ $container->id }} foram retirados
                        para descarte.
                    </label>
                </p>

                <button id="close-container-button" type="submit">
                    Confirmar retirada e encerrar caçamba
                </button>
            </form>
        @else
            <p>
                Inclua materiais antes de encerrar esta caçamba.
            </p>
        @endif
    </section>
@endcan

@if ($container->status === 'closed')
    <p>
        <strong>Descarte encerrado em:</strong>
        {{ $container->closed_at->copy()
            ->timezone('America/Sao_Paulo')
            ->format('d/m/Y') }}
    </p>

    <p>
        Esta caçamba está encerrada e permanece disponível
        para consulta.
    </p>
@endif

<script>
    (() => {
        const form = document.getElementById('close-container-form');

        if (!form) {
            return;
        }

        form.addEventListener('submit', () => {
            const button = document.getElementById(
                'close-container-button'
            );

            button.disabled = true;
            button.textContent = 'Encerrando…';
        });
    })();
</script>
    

    <script>
        const form = document.getElementById('add-asset-form');

        if (form) {
            form.addEventListener('submit', () => {
                const button = document.getElementById('add-button');

                button.disabled = true;
                button.textContent = 'Adicionando…';
            });
        }
    </script>

</div>
@endsection
