@extends('layouts.app')
@section('title', 'Equipamentos')
@section('content')
<div class="module-page">

    
        <a href="{{ route('assets.index') }}">
            Voltar aos equipamentos
        </a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Patrimônio {{ $asset->patrimony }}</h1></div>

        <dl>
            <dt>Item/modelo</dt>
            <dd>{{ $asset->item->name }}</dd>

            <dt>Número de série</dt>
            <dd>{{ $asset->serial_number ?? 'Não informado' }}</dd>

            <dt>Unidade de estoque responsável</dt>
            <dd>{{ $asset->unit->name }}</dd>

            <dt>Situação atual</dt>
            <dd>{{ $statuses[$asset->status] ?? $asset->status }}</dd>
        </dl>

        <h2>Histórico de movimentações</h2>

        <div class="table-wrapper" tabindex="0" role="region" aria-label="Tabela de registros"><table>
            <thead>
                <tr>
                    <th scope="col">Data</th>
                    <th scope="col">Operação</th>
                    <th scope="col">Unidade da movimentação</th>
                    <th scope="col">Destino informado na movimentação</th>
                    <th scope="col">Setor de destino</th>
                    <th scope="col">Responsável</th>
                    <th scope="col">Lote</th>
                    <th scope="col">Observação</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td>
                            {{ $movement->created_at->copy()->timezone('America/Sao_Paulo')->format('d/m/Y') }}
                        </td>
                        <td>
                            {{ $movementTypes[$movement->type] ?? $movement->type }}
                        </td>
                        <td>{{ $movement->unit->name }}</td>
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
                        <td>{{ $movement->batch_id }}</td>
                        <td>{{ $movement->notes ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
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
