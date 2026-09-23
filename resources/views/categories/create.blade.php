@extends('layouts.app')
@section('title', 'Categorias')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Cadastrar categoria</h1></div>

        

        

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
    

</div>
@endsection
