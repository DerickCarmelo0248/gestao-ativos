@extends('layouts.app')
@section('title', 'Devolução de equipamentos')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Registrar devolução de equipamento</h1></div>

        <p>A devolução mantém o patrimônio e o histórico do equipamento.</p>

        

        

        @if ($assets->isEmpty() || $units->isEmpty() || $technicians->isEmpty())
            <p>
                É necessário ter um equipamento em uso,
                um estoque ativo e um técnico ativo.
            </p>
        @else
            <form method="POST"
                  action="{{ route('asset-returns.store') }}"
                  id="return-form">
                @csrf

                <p>
                    <label for="asset_id">Equipamento</label><br>
                    <select id="asset_id" name="asset_id" required>
                        <option value="">Selecione</option>
                        @foreach ($assets as $asset)
                            <option value="{{ $asset->id }}"
                                @selected(old('asset_id') == $asset->id)>
                                {{ $asset->patrimony }} — {{ $asset->item->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <p>
                    <label for="unit_id">Estoque de recebimento</label><br>
                    <select id="unit_id" name="unit_id" required>
                        <option value="">Selecione</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}"
                                @selected(old('unit_id') == $unit->id)>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <p>
                    <label for="technician_id">Técnico que devolveu</label><br>
                    <select id="technician_id" name="technician_id" required>
                        <option value="">Selecione</option>
                        @foreach ($technicians as $technician)
                            <option value="{{ $technician->id }}"
                                @selected(old('technician_id') == $technician->id)>
                                {{ $technician->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <p>
                    <label for="return_status">Condição do equipamento</label><br>
                    <select id="return_status" name="return_status" required>
                        <option value="">Selecione</option>
                        <option value="available"
                            @selected(old('return_status') === 'available')>
                            Disponível para uso
                        </option>
                        <option value="awaiting_disposal"
                            @selected(old('return_status') === 'awaiting_disposal')>
                            Aguardando descarte
                        </option>
                    </select>
                </p>

                <p>
                    <label for="notes">
                        Observação (obrigatória para descarte)
                    </label><br>
                    <textarea id="notes" name="notes"
                              rows="4" maxlength="2000">{{ old('notes') }}</textarea>
                </p>

                <button type="submit" id="submit-button">
                    Registrar devolução
                </button>
            </form>

            <script>
                const condition = document.getElementById('return_status');
                const notes = document.getElementById('notes');

                function updateRequired() {
                    notes.required = condition.value === 'awaiting_disposal';
                }

                condition.addEventListener('change', updateRequired);
                updateRequired();

                document.getElementById('return-form')
                    .addEventListener('submit', () => {
                        const button = document.getElementById('submit-button');
                        button.disabled = true;
                        button.textContent = 'Registrando…';
                    });
            </script>
        @endif
    

</div>
@endsection
