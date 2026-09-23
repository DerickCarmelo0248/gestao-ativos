@extends('layouts.app')
@section('title', 'Catálogo de itens')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Cadastrar item</h1></div>

        <p>
            Este cadastro define o item no catálogo.
            Quantidades e equipamentos serão registrados depois.
        </p>

        

        

        @if ($categories->isEmpty())
            <p>Cadastre uma categoria antes de cadastrar itens.</p>

            <a href="{{ route('categories.create') }}">
                Cadastrar categoria
            </a>
        @else
            <form method="POST" action="{{ route('items.store') }}">
                @csrf

                <p>
                    <label for="category_id">Categoria</label><br>
                    <select id="category_id" name="category_id" required>
                        <option value="">Selecione</option>

                        @foreach ($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(
                                    (string) old('category_id') ===
                                    (string) $category->id
                                )
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </p>

                <p>
                    <label for="code">Código interno</label><br>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        value="{{ old('code') }}"
                        maxlength="50"
                        placeholder="MON-001"
                        required
                    >
                </p>

                <p>
                    <label for="name">Nome</label><br>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        maxlength="150"
                        required
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

                <p>
                    <label for="tracking_type">Tipo de controle</label><br>
                    <select
                        id="tracking_type"
                        name="tracking_type"
                        required
                    >
                        <option value="">Selecione</option>

                        <option
                            value="quantity"
                            @selected(old('tracking_type') === 'quantity')
                        >
                            Por quantidade
                        </option>

                        <option
                            value="individual"
                            @selected(old('tracking_type') === 'individual')
                        >
                            Por equipamento individual
                        </option>
                    </select>
                </p>

                <button type="submit">Cadastrar item</button>
            </form>
        @endif
    

</div>
@endsection
