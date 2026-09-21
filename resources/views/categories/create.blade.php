<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova categoria — Gestão de Ativos</title>
</head>
<body>
    <main>
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <h1>Cadastrar categoria</h1>

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

        <form method="POST" action="{{ route('categories.store') }}">
            @csrf

            <p>
                <label for="name">Nome da categoria</label><br>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    maxlength="100"
                    required
                    autofocus
                >
            </p>

            <p>
                <label for="description">Descrição (opcional)</label><br>
                <textarea
                    id="description"
                    name="description"
                    maxlength="2000"
                    rows="4"
                >{{ old('description') }}</textarea>
            </p>

            <button type="submit">Cadastrar categoria</button>
        </form>
    </main>
</body>
</html>