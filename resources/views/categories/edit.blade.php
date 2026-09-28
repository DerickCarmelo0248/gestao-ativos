@extends('layouts.app')
@section('title', 'Editar categoria')
@section('content')
<div class="module-page">
    <a href="{{ route('categories.index') }}">Voltar às categorias</a>
    <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / CADASTROS</p><h1>Editar categoria</h1></div>
    <form method="POST" action="{{ route('categories.update', $category) }}">
        @csrf
        @method('PUT')
        <p>
            <label for="name">Nome da categoria</label><br>
            <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" maxlength="100" required autofocus>
        </p>
        <p>
            <label for="description">Descrição (opcional)</label><br>
            <textarea id="description" name="description" maxlength="2000" rows="4">{{ old('description', $category->description) }}</textarea>
        </p>
        <button type="submit">Salvar alterações</button>
        <a href="{{ route('categories.index') }}">Cancelar</a>
    </form>
</div>
@endsection
