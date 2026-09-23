@extends('layouts.app')
@section('title', 'Estoque por quantidade')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Estoque por quantidade</h1></div>

        @can('recordEntry', \App\Models\StockBalance::class)
            <p>
                <a href="{{ route('stock-entries.create') }}">
                    Registrar entrada
                </a>
            </p>
        @endcan

        <p>
            Cada linha mostra o saldo de um item em uma unidade.
        </p>

        <div class="table-wrapper" tabindex="0" role="region" aria-label="Tabela de registros"><table>
            <thead>
                <tr>
                    <th scope="col">Código do item</th>
                    <th scope="col">Item</th>
                    <th scope="col">Unidade</th>
                    <th scope="col">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($balances as $balance)
                    <tr>
                        <td>{{ $balance->item->code }}</td>
                        <td>
                            <a href="{{ route('stock-balances.show', $balance) }}">
                                {{ $balance->item->name }}
                            </a>
                        </td>
                        <td>{{ $balance->unit->name }}</td>
                        <td>{{ $balance->quantity }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            Nenhum saldo registrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table></div>

        @if ($balances->hasPages())
            <nav aria-label="Paginação do estoque">
                @if (! $balances->onFirstPage())
                    <a href="{{ $balances->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $balances->currentPage() }}
                    de {{ $balances->lastPage() }}
                </span>

                @if ($balances->hasMorePages())
                    <a href="{{ $balances->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    

</div>
@endsection
