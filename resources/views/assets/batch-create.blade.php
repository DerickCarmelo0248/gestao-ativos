<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrada em lote — Gestão de Ativos</title>
</head>
<body>
    <main>
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <h1>Entrada de equipamentos por intervalo</h1>

        <p>
            Todos os equipamentos terão o mesmo item e unidade.
            Cada patrimônio será cadastrado separadamente.
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
                É necessário ter um item ativo com controle individual
                e uma unidade ativa para registrar a entrada.
            </p>
        @else
            <form
                id="batch-form"
                method="POST"
                action="{{ route('assets.batch.store') }}"
            >
                @csrf

                <p>
                    <label for="item_id">Item/modelo</label><br>
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
                    <label for="patrimony_start">Patrimônio inicial</label><br>
                    <input
                        id="patrimony_start"
                        name="patrimony_start"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{1,9}"
                        maxlength="9"
                        value="{{ old('patrimony_start') }}"
                        required
                    >
                </p>

                <p>
                    <label for="patrimony_end">Patrimônio final</label><br>
                    <input
                        id="patrimony_end"
                        name="patrimony_end"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{1,9}"
                        maxlength="9"
                        value="{{ old('patrimony_end') }}"
                        required
                    >
                </p>

                <p>
                    Limite de 500 equipamentos.
                    Os números de série ficarão em branco.
                </p>

                <button type="button" id="preview-button">
                    Conferir intervalo
                </button>

                <p id="preview-error" role="alert"></p>

                <section id="preview" hidden>
                    <h2>Confira a entrada</h2>
                    <p id="preview-summary" aria-live="polite"></p>

                    <label for="preview-list">Patrimônios do lote</label><br>
                    <textarea
                        id="preview-list"
                        rows="8"
                        cols="30"
                        readonly
                    ></textarea>

                    <p>
                        <label>
                            <input id="confirm-batch" type="checkbox">
                            Conferi os patrimônios e confirmo a entrada.
                        </label>
                    </p>

                    <button id="submit-button" type="submit" disabled>
                        Registrar entrada
                    </button>
                </section>

                <noscript>
                    <p>Ative o JavaScript para conferir o intervalo.</p>
                </noscript>
            </form>

            <script>
                const form = document.getElementById('batch-form');
                const item = document.getElementById('item_id');
                const unit = document.getElementById('unit_id');
                const start = document.getElementById('patrimony_start');
                const end = document.getElementById('patrimony_end');
                const preview = document.getElementById('preview');
                const error = document.getElementById('preview-error');
                const confirmation = document.getElementById('confirm-batch');
                const submit = document.getElementById('submit-button');

                let reviewedValues = null;

                function currentValues() {
                    return JSON.stringify([
                        item.value, unit.value, start.value, end.value
                    ]);
                }

                function invalidatePreview() {
                    reviewedValues = null;
                    preview.hidden = true;
                    confirmation.checked = false;
                    submit.disabled = true;
                    error.textContent = '';
                }

                for (const field of [item, unit, start, end]) {
                    field.addEventListener('input', invalidatePreview);
                    field.addEventListener('change', invalidatePreview);
                }

                document.getElementById('preview-button')
                    .addEventListener('click', () => {
                        invalidatePreview();

                        if (!form.reportValidity()) {
                            return;
                        }

                        const first = start.value;
                        const last = end.value;

                        if (
                            (first.startsWith('0') || last.startsWith('0')) &&
                            first.length !== last.length
                        ) {
                            error.textContent =
                                'Com zeros à esquerda, use o mesmo número de dígitos nos dois campos.';
                            return;
                        }

                        const quantity = Number(last) - Number(first) + 1;

                        if (quantity < 1 || quantity > 500) {
                            error.textContent =
                                'Informe um intervalo crescente com até 500 equipamentos.';
                            return;
                        }

                        const patrimonies = [];

                        for (
                            let number = Number(first);
                            number <= Number(last);
                            number++
                        ) {
                            patrimonies.push(
                                String(number).padStart(first.length, '0')
                            );
                        }

                        document.getElementById('preview-summary').textContent =
                            `${quantity} equipamentos — ` +
                            `${item.selectedOptions[0].textContent.trim()} — ` +
                            `${unit.selectedOptions[0].textContent.trim()}`;

                        document.getElementById('preview-list').value =
                            patrimonies.join('\n');

                        reviewedValues = currentValues();
                        preview.hidden = false;
                    });

                confirmation.addEventListener('change', () => {
                    submit.disabled = !confirmation.checked;
                });

                form.addEventListener('submit', (event) => {
                    if (
                        !confirmation.checked ||
                        reviewedValues !== currentValues()
                    ) {
                        event.preventDefault();
                        error.textContent =
                            'Confira o intervalo e confirme a entrada antes de enviar.';
                        return;
                    }

                    submit.disabled = true;
                    submit.textContent = 'Registrando…';
                });
            </script>
        @endif
    </main>
</body>
</html>