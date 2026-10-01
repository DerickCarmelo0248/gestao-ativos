@extends('layouts.app')
@section('title', 'Estoque')
@section('content')
<div class="module-page">
    <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / CONSULTAS</p><h1>Estoque</h1></div>
    <p>Selecione um modelo para consultar seus patrimônios ou os saldos por unidade.</p>
    <form method="GET" action="{{ route('assets.index') }}">
        <p><label for="search">Nome ou código do modelo</label><br><input id="search" name="search" maxlength="150" value="{{ $filters['search'] ?? '' }}"></p>
        <p><label for="unit_id">Unidade</label><br><select id="unit_id" name="unit_id"><option value="">Todas as unidades</option>
            @foreach ($units as $unit)<option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? '') == $unit->id)>{{ $unit->name }}</option>@endforeach
        </select></p>
        <p><label for="tracking_type">Tipo de controle</label><br>
            <select id="tracking_type" name="tracking_type">
                <option value="">Todos os tipos</option>
                <option value="individual" @selected(($filters['tracking_type'] ?? '') === 'individual')>Com patrimônio</option>
                <option value="quantity" @selected(($filters['tracking_type'] ?? '') === 'quantity')>Por quantidade</option>
            </select>
        </p>
        <p><label for="category_id">Categoria</label><br>
            <select id="category_id" name="category_id">
                <option value="">Todas as categorias</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </p>
        <button type="submit">Filtrar estoque</button> <a href="{{ route('assets.index') }}">Limpar filtros</a>
    </form>
    <form method="GET" action="{{ route('assets.index') }}">
        <p><label for="patrimony">Buscar diretamente por patrimônio completo</label><br><input id="patrimony" name="patrimony" maxlength="50" required></p>
        <button type="submit">Buscar patrimônio</button>
    </form>
    <p>{{ $models->total() }} modelo(s). Quantidades {{ empty($filters['unit_id']) ? 'somadas de todas as unidades' : 'da unidade selecionada' }}. Em descarte inclui equipamentos aguardando descarte e já na caçamba.</p>
    <div class="table-wrapper"><table>
        <thead><tr><th>Modelo</th><th>Controle</th><th>Disponíveis</th><th>Em uso</th><th>Em descarte</th><th>Descartados</th><th>Consulta</th></tr></thead>
        <tbody>
        @forelse ($models as $model)
            @php($url = route($model->tracking_type === 'individual' ? 'assets.index' : 'stock-balances.index', array_filter(['item_id' => $model->id, 'unit_id' => $filters['unit_id'] ?? null])))
            <tr><td><a href="{{ $url }}">{{ $model->name }}</a><br><small>{{ $model->code }}{{ $model->is_active ? '' : ' · Inativo' }}</small></td>
                <td>{{ $model->tracking_type === 'individual' ? 'Com patrimônio' : 'Quantidade' }}</td>
                <td>{{ $model->available }}</td>
                <td>{{ $model->tracking_type === 'individual' ? $model->in_use : '—' }}</td>
                <td>{{ $model->tracking_type === 'individual' ? $model->disposal : '—' }}</td>
                <td>{{ $model->tracking_type === 'individual' ? $model->disposed : '—' }}</td>
                <td><a href="{{ $url }}">{{ $model->tracking_type === 'individual' ? 'Ver patrimônios' : 'Ver saldos' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="7">Nenhum modelo encontrado.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $models->links() }}
</div>
@endsection
