@extends('layouts.app')
@section('title', 'Reposição de equipamentos')
@section('content')
<div class="module-page">

    
        <a href="{{ route('asset-replacements.index') }}">
            Voltar às pendências
        </a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Receber reposição de equipamento</h1></div>

        <p>
            <strong>Item:</strong>
            {{ $replacement->movement->asset->item->name }}
        </p>

        <p>
            <strong>Patrimônio que saiu:</strong>
            {{ $replacement->movement->asset->patrimony }}
        </p>

        <p>
            <strong>Estoque que receberá a reposição:</strong>
            {{ $replacement->movement->unit->name }}
        </p>

        <p>
            Registre o novo equipamento somente após o recebimento.
            Esta operação repõe uma unidade.
        </p>

        

        <form
            id="replacement-form"
            method="POST"
            action="{{ route('asset-replacements.complete', $replacement) }}"
        >
            @csrf

            <p>
                <label for="patrimony">
                    Patrimônio do equipamento recebido
                </label><br>

                <input
                    id="patrimony"
                    name="patrimony"
                    type="text"
                    maxlength="50"
                    value="{{ old('patrimony') }}"
                    required
                >
            </p>

            <p>
                <label for="serial_number">
                    Número de série (opcional)
                </label><br>

                <input
                    id="serial_number"
                    name="serial_number"
                    type="text"
                    maxlength="100"
                    value="{{ old('serial_number') }}"
                >
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

            <p>
                <label>
                    <input
                        type="checkbox"
                        name="received"
                        value="1"
                        @checked(old('received') == '1')
                        required
                    >
                    Confirmo que o equipamento foi recebido no estoque
                    de {{ $replacement->movement->unit->name }}.
                </label>
            </p>

            <button id="submit-button" type="submit">
                Concluir reposição
            </button>
        </form>

        <script>
            document.getElementById('replacement-form')
                .addEventListener('submit', () => {
                    const button = document.getElementById('submit-button');

                    button.disabled = true;
                    button.textContent = 'Registrando…';
                });
        </script>
    

</div>
@endsection
