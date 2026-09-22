<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Estoque por quantidade — Gestão de Ativos</title>
</head>
<body>
    <main>
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <h1>Estoque por quantidade</h1>

        @can('recordEntry', \App\Models\StockBalance::class)
            <p>
                <a href="{{ route('stock-entries.create') }}">
                    Registrar entrada
                </a>
            </p>
        @endcan

        <p>
            Cada linha mostra o saldo de um item em uma unidade.
        </p>

        <table>
            <thead>
                <tr>
                    <th scope="col">Código do item</th>
                    <th scope="col">Item</th>
                    <th scope="col">Unidade</th>
                    <th scope="col">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($balances as $balance)
                    <tr>
                        <td>{{ $balance->item->code }}</td>
                        <td>{{ $balance->item->name }}</td>
                        <td>{{ $balance->unit->name }}</td>
                        <td>{{ $balance->quantity }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            Nenhum saldo registrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($balances->hasPages())
            <nav aria-label="Paginação do estoque">
                @if (! $balances->onFirstPage())
                    <a href="{{ $balances->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $balances->currentPage() }}
                    de {{ $balances->lastPage() }}
                </span>

                @if ($balances->hasMorePages())
                    <a href="{{ $balances->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    </main>
</body>
</html>