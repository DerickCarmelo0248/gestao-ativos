@extends('layouts.app')
@section('title', 'Saída por quantidade')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        @isset($movement)
            @include('movements.selector')
        @else
        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Registrar saída por quantidade</h1></div>
        @endisset

        

        

        @if (
            $items->isEmpty() ||
            $units->isEmpty() ||
            $technicians->isEmpty() ||
            $establishments->isEmpty() ||
            $sectors->isEmpty()
        )
            <p>
                Para registrar a saída, é necessário ter item por
                quantidade, unidade, técnico, estabelecimento e setor ativos.
            </p>
        @else
            <form
                id="stock-exit-form"
                method="POST"
                action="{{ route('stock-exits.store') }}"
            >
                @csrf
                @isset($movement)<input type="hidden" name="unified" value="1">@endisset

                <p>
                    <label for="item_id">Item</label><br>
                    <select id="item_id" name="item_id" required>
                        <option value="">Selecione</option>

                        @foreach ($items as $item)
                            <option
                                value="{{ $item->id }}"
                                @selected(
                                    (string) old('item_id', $selectedItem->id ?? null) ===
                                    (string) $item->id
                                )
                            >
                                {{ $item->code }} — {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <p>
                    <label for="unit_id">Unidade de estoque de origem</label><br>
                    <select id="unit_id" name="unit_id" required>
                        <option value="">Selecione</option>

                        @foreach ($units as $unit)
                            <option
                                value="{{ $unit->id }}"
                                @selected(
                                    (string) old('unit_id') ===
                                    (string) $unit->id
                                )
                            >
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <p>
                    <label for="quantity">Quantidade retirada</label><br>
                    <input
                        id="quantity"
                        name="quantity"
                        type="number"
                        min="1"
                        max="10000"
                        step="1"
                        value="{{ old('quantity') }}"
                        required
                    >
                </p>

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
                    Ao marcar, será criada uma pendência para repor toda
                    a quantidade retirada no estoque de origem.
                    O estabelecimento e o setor de destino serão
                    os responsáveis pelo custo.
                </p>

                <p>
                    <label for="notes">Observação (opcional)</label><br>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        maxlength="2000"
                    >{{ old('notes') }} </textarea>
                </p>

                <button id="submit-button" type="submit">
                    Registrar saída
                </button>
            </form>

            <script>
                document.getElementById('stock-exit-form')
                    .addEventListener('submit', () => {
                        const button = document.getElementById('submit-button');

                        button.disabled = true;
                        button.textContent = 'Registrando…';
                    });
            </script>
        @endif
    

</div>
@endsection
