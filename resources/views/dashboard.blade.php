<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel — Gestão de Ativos</title>
</head>
<body>
    <main>
        <h1>Gestão de Ativos</h1>

        <p>Bem-vindo(a), {{ auth()->user()->name }}.</p>

        <p>Você está autenticado no sistema.</p>

        @can('create', \App\Models\Category::class)
    <p>
        <a href="{{ route('categories.create') }}">
            Cadastrar categoria
        </a>
    </p>
@endcan

@can('viewAny', \App\Models\Category::class)
    <p>
        <a href="{{ route('categories.index') }}">
            Consultar categorias
        </a>
    </p>
@endcan

@can('create', \App\Models\Item::class)
    <p>
        <a href="{{ route('items.create') }}">
            Cadastrar item
        </a>
    </p>
@endcan

@can('create', \App\Models\Asset::class)
    <p>
        <a href="{{ route('assets.batch.create') }}">
            Registrar equipamentos por intervalo
        </a>
    </p>
@endcan

@can('viewAny', \App\Models\Asset::class)
    <p>
        <a href="{{ route('assets.index') }}">
            Consultar equipamentos
        </a>
    </p>
@endcan

@can('recordEntry', \App\Models\StockBalance::class)
    <p>
        <a href="{{ route('stock-entries.create') }}">
            Registrar entrada por quantidade
        </a>
    </p>
@endcan

@can('viewAny', \App\Models\StockBalance::class)
    <p>
        <a href="{{ route('stock-balances.index') }}">
            Consultar estoque por quantidade
        </a>
    </p>
@endcan

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sair</button>
        </form>
    </main>
</body>
</html>