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

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sair</button>
        </form>
    </main>
</body>
</html>