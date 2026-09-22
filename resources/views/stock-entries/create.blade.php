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
                document.getElementById('stock-entry-form')
                    .addEventListener('submit', () => {
                        const button = document.getElementById('submit-button');

                        button.disabled = true;
                        button.textContent = 'Registrando…';
                    });
            </script>
        @endif
    </main>
</body>
</html>