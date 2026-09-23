<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pendências de reposição — Gestão de Ativos</title>
</head>
<body>
    <main>
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <h1>Pendências de reposição</h1>

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

        <p>
            A reposição retornará ao estoque de origem.
            O custo será atribuído ao estabelecimento e setor
            que receberam o material na saída.
        </p>

        <p>Total de pendências: {{ $replacements->total() }}</p>

        <table>
            <thead>
                <tr>
                    <th scope="col">Data</th>
                    <th scope="col">Saída</th>
                    <th scope="col">Item</th>
                    <th scope="col">Quantidade a repor</th>
                    <th scope="col">Estoque de origem</th>
                    <th scope="col">Estabelecimento responsável pelo custo</th>
                    <th scope="col">Setor responsável pelo custo</th>
                    <th scope="col">Técnico</th>
                    <th scope="col">Chamado</th>
                    <th scope="col">Situação</th>
                    <th scope="col">Reposição</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($replacements as $replacement)
                    <tr>
                        <td>
                            {{ $replacement->created_at->copy()
                                ->timezone('America/Sao_Paulo')
                                ->format('d/m/Y') }}
                        </td>

                        <td>#{{ $replacement->stock_movement_id }}</td>

                        <td>
                            {{ $replacement->movement->item->code }}
                            — {{ $replacement->movement->item->name }}
                        </td>

                        <td>{{ $replacement->quantity }}</td>

                        <td>{{ $replacement->movement->unit->name }}</td>

                        <td>
                            {{ $replacement->destinationEstablishment->code }}
                            - {{ $replacement->destinationEstablishment->name }}
                        </td>

                        <td>{{ $replacement->destinationSector->name }}</td>

                        <td>
                            {{ $replacement->movement->technician?->name
                                ?? 'Não informado' }}
                        </td>

                        <td>{{ $replacement->movement->ticket_number }}</td>

                        <td>
                            {{ $statuses[$replacement->status]
                                ?? $replacement->status }}
                        </td>

                        <td>
                            @can('complete', $replacement)
                                <form
                                    method="POST"
                                    action="{{ route('stock-replacements.complete', $replacement) }}"
                                    class="replacement-form"
                                >
                                    @csrf

                                    <p>
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="received"
                                                value="1"
                                                required
                                            >
                                            Confirmo o recebimento de
                                            {{ $replacement->quantity }} unidades
                                            no estoque de {{ $replacement->movement->unit->name }}.
                                        </label>
                                    </p>

                                    <button type="submit">Repor</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            Nenhuma reposição pendente.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($replacements->hasPages())
            <nav aria-label="Paginação das reposições">
                @if (! $replacements->onFirstPage())
                    <a href="{{ $replacements->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $replacements->currentPage() }}
                    de {{ $replacements->lastPage() }}
                </span>

                @if ($replacements->hasMorePages())
                    <a href="{{ $replacements->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    </main>
    <script>
    document.querySelectorAll('.replacement-form').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');

            button.disabled = true;
            button.textContent = 'Registrando…';
        });
    });
</script>
</body>
</html>