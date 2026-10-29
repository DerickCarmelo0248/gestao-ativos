@extends('layouts.app')
@section('title', 'Editar nome do usuário')
@section('content')
<div class="module-page">
    <a href="{{ route('users.index') }}">Voltar aos usuários</a>
    <div class="page-heading"><h1>Editar nome do usuário</h1></div>
    <p>{{ $user->email }}</p>
    <form method="POST" action="{{ route('users.name.update', $user) }}">
        @csrf
        @method('PUT')
        <p><label for="name">Nome completo</label>
        <input id="name" name="name" type="text" maxlength="255" value="{{ old('name', $user->name) }}" required autocomplete="name"></p>
        <button type="submit">Salvar nome</button>
        <a href="{{ route('users.index') }}">Cancelar</a>
    </form>
</div>
@endsection
