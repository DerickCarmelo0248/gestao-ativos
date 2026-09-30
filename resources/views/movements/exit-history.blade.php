@extends('layouts.app')
@section('title', 'Histórico de saídas')
@section('content')
<div class="module-page exit-history">
    <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / MOVIMENTAÇÕES</p><h1>Histórico de saídas</h1></div>
    <form class="exit-history-filters" method="GET" action="{{ route('movements.exit-history') }}">
        <div class="exit-history-toolbar">
            <label class="sr-only" for="search">Item, código ou patrimônio</label><input id="search" name="search" placeholder="Buscar saída…" maxlength="150" value="{{ $filters['search'] ?? '' }}">
            <label class="sr-only" for="unit_id">Unidade de origem</label><select id="unit_id" name="unit_id"><option value="">Todas as unidades de origem</option>@foreach ($units as $unit)<option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? '') == $unit->id)>{{ $unit->name }}</option>@endforeach</select>
            <button type="submit">Buscar</button>
            @if (array_filter($filters))<a href="{{ route('movements.exit-history') }}">Limpar</a>@endif
        </div>
        <details @if (!empty($filters['kind']) || !empty($filters['from']) || !empty($filters['to'])) open @endif>
            <summary>Mais filtros</summary>
            <div class="exit-history-toolbar">
                <label for="kind">Tipo<select id="kind" name="kind"><option value="">Todos</option><option value="asset" @selected(($filters['kind'] ?? '') === 'asset')>Com patrimônio</option><option value="stock" @selected(($filters['kind'] ?? '') === 'stock')>Por quantidade</option></select></label>
                <label for="from">De<input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
                <label for="to">Até<input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
                <button type="submit">Aplicar</button>
            </div>
        </details>
    </form>
    <p>{{ $exits->total() }} registro(s)</p>
    <div class="table-wrapper"><table>
        <thead><tr><th>Data</th><th>Item/modelo</th><th>Qtd</th><th>Chamado</th><th>Patrimônio</th><th>Origem</th><th>Destino</th><th>Setor</th><th>Técnico</th><th>Usuário</th><th>Observações</th></tr></thead>
        <tbody>@forelse ($exits as $exit)
            @php($m = $exit->movement)
            @php($item = $exit->kind === 'asset' ? $m->asset->item : $m->item)
            <tr>
                <td>{{ $m->created_at->copy()->timezone('America/Sao_Paulo')->format('d/m/Y') }}</td>
                <td>{{ $item->name }}<br><small>{{ $item->code }}</small></td>
                <td>{{ $exit->kind === 'asset' ? 1 : $m->quantity }}</td>
                <td>{{ $m->ticket_number ?? '—' }}</td>
                <td>@if ($exit->kind === 'asset')<a href="{{ route('assets.show', $m->asset_id) }}">{{ $m->asset->patrimony }}</a>@else — @endif</td>

                <td>{{ $m->unit->name }}</td>
                <td>{{ $m->destinationEstablishment ? $m->destinationEstablishment->code.' — '.$m->destinationEstablishment->name : ($m->legacyDestinationUnit?->name ?? 'Não informado') }}</td>
                <td>{{ $m->destinationSector?->name ?? $m->destination_sector ?? 'Não informado' }}</td>
                <td>{{ $m->technician?->name ?? 'Não informado' }}</td><td>{{ $m->user->name }}</td><td>{{ $m->notes ?? '—' }}</td>
            </tr>
        @empty<tr><td colspan="11">Nenhuma saída encontrada.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $exits->links() }}
</div>
@endsection
