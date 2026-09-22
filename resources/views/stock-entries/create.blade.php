<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrada de estoque — Gestão de Ativos</title>
</head>
<body>
    <main>
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <h1>Entrada de estoque por quantidade</h1>

        <p>
            Registre a quantidade recebida.
            Ela será somada ao saldo do item na unidade selecionada.
        </p>

        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($items->isEmpty() || $units->isEmpty())
            <p>
                É necessário ter um item ativo com controle por quantidade
                e uma unidade ativa para registrar a entrada.
            </p>

            @can('create', \App\Models\Item::class)
                <a href="{{ route('items.create') }}">Cadastrar item</a>
            @endcan
        @else
            <form
                id="stock-entry-form"
                method="POST"
                action="{{ route('stock-entries.store') }}"
            >
                @csrf

                <p>
                    <label for="item_id">Item</label><br>
                    <select id="item_id" name="item_id" required>
                        <option value="">Selecione</option>

                        @foreach ($items as $item)
                            <option
                                value="{{ $item->id }}"
                                @selected(
                                    (string) old('item_id') ===
                                    (string) $item->id
                                )
                            >
                                {{ $item->code }} — {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <p>
                    <label for="unit_id">Unidade de entrada</label><br>
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
                    <label for="quantity">Quantidade recebida</label><br>
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

<input type="hidden" name="has_destination" value="0">

<p>
    <label>
        <input
            id="has_destination"
            name="has_destination"
            type="checkbox"
            value="1"
            @checked(old('has_destination') == '1')
        >
        Este material já tem destino definido?
    </label>
</p>

<fieldset id="destination-fields">
    <legend>Destino previsto</legend>

    <p>O destino vale para todos os itens desta entrada.</p>

    <p>
        <label for="destination_establishment_id">
            Estabelecimento de destino
        </label><br>

        <select
            id="destination_establishment_id"
            name="destination_establishment_id"
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
</fieldset>

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
                    Registrar entrada
                </button>
            </form>

            <script>
    const form = document.getElementById('stock-entry-form');
    const checkbox = document.getElementById('has_destination');
    const fields = document.getElementById('destination-fields');
    const destinationUnit = document.getElementById('destination_establishment_id');
    const destinationSector = document.getElementById('destination_sector_id');

    function updateDestinationFields() {
        const enabled = checkbox.checked;

        fields.hidden = !enabled;
        fields.disabled = !enabled;

        destinationUnit.required = enabled;
        destinationSector.required = enabled;
    }

    checkbox.addEventListener('change', updateDestinationFields);
    updateDestinationFields();

    form.addEventListener('submit', () => {
        const button = document.getElementById('submit-button');

        button.disabled = true;
        button.textContent = 'Registrando…';
    });
</script>
        @endif
    </main>
</body>
</html>