@extends('layouts.app')
@section('title', 'Visão geral')

@section('content')
    <div class="dashboard-heading">
        <div>
            <p class="eyebrow">CONTROLE E RASTREABILIDADE</p>
            <h1>Seu estoque, em perspectiva.</h1>
            <p>Bem-vindo(a), <strong>{{ auth()->user()->name }}</strong>. Acompanhe o que precisa de atenção.</p>
        </div>
        <span class="date-chip">{{ now('America/Sao_Paulo')->format('d/m/Y') }}</span>
    </div>

    <div class="metric-grid">
        @foreach ([
            ['available', 'Equipamentos disponíveis', 'Prontos para uma nova saída', 'monitor', 'blue'],
            ['zero', 'Saldos zerados', 'Item por unidade de estoque', 'box', 'red'],
            ['pending', 'Reposições pendentes', 'Solicitações abertas ou em compra', 'refresh', 'amber'],
            ['waiting', 'Aguardando descarte', 'Equipamentos separados', 'clock', 'amber'],
            ['open', 'Caçambas abertas', 'Votuporanga e Rio Preto', 'container', 'blue'],
            ['disposed', 'Descartados no mês', 'Unidades com e sem patrimônio', 'check', 'green'],
        ] as [$key, $label, $hint, $icon, $tone])
            <article class="metric-card">
                <div class="metric-top"><span class="icon-tile {{ $tone }}"><svg aria-hidden="true"><use href="#icon-{{ $icon }}"/></svg></span><span class="metric-dot {{ $tone }}"></span></div>
                <span class="metric-label">{{ $label }}</span>
                <strong class="metric-value">{{ number_format($stats[$key], 0, ',', '.') }}</strong>
                <span class="metric-hint">{{ $hint }}</span>
            </article>
        @endforeach
    </div>

    <div class="dashboard-columns">
        <section class="panel inventory-panel">
            <div class="panel-heading"><div><p class="eyebrow">ATENÇÃO AO ESTOQUE</p><h2>Itens com saldo zerado</h2></div><span class="badge red">{{ $stats['zero'] }} registros</span></div>
            <p class="panel-description">Saldos cadastrados de itens e unidades ativos. O mínimo de estoque ainda não está configurado.</p>
            <form class="filter-bar" action="{{ route('dashboard') }}" method="GET">
                <label class="search-field"><span class="sr-only">Buscar nome ou código</span><svg aria-hidden="true"><use href="#icon-search"/></svg><input name="search" maxlength="150" placeholder="Buscar nome ou código…" value="{{ $filters['search'] ?? '' }}"></label>
                <label><span class="sr-only">Unidade</span><select name="unit_id"><option value="">Todas as unidades</option>@foreach ($units as $unit)<option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? '') == $unit->id)>{{ $unit->name }}</option>@endforeach</select></label>
                <button class="button button-secondary" type="submit">Filtrar</button>
                @if (!empty($filters['search']) || !empty($filters['unit_id']))<a class="reset-filter" href="{{ route('dashboard') }}">Limpar</a>@endif
            </form>
            <div class="table-wrapper"><table class="dashboard-table"><thead><tr><th>Item / modelo</th><th>Unidade</th><th>Saldo</th><th>Situação</th></tr></thead><tbody>
                @forelse ($zeroBalances as $balance)
                    <tr><td><a class="item-title" href="{{ route('stock-balances.show', $balance) }}">{{ $balance->item->name }}</a><small>{{ $balance->item->code }}</small></td><td>{{ $balance->unit->name }}</td><td class="quantity-zero">0</td><td><span class="badge red">Sem saldo</span></td></tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state"><span class="icon-tile green"><svg aria-hidden="true"><use href="#icon-check"/></svg></span><strong>Nenhum saldo zerado encontrado</strong><p>Não há registros correspondentes a esta consulta.</p></div></td></tr>
                @endforelse
            </tbody></table></div>
            <div class="panel-footer"><span>{{ $zeroBalances->total() }} registro(s) nesta consulta</span><div class="pagination">@if (!$zeroBalances->onFirstPage())<a href="{{ $zeroBalances->previousPageUrl() }}" aria-label="Página anterior">←</a>@endif<span>{{ $zeroBalances->currentPage() }} / {{ $zeroBalances->lastPage() }}</span>@if ($zeroBalances->hasMorePages())<a href="{{ $zeroBalances->nextPageUrl() }}" aria-label="Próxima página">→</a>@endif</div></div>
            <div class="panel-bottom-link"><a href="{{ route('stock-balances.index') }}">Consultar todo o estoque <span aria-hidden="true">↗</span></a></div>
        </section>

        <div class="dashboard-side">
            <section class="panel">
                <div class="panel-heading"><div><p class="eyebrow">HISTÓRICO RECENTE</p><h2>Últimas movimentações</h2></div><svg class="heading-icon" aria-hidden="true"><use href="#icon-clock"/></svg></div>
                <div class="activity-list">
                    @forelse ($events as $event)
                        <a class="activity-row" href="{{ $event->url }}">
                            <span class="activity-icon {{ $event->type === 'exit' ? 'red' : 'blue' }}"><svg aria-hidden="true"><use href="#icon-{{ $event->type === 'exit' ? 'arrow' : 'refresh' }}"/></svg></span>
                            <span class="activity-copy"><strong>{{ $eventLabels[$event->type] ?? $event->type }}</strong><span>{{ $event->name }}</span><small>{{ $event->detail }} · {{ $event->unit_name }}</small></span>
                            <time datetime="{{ \Illuminate\Support\Carbon::parse($event->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($event->created_at)->timezone('America/Sao_Paulo')->format('d/m/Y') }}</time>
                        </a>
                    @empty
                        <p class="quiet-empty">As movimentações registradas aparecerão aqui.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel disposal-panel">
                <div class="panel-heading"><div><p class="eyebrow">DESTINAÇÃO DOS MATERIAIS</p><h2>Visão de descarte</h2></div><svg class="heading-icon" aria-hidden="true"><use href="#icon-container"/></svg></div>
                <div class="container-grid">
                    @forelse ($containers as $container)
                        <a class="container-tile" href="{{ route('disposal-containers.show', $container) }}"><span class="container-location"><i></i>{{ $container->unit->name }}</span><strong>Caçamba #{{ $container->id }}</strong><span>{{ number_format($container->items_sum_quantity ?? 0, 0, ',', '.') }} unidade(s) incluída(s)</span><small>Aberta em {{ $container->opened_at->copy()->timezone('America/Sao_Paulo')->format('d/m/Y') }}</small></a>
                    @empty
                        <p class="quiet-empty">Nenhuma caçamba aberta.</p>
                    @endforelse
                </div>
                @if ($lastClosed)<p class="last-disposal">Último encerramento: <a href="{{ route('disposal-containers.show', $lastClosed) }}">#{{ $lastClosed->id }} · {{ $lastClosed->unit->name }}</a> em {{ $lastClosed->closed_at->copy()->timezone('America/Sao_Paulo')->format('d/m/Y') }}.</p>@endif
                <a class="button full-width" href="{{ route('disposal-containers.index') }}">Gerenciar caçambas <span aria-hidden="true">→</span></a>
            </section>
        </div>
    </div>
    <p class="dashboard-note">Visão geral de todas as unidades · Os filtros acima se aplicam à tabela de saldos zerados.</p>
@endsection
