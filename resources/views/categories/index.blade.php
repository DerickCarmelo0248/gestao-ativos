<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Categorias — Gestão de Ativos</title>
</head>
<body>
    <main>
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <h1>Categorias</h1>

        @can('create', \App\Models\Category::class)
            <p>
                <a href="{{ route('categories.create') }}">
                    Cadastrar categoria
                </a>
            </p>
        @endcan

        <p>Total: {{ $categories->total() }}</p>

        <table>
            <thead>
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">Descrição</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->description ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">Nenhuma categoria cadastrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($categories->hasPages())
            <nav aria-label="Paginação de categorias">
                @if (! $categories->onFirstPage())
                    <a href="{{ $categories->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $categories->currentPage() }}
                    de {{ $categories->lastPage() }}
                </span>

                @if ($categories->hasMorePages())
                    <a href="{{ $categories->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    </main>
</body>
</html>