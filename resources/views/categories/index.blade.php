@extends('layouts.app')
@section('title', 'Categorias')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Categorias</h1></div>

        @can('create', \App\Models\Category::class)
            <p>
                <a href="{{ route('categories.create') }}">
                    Cadastrar categoria
                </a>
            </p>
        @endcan

        <p>Total: {{ $categories->total() }}</p>

        <div class="table-wrapper" tabindex="0" role="region" aria-label="Tabela de registros"><table>
            <thead>
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">Descrição</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->description ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">Nenhuma categoria cadastrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table></div>

        @if ($categories->hasPages())
            <nav aria-label="Paginação de categorias">
                @if (! $categories->onFirstPage())
                    <a href="{{ $categories->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $categories->currentPage() }}
                    de {{ $categories->lastPage() }}
                </span>

                @if ($categories->hasMorePages())
                    <a href="{{ $categories->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    

</div>
@endsection
