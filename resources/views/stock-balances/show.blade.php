@extends('layouts.app')
@section('title', 'Estoque por quantidade')
@section('content')
<div class="module-page">

    
        <a href="{{ route('stock-balances.index') }}">
            Voltar ao estoque
        </a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>{{ $stockBalance->item->name }}</h1></div>

        <dl>
            <dt>Código do item</dt>
            <dd>{{ $stockBalance->item->code }}</dd>

            <dt>Unidade do estoque</dt>
            <dd>{{ $stockBalance->unit->name }}</dd>

            <dt>Saldo atual</dt>
            <dd>{{ $stockBalance->quantity }}</dd>
        </dl>

        <h2>Histórico de movimentações</h2>

        <div class="table-wrapper" tabindex="0" role="region" aria-label="Tabela de registros"><table>
            <thead>
                <tr>
                    <th scope="col">Data</th>
                    <th scope="col">Operação</th>
                    <th scope="col">Quantidade movimentada</th>
                    <th scope="col">Destino previsto</th>
                    <th scope="col">Setor de destino</th>
                    <th scope="col">Responsável</th>
                    <th scope="col">Observação</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td>
                            {{ $movement->created_at->copy()
                                ->timezone('America/Sao_Paulo')
                                ->format('d/m/Y') }}
                        </td>

                        <td>
                            {{ $movementTypes[$movement->type]
                                ?? $movement->type }}
                        </td>

                        <td>{{ $movement->quantity }}</td>

                        <td>
                            @if ($movement->destinationEstablishment)
                                {{ $movement->destinationEstablishment->code }}
                                - {{ $movement->destinationEstablishment->name }}
                            @elseif ($movement->legacyDestinationUnit)
                                {{ $movement->legacyDestinationUnit->name }}
                                (registro antigo por unidade)
                            @else
                                Não informado
                            @endif
                        </td>

                        <td>
                            {{ $movement->destinationSector?->name
                                ?? $movement->destination_sector
                                ?? 'Não informado' }}
                        </td>

                        <td>{{ $movement->user->name }}</td>
                        <td>{{ $movement->notes ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            Nenhuma movimentação registrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table></div>

        @if ($movements->hasPages())
            <nav aria-label="Paginação do histórico">
                @if (! $movements->onFirstPage())
                    <a href="{{ $movements->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $movements->currentPage() }}
                    de {{ $movements->lastPage() }}
                </span>

                @if ($movements->hasMorePages())
                    <a href="{{ $movements->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    

</div>
@endsection
