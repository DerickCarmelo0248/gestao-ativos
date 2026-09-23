@extends('layouts.app')
@section('title', 'Saída de equipamentos')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Registrar saída de equipamento</h1></div>

        <p>
            Selecione o patrimônio que será retirado.
            Cada envio registra a saída de um equipamento.
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

                <p>
                    <label for="asset_id">Equipamento</label><br>

                    <select id="asset_id" name="asset_id" required>
                        <option value="">Selecione o patrimônio</option>

                        @foreach ($assets as $asset)
                            <option
                                value="{{ $asset->id }}"
                                @selected(
                                    (string) old('asset_id') ===
                                    (string) $asset->id
                                )
                            >
                                {{ $asset->patrimony }}
                                — {{ $asset->item->name }}
                                — Estoque: {{ $asset->unit->name }}
                            </option>
                        @endforeach
                    </select>
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
                    Ao marcar, será criada uma pendência de um equipamento
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
                document.getElementById('asset-exit-form')
                    .addEventListener('submit', () => {
                        const button = document.getElementById('submit-button');

                        button.disabled = true;
                        button.textContent = 'Registrando…';
                    });
            </script>
        @endif
    

</div>
@endsection
