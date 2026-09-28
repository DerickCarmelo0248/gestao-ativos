@extends('layouts.app')
@section('title', 'Editar item')
@section('content')
<div class="module-page">

    
        <a href="{{ route('items.index') }}">Voltar à consulta de itens</a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Editar item</h1></div>

        <p>
            Atualize os dados do item e o estoque mínimo por unidade.
            O tipo de controle é mantido para preservar a coerência do estoque.
        </p>

        

        

        @if ($categories->isEmpty())
            <p>Cadastre uma categoria antes de cadastrar itens.</p>

            <a href="{{ route('categories.create') }}">
                Cadastrar categoria
            </a>
        @else
            <form method="POST" action="{{ route('items.update', $item) }}">
                @csrf
                @method('PUT')

                <p>
                    <label for="category_id">Categoria</label><br>
                    <select id="category_id" name="category_id" required>
                        <option value="">Selecione</option>

                        @foreach ($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(
                                    (string) old('category_id', $item->category_id) ===
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
                        value="{{ old('code', $item->code) }}"
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
                        value="{{ old('name', $item->name) }}"
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
                    >{{ old('description', $item->description) }}</textarea>
                </p>

                <p>
                    <label for="tracking_type">Tipo de controle</label><br>
<input type="hidden" name="tracking_type" value="{{ $item->tracking_type }}"><span>{{ $item->tracking_type === 'individual' ? 'Por equipamento individual' : 'Por quantidade' }}</span>
                </p>


                <p>
                    <label for="minimum_stock_enabled">Controlar estoque mínimo?</label><br>
                    <select id="minimum_stock_enabled" name="minimum_stock_enabled">
                        <option value="1" @selected(old('minimum_stock_enabled', $item->minimum_stock_enabled) == 1)>Sim</option>
                        <option value="0" @selected(old('minimum_stock_enabled', $item->minimum_stock_enabled) == 0)>Não — avisar somente quando zerar</option>
                    </select><br>
                    <label for="minimum_stock">Estoque mínimo por unidade</label><br>
                    <input id="minimum_stock" name="minimum_stock" type="number" min="0" max="2147483647" step="1" value="{{ old('minimum_stock', $item->minimum_stock) }}" required>
                    <small>O painel avisa quando o saldo disponível de cada unidade for igual ou inferior ao mínimo. Para equipamentos, são contados apenas os disponíveis. Com o controle desativado, o mínimo é ignorado e o item aparece apenas na consulta de saldos zerados quando não houver saldo disponível.</small>
                </p>
                <button type="submit">Salvar alterações</button>
            </form>
        @endif
    

</div>
@endsection
