@extends('layouts.app')
@section('title', 'Saída de equipamentos')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        @isset($movement)
            @include('movements.selector')
        @else
        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Registrar saída de equipamento</h1></div>
        @endisset

        <p>
            Selecione os patrimônios que serão retirados.
            O chamado, técnico e destino serão os mesmos para todos os selecionados.
        </p>

        

        

        @if (
            $assets->isEmpty() ||
            $technicians->isEmpty() ||
            $establishments->isEmpty() ||
            $sectors->isEmpty()
        )
            <p>
                É necessário ter um equipamento disponível,
                técnico, estabelecimento e setor ativos.
            </p>
        @else
            <form
                id="asset-exit-form"
                method="POST"
                action="{{ route('asset-exits.store') }}"
            >
                @csrf
                @isset($movement)<input type="hidden" name="unified" value="1">@endisset

                <fieldset style="grid-column: 1 / -1; min-width: 0; border: 1px solid #dce3ed; border-radius: 8px; padding: 16px;">
                    <legend>Equipamentos para saída</legend>
                    <label for="asset-search">Pesquisar patrimônio ou unidade</label>
                    <input id="asset-search" type="search" placeholder="Digite para encontrar..." autocomplete="off">
                    <p><button type="button" id="select-visible">Selecionar resultados</button>
                    <button type="button" id="clear-selection">Limpar seleção</button></p>
                    <p id="asset-selection-count" aria-live="polite"></p>
                    <div style="max-height: 280px; overflow-y: auto;">
                    @foreach ($assets as $asset)
                        <label data-asset-row style="display: flex; align-items: center; gap: 12px; padding: 8px;">
                            <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}"
                                style="width: auto; flex: none;"
                                @checked(in_array((string) $asset->id, array_map('strval', (array) old('asset_ids', old('asset_id') ? [old('asset_id')] : [])), true))>
                            <span>{{ $asset->patrimony }} — {{ $asset->item->name }} — Estoque: {{ $asset->unit->name }}</span>
                        </label>
                    @endforeach
                    </div>
                </fieldset>
                <p>
                    <label for="technician_id">Técnico que levou</label><br>

                    <select id="technician_id" name="technician_id" required>
                        <option value="">Selecione</option>

                        @foreach ($technicians as $technician)
                            <option
                                value="{{ $technician->id }}"
                                @selected(
                                    (string) old('technician_id') ===
                                    (string) $technician->id
                                )
                            >
                                {{ $technician->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <p>
                    <label for="ticket_number">Número do chamado</label><br>
                    <input
                        id="ticket_number"
                        name="ticket_number"
                        type="text"
                        maxlength="100"
                        value="{{ old('ticket_number') }}"
                        required
                    >
                </p>

                <p>
                    <label for="destination_establishment_id">
                        Estabelecimento de destino
                    </label><br>

                    <select
                        id="destination_establishment_id"
                        name="destination_establishment_id"
                        required
                    >
                        <option value="">Selecione</option>

                        @foreach ($establishments as $establishment)
                            <option
                                value="{{ $establishment->id }}"
                                @selected(
                                    (string) old('destination_establishment_id') ===
                                    (string) $establishment->id
                                )
                            >
                                {{ $establishment->code }} - {{ $establishment->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <p>
                    <label for="destination_sector_id">Setor de destino</label><br>

                    <select
                        id="destination_sector_id"
                        name="destination_sector_id"
                        required
                    >
                        <option value="">Selecione</option>

                        @foreach ($sectors as $sector)
                            <option
                                value="{{ $sector->id }}"
                                @selected(
                                    (string) old('destination_sector_id') ===
                                    (string) $sector->id
                                )
                            >
                                {{ $sector->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <input type="hidden" name="replacement_required" value="0">

                <p>
                    <label>
                        <input
                            id="replacement_required"
                            name="replacement_required"
                            type="checkbox"
                            value="1"
                            @checked(old('replacement_required') == '1')
                        >
                        Reposição necessária
                    </label>
                </p>

                <p>
                    Ao marcar, será criada uma pendência para cada equipamento selecionado
                    para o estoque de origem. O estabelecimento e o setor
                    de destino serão os responsáveis pelo custo.
                    O equipamento recebido como reposição terá patrimônio próprio.
                </p>

                <p>
                    <label for="notes">Observação (opcional)</label><br>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        maxlength="2000"
                    >{{ old('notes') }}</textarea>
                </p>

                <button id="submit-button" type="submit">
                    Registrar saída
                </button>
            </form>

            <script>
                const rows = [...document.querySelectorAll('[data-asset-row]')];
                const checkboxes = rows.map(row => row.querySelector('input'));
                const count = document.getElementById('asset-selection-count');
                function updateSelection() {
                    count.textContent = checkboxes.filter(input => input.checked).length + ' patrimônio(s) selecionado(s)';
                }
                const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
                document.getElementById('asset-search').addEventListener('input', event => {
                    const search = normalize(event.target.value.trim());
                    rows.forEach(row => { row.style.display = normalize(row.textContent).includes(search) ? 'flex' : 'none'; });
                });
                document.getElementById('select-visible').addEventListener('click', () => {
                    rows.filter(row => row.style.display !== 'none').forEach(row => { row.querySelector('input').checked = true; });
                    updateSelection();
                });
                document.getElementById('clear-selection').addEventListener('click', () => {
                    checkboxes.forEach(input => { input.checked = false; });
                    updateSelection();
                });
                checkboxes.forEach(input => input.addEventListener('change', updateSelection));
                updateSelection();
                document.getElementById('asset-exit-form')
                    .addEventListener('submit', event => {
                        if (!checkboxes.some(input => input.checked)) {
                            event.preventDefault();
                            count.textContent = 'Selecione pelo menos um patrimônio.';
                            document.getElementById('asset-search').focus();
                            return;
                        }
                        const button = document.getElementById('submit-button');

                        button.disabled = true;
                        button.textContent = 'Registrando…';
                    });
            </script>
        @endif
    

</div>
@endsection
