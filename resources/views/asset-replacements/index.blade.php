@extends('layouts.app')
@section('title', 'Reposição de equipamentos')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Reposições pendentes de equipamentos</h1></div>

        

        <p>
            Cada pendência corresponde a um equipamento.
            O patrimônio exibido identifica o equipamento que saiu,
            não o que será comprado.
        </p>

        <p>Total de pendências: {{ $replacements->total() }}</p>

        <div class="table-wrapper" tabindex="0" role="region" aria-label="Tabela de registros"><table>
            <thead>
                <tr>
                    <th scope="col">Data</th>
                    <th scope="col">Saída</th>
                    <th scope="col">Patrimônio que saiu</th>
                    <th scope="col">Item</th>
                    <th scope="col">Estoque de origem</th>
                    <th scope="col">Estabelecimento responsável pelo custo</th>
                    <th scope="col">Setor responsável pelo custo</th>
                    <th scope="col">Técnico</th>
                    <th scope="col">Chamado</th>
                    <th scope="col">Situação</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($replacements as $replacement)
                    <tr>
                        <td>
                            {{ $replacement->created_at->copy()
                                ->timezone('America/Sao_Paulo')
                                ->format('d/m/Y') }}
                        </td>

                        <td>#{{ $replacement->asset_movement_id }}</td>

                        <td>
                            <a href="{{ route('assets.show', $replacement->movement->asset) }}">
                                {{ $replacement->movement->asset->patrimony }}
                            </a>
                        </td>

                        <td>
                            {{ $replacement->movement->asset->item->name }}
                        </td>

                        <td>{{ $replacement->movement->unit->name }}</td>

                        <td>
                            {{ $replacement->destinationEstablishment->code }}
                            - {{ $replacement->destinationEstablishment->name }}
                        </td>

                        <td>{{ $replacement->destinationSector->name }}</td>

                        <td>
                            {{ $replacement->movement->technician?->name
                                ?? 'Não informado' }}
                        </td>

                        <td>{{ $replacement->movement->ticket_number }}</td>

                        <td>
                            {{ $statuses[$replacement->status]
                                ?? $replacement->status }}
                        </td>

                        <td>
                            @can('complete', $replacement)
                                <a href="{{ route('asset-replacements.edit', $replacement) }}">
                                    Registrar reposição
                                </a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            Nenhuma reposição de equipamento pendente.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table></div>

        @if ($replacements->hasPages())
            <nav aria-label="Paginação das reposições de equipamentos">
                @if (! $replacements->onFirstPage())
                    <a href="{{ $replacements->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $replacements->currentPage() }}
                    de {{ $replacements->lastPage() }}
                </span>

                @if ($replacements->hasMorePages())
                    <a href="{{ $replacements->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    

</div>
@endsection
