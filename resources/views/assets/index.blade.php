<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Equipamentos — Gestão de Ativos</title>
</head>
<body>
    <main>
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <h1>Equipamentos</h1>

        @can('create', \App\Models\Asset::class)
            <p>
                <a href="{{ route('assets.batch.create') }}">
                    Registrar entrada por intervalo
                </a>
            </p>
        @endcan

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="GET" action="{{ route('assets.index') }}">
            <p>
                <label for="patrimony">Patrimônio completo</label><br>
                <input
                    id="patrimony"
                    name="patrimony"
                    type="text"
                    maxlength="50"
                    value="{{ $filters['patrimony'] ?? '' }}"
                >
            </p>

            <p>
                <label for="unit_id">Unidade</label><br>
                <select id="unit_id" name="unit_id">
                    <option value="">Todas as unidades</option>

                    @foreach ($units as $unit)
                        <option
                            value="{{ $unit->id }}"
                            @selected(
                                (string) ($filters['unit_id'] ?? '') ===
                                (string) $unit->id
                            )
                        >
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
            </p>

            <button type="submit">Filtrar</button>
            <a href="{{ route('assets.index') }}">Limpar filtros</a>
        </form>

        <p>Total encontrado: {{ $assets->total() }}</p>

        <table>
            <thead>
                <tr>
                    <th scope="col">Patrimônio</th>
                    <th scope="col">Item/modelo</th>
                    <th scope="col">Número de série</th>
                    <th scope="col">Unidade</th>
                    <th scope="col">Situação</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assets as $asset)
                    <tr>
                        <td>
    <a href="{{ route('assets.show', $asset) }}">
        {{ $asset->patrimony }}
    </a>
</td>
                        <td>{{ $asset->item->name }}</td>
                        <td>{{ $asset->serial_number ?? '—' }}</td>
                        <td>{{ $asset->unit->name }}</td>
                        <td>
                            {{ $statuses[$asset->status] ?? $asset->status }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            Nenhum equipamento encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($assets->hasPages())
            <nav aria-label="Paginação dos equipamentos">
                @if (! $assets->onFirstPage())
                    <a href="{{ $assets->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $assets->currentPage() }}
                    de {{ $assets->lastPage() }}
                </span>

                @if ($assets->hasMorePages())
                    <a href="{{ $assets->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    </main>
</body>
</html>