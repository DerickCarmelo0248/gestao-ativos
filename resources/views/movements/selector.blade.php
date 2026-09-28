<div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / MOVIMENTAÇÕES</p><h1>{{ $movement === 'entry' ? 'Entrada' : 'Saída' }}</h1></div>
<form method="GET" action="{{ route('movements.'.$movement) }}">
    <p><label for="movement_item">Item</label><br>
    <select id="movement_item" name="item_id" required>
        <option value="">Selecione o item</option>
        @foreach ($catalogItems as $catalogItem)
            <option value="{{ $catalogItem->id }}" @selected($selectedItem?->id === $catalogItem->id)>{{ $catalogItem->code }} — {{ $catalogItem->name }} ({{ $catalogItem->tracking_type === 'individual' ? 'Com patrimônio' : 'Por quantidade' }})</option>
        @endforeach
    </select></p>
    <button type="submit">{{ $selectedItem ? 'Trocar item' : 'Continuar' }}</button>
</form>
@if ($catalogItems->isEmpty())
    <p>Nenhum item ativo cadastrado.</p>
@elseif ($selectedItem)
    <p>{{ $selectedItem->tracking_type === 'individual' ? 'Este item é controlado por patrimônio.' : 'Este item é controlado por quantidade, sem patrimônio.' }}</p>
@else
    <p>Selecione o item. O formulário será definido automaticamente conforme seu cadastro.</p>
@endif
