@extends('layouts.app')

@section('title', 'Usuários')

@section('content')
    <div class="module-page">
        <div class="page-heading">
            <p class="eyebrow">ADMINISTRAÇÃO / ACESSOS</p>
            <h1>Usuários do sistema</h1>
            <p>Cadastre as pessoas autorizadas a acessar o sistema.</p>
        </div>

        <h2>Cadastrar usuário</h2>

        <form
            id="user-form"
            method="POST"
            action="{{ route('users.store') }}"
        >
            @csrf

            <p>
                <label for="name">Nome completo</label><br>
                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="255"
                    value="{{ old('name') }}"
                    autocomplete="name"
                    required
                >
            </p>

            <p>
                <label for="email">E-mail</label><br>
                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="255"
                    value="{{ old('email') }}"
                    autocomplete="off"
                    required
                >
            </p>

            <p>
                <label for="role">Perfil de acesso</label><br>
                <select id="role" name="role" required>
                    <option
                        value="operator"
                        @selected(old('role', 'operator') === 'operator')
                    >
                        Operador
                    </option>

                    <option
                        value="admin"
                        @selected(old('role') === 'admin')
                    >
                        Administrador
                    </option>
                </select>
            </p>

            <p>
                Administradores também podem cadastrar usuários e itens.
                Operadores acessam consultas e movimentações.
            </p>

            <p>
                <label for="password">Senha</label><br>
                <input
                    id="password"
                    name="password"
                    type="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
                <small class="form-help">
                    Use pelo menos 8 caracteres.
                </small>
            </p>

            <p>
                <label for="password_confirmation">
                    Confirmar senha
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

            <button id="user-submit" type="submit">
                Cadastrar usuário
            </button>
        </form>

        <hr>

        <h2>Usuários cadastrados</h2>
        <p>Total: {{ $users->total() }}</p>

        <div
            class="table-wrapper"
            tabindex="0"
            role="region"
            aria-label="Usuários cadastrados"
        >
            <table>
                <thead>
                    <tr>
                        <th scope="col">Nome</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Perfil</th>
                        <th scope="col">Cadastrado em</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($user->role === 'admin')
                                    <span class="badge blue">
                                        Administrador
                                    </span>
                                @elseif ($user->role === 'operator')
                                    <span class="badge green">
                                        Operador
                                    </span>
                                @else
                                    {{ $user->role }}
                                @endif
                            </td>
                            <td>
                                {{ $user->created_at?->copy()
                                    ->timezone('America/Sao_Paulo')
                                    ->format('d/m/Y') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                Nenhum usuário encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <nav aria-label="Paginação de usuários">
                @if (! $users->onFirstPage())
                    <a href="{{ $users->previousPageUrl() }}">Anterior</a>
                @endif

                <span>
                    Página {{ $users->currentPage() }}
                    de {{ $users->lastPage() }}
                </span>

                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}">Próxima</a>
                @endif
            </nav>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('user-form');

            form.addEventListener('submit', () => {
                const button = document.getElementById('user-submit');

                button.disabled = true;
                button.textContent = 'Cadastrando…';
            });
        })();
    </script>
@endpush