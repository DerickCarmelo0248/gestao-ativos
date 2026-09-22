<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Equipamento {{ $asset->patrimony }} — Gestão de Ativos</title>
</head>
<body>
    <main>
        <a href="{{ route('assets.index') }}">
            Voltar aos equipamentos
        </a>

        <h1>Patrimônio {{ $asset->patrimony }}</h1>

        <dl>
            <dt>Item/modelo</dt>
            <dd>{{ $asset->item->name }}</dd>

            <dt>Número de série</dt>
            <dd>{{ $asset->serial_number ?? 'Não informado' }}</dd>

            <dt>Unidade atual</dt>
            <dd>{{ $asset->unit->name }}</dd>

            <dt>Situação atual</dt>
            <dd>{{ $statuses[$asset->status] ?? $asset->status }}</dd>
        </dl>

        <h2>Histórico de movimentações</h2>

        <table>
            <thead>
                <tr>
                    <th scope="col">Data</th>
                    <th scope="col">Operação</th>
                    <th scope="col">Unidade da movimentação</th>
                    <th scope="col">Responsável</th>
                    <th scope="col">Lote</th>
                    <th scope="col">Observação</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td>
                            {{ $movement->created_at->copy()->timezone('America/Sao_Paulo')->format('d/m/Y') }}
                        </td>
                        <td>
                            {{ $movementTypes[$movement->type] ?? $movement->type }}
                        </td>
                        <td>{{ $movement->unit->name }}</td>
                        <td>{{ $movement->user->name }}</td>
                        <td>{{ $movement->batch_id }}</td>
                        <td>{{ $movement->notes ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            Nenhuma movimentação registrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($movements->hasPages())
            <nav aria-label="Paginação do histórico">
                @if (! $movements->onFirstPage())
                    <a href="{{ $movements->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $movements->currentPage() }}
                    de {{ $movements->lastPage() }}
                </span>

                @if ($movements->hasMorePages())
                    <a href="{{ $movements->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    </main>
</body>
</html>