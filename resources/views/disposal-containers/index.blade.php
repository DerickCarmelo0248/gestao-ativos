@extends('layouts.app')
@section('title', 'Caçambas de descarte')
@section('content')
<div class="module-page">

    
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>

        <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / OPERAÇÕES</p><h1>Caçambas de descarte</h1></div>

        

        

        @can('create', \App\Models\DisposalContainer::class)
            <section>
                <h2>Abrir caçamba</h2>

                @if ($units->isEmpty())
                    <p>
                        Não há unidade disponível para abertura.
                        Cada unidade pode ter somente uma caçamba aberta
                        e precisa estar ativa.
                    </p>
                @else
                    <form
                        id="open-container-form"
                        method="POST"
                        action="{{ route('disposal-containers.store') }}"
                    >
                        @csrf

                        <p>
                            <label for="unit_id">Unidade da caçamba</label><br>

                            <select id="unit_id" name="unit_id" required>
                                <option value="">Selecione</option>

                                @foreach ($units as $unit)
                                    <option
                                        value="{{ $unit->id }}"
                                        @selected(old('unit_id') == $unit->id)
                                    >
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                        </p>

                        <button id="open-button" type="submit">
                            Abrir caçamba
                        </button>
                    </form>
                @endif
            </section>
        @endcan

        <h2>Caçambas cadastradas</h2>

        <div class="table-wrapper" tabindex="0" role="region" aria-label="Tabela de registros"><table>
            <thead>
                <tr>
                    <th scope="col">Caçamba</th>
                    <th scope="col">Unidade</th>
                    <th scope="col">Situação</th>
                    <th scope="col">Abertura</th>
                    <th scope="col">Aberta por</th>
                    <th scope="col">Encerramento</th>
                    <th scope="col">Encerrada por</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($containers as $container)
                    <tr>
                        <td>
    <a href="{{ route('disposal-containers.show', $container) }}">
        #{{ $container->id }}
    </a>
</td>

                        <td>{{ $container->unit->name }}</td>

                        <td>
                            {{ $container->status === 'open'
                                ? 'Aberta'
                                : 'Encerrada' }}
                        </td>

                        <td>
                            {{ $container->opened_at->copy()
                                ->timezone('America/Sao_Paulo')
                                ->format('d/m/Y') }}
                        </td>

                        <td>{{ $container->openedBy->name }}</td>

                        <td>
                            @if ($container->closed_at)
                                {{ $container->closed_at->copy()
                                    ->timezone('America/Sao_Paulo')
                                    ->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>{{ $container->closedBy?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            Nenhuma caçamba cadastrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table></div>

        @if ($containers->hasPages())
            <nav aria-label="Paginação das caçambas">
                @if (! $containers->onFirstPage())
                    <a href="{{ $containers->previousPageUrl() }}">
                        Anterior
                    </a>
                @endif

                <span>
                    Página {{ $containers->currentPage() }}
                    de {{ $containers->lastPage() }}
                </span>

                @if ($containers->hasMorePages())
                    <a href="{{ $containers->nextPageUrl() }}">
                        Próxima
                    </a>
                @endif
            </nav>
        @endif
    

    <script>
        const form = document.getElementById('open-container-form');

        if (form) {
            form.addEventListener('submit', () => {
                const button = document.getElementById('open-button');

                button.disabled = true;
                button.textContent = 'Abrindo…';
            });
        }
    </script>

</div>
@endsection
