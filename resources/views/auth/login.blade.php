<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Entrar — Gestão de Ativos</title><link rel="stylesheet" href="{{ asset('css/app.css').'?v=3' }}"></head><body class="login-page"><main class="login-shell"><section class="login-intro"><span class="login-brand">GESTÃO DE ATIVOS DE TI</span><h2>Organização em cada etapa.</h2><p>Do recebimento ao descarte, acompanhe os equipamentos e materiais da sua equipe.</p><div class="login-decoration" aria-hidden="true"><span>01 / Estoque</span><span>02 / Movimentações</span><span>03 / Descarte</span></div><small>Acesso interno · Votuporanga / Rio Preto / Roseira / Guarulhos</small></section><section class="module-page login-card">
    
        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / ACESSO</p><h1>Gestão de Ativos</h1></div>
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
    
</section></main></body></html>