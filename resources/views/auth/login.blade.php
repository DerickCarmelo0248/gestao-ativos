<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — Gestão de Ativos</title>
</head>
<body>
    <main>
        <h1>Gestão de Ativos</h1>
        <p>Entre com a conta fornecida pela equipe.</p>

        @if ($errors->any())
            <div role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <p>
                <label for="email">E-mail</label><br>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    maxlength="255"
                    required
                    autofocus
                >
            </p>

            <p>
                <label for="password">Senha</label><br>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </p>

            <button type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>