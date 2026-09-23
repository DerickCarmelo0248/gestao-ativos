@extends('layouts.app')

@section('title', 'Minha conta')

@section('content')
    <div class="module-page">
        <div class="page-heading">
            <p class="eyebrow">CONTA / SEGURANÇA</p>
            <h1>Minha conta</h1>
            <p>Consulte seus dados e altere sua senha de acesso.</p>
        </div>

        <dl>
            <dt>Nome</dt>
            <dd>{{ auth()->user()->name }}</dd>

            <dt>E-mail</dt>
            <dd>{{ auth()->user()->email }}</dd>

            <dt>Perfil</dt>
            <dd>
                {{ [
                    'admin' => 'Administrador',
                    'operator' => 'Operador',
                ][auth()->user()->role] ?? 'Não identificado' }}
            </dd>
        </dl>

        <h2>Alterar minha senha</h2>

        <form
            id="password-form"
            method="POST"
            action="{{ route('account.password.update') }}"
        >
            @csrf
            @method('PUT')

            <p>
                <label for="current_password">Senha atual</label><br>

                <input
                    id="current_password"
                    name="current_password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </p>

            <p>
                Para confirmar a alteração, informe sua senha atual
                e escolha uma nova senha.
            </p>

            <p>
                <label for="password">Nova senha</label><br>

                <input
                    id="password"
                    name="password"
                    type="password"
                    minlength="8"
                    autocomplete="new-password"
                    aria-describedby="password-help"
                    required
                >

                <small id="password-help" class="form-help">
                    Use pelo menos 8 caracteres e uma senha diferente da atual.
                </small>
            </p>

            <p>
                <label for="password_confirmation">
                    Confirmar nova senha
                </label><br>

                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </p>

            <button id="password-submit" type="submit">
                Salvar nova senha
            </button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('password-form');

            form.addEventListener('submit', () => {
                const button = document.getElementById('password-submit');

                button.disabled = true;
                button.textContent = 'Salvando…';
            });
        })();
    </script>
@endpush