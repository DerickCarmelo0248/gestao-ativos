@extends('layouts.app')
@section('title', 'Transferência entre estoques')
@section('content')
<div class="module-page">
<div class="page-heading"><h1>Transferência entre estoques</h1></div>
<p>Transfira materiais disponíveis entre unidades. A confirmação atualiza os dois estoques imediatamente.</p>
<form method="GET" action="{{ route('transfers.index') }}">
<p><label for="from_unit_id">Estoque de origem</label><select id="from_unit_id" name="from_unit_id" required>
<option value="">Selecione</option>@foreach ($units as $unit)<option value="{{ $unit->id }}" @selected($origin?->id === $unit->id)>{{ $unit->name }}</option>@endforeach
</select></p>
<p><label for="item_id">Item/modelo</label><select id="item_id" name="item_id" required>
<option value="">Selecione</option>@foreach ($items as $item)<option value="{{ $item->id }}" @selected($selected?->id === $item->id)>{{ $item->code }} — {{ $item->name }}</option>@endforeach
</select></p><button>Consultar estoque</button></form>
@if ($selected && $origin)
<h2>{{ $selected->name }} — {{ $origin->name }}</h2>
<form method="POST" action="{{ route('transfers.store') }}">
@csrf
<input type="hidden" name="item_id" value="{{ $selected->id }}">
<input type="hidden" name="from_unit_id" value="{{ $origin->id }}">
<p><label for="to_unit_id">Estoque de destino</label><select id="to_unit_id" name="to_unit_id" required><option value="">Selecione</option>
@foreach ($units->where('id', '!=', $origin->id) as $unit)<option value="{{ $unit->id }}" @selected(old('to_unit_id') == $unit->id)>{{ $unit->name }}</option>@endforeach</select></p>
@if ($selected->tracking_type === 'individual')
<fieldset><legend>Patrimônios disponíveis ({{ $assets->count() }})</legend>
<div style="max-height:300px;overflow:auto">
@forelse ($assets as $asset)<label style="display:block;padding:8px"><input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}" @checked(in_array($asset->id, (array) old('asset_ids', [])))> {{ $asset->patrimony }}</label>
@empty<p>Nenhum patrimônio disponível na origem.</p>@endforelse
</div></fieldset>
@else
<p>Saldo disponível: <strong>{{ $quantity }}</strong></p>
<p><label for="quantity">Quantidade a transferir</label><input id="quantity" type="number" name="quantity" min="1" max="{{ min($quantity, 10000) }}" value="{{ old('quantity') }}" required></p>
@endif
<p><label for="notes">Observação (opcional)</label><textarea id="notes" name="notes" maxlength="2000">{{ old('notes') }}</textarea></p>
<button type="submit" @disabled($selected->tracking_type === 'individual' ? $assets->isEmpty() : $quantity < 1)>Confirmar transferência</button>
</form>
@endif
<h2>Histórico de transferências</h2>
<div class="table-wrapper"><table><thead><tr><th>Data</th><th>Item</th><th>Patrimônio</th><th>Quantidade</th><th>Origem</th><th>Destino</th><th>Responsável</th><th>Observação</th></tr></thead><tbody>
@forelse ($history as $row)<tr><td>{{ \Illuminate\Support\Carbon::parse($row->created_at)->timezone('America/Sao_Paulo')->format('d/m/Y') }}</td><td>{{ $row->item_name }}</td><td>{{ $row->patrimony ?? '—' }}</td><td>{{ $row->quantity }}</td><td>{{ $row->origin }}</td><td>{{ $row->destination }}</td><td>{{ $row->user_name }}</td><td>{{ $row->notes ?? '—' }}</td></tr>
@empty<tr><td colspan="8">Nenhuma transferência registrada.</td></tr>@endforelse
</tbody></table></div>{{ $history->links() }}
</div>
@endsection
