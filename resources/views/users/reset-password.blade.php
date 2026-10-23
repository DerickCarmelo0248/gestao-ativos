@extends('layouts.app')
@section('title', 'Redefinir senha')
@section('content')
<div class="module-page">
    <a href="{{ route('users.index') }}">Voltar aos usuários</a>
    <div class="page-heading"><h1>Redefinir senha</h1></div>
    <p>Usuário: <strong>{{ $user->name }}</strong> — {{ $user->email }}</p>
    <p>Defina uma nova senha e informe-a ao usuário por um canal privado.</p>
    <form method="POST" action="{{ route('users.password.reset', $user) }}">
        @csrf
        @method('PUT')
        <p><label for="current_password">Sua senha de administrador</label>
        <input id="current_password" type="password" name="current_password" autocomplete="current-password" required></p>
        <p><label for="password">Nova senha do usuário</label>
        <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required>
        <small>Use pelo menos 8 caracteres.</small></p>
        <p><label for="password_confirmation">Confirmar nova senha</label>
        <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></p>
        <button type="submit">Redefinir senha</button>
    </form>
</div>
@endsection
