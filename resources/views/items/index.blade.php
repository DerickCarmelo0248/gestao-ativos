@extends('layouts.app')
@section('title', 'Consultar itens')
@section('content')
<div class="module-page">
    <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / CADASTROS</p><h1>Consultar itens</h1></div>
    <p><a class="button" href="{{ route('items.create') }}">Cadastrar item</a></p>
    <form method="GET" action="{{ route('items.index') }}">
        <label for="search">Buscar por nome ou código</label>
        <input id="search" name="search" value="{{ $search }}" maxlength="150">
        <button type="submit">Buscar</button>
        <a href="{{ route('items.index') }}">Limpar</a>
    </form>
    <p>Itens com equipamentos, saldos ou histórico vinculados não podem ser excluídos.</p>
    <div class="table-wrapper"><table>
        <thead><tr><th>Código</th><th>Nome</th><th>Categoria</th><th>Controle</th><th>Mínimo por unidade</th><th>Situação</th><th>Ações</th></tr></thead>
        <tbody>
        @forelse ($items as $item)
            <tr><td>{{ $item->code }}</td><td>{{ $item->name }}</td><td>{{ $item->category->name }}</td><td>{{ $item->tracking_type === 'individual' ? 'Individual' : 'Quantidade' }}</td><td>{{ $item->minimum_stock }}</td><td>{{ $item->is_active ? 'Ativo' : 'Inativo' }}</td>
                <td><a href="{{ route('items.edit', $item) }}">Editar</a>
                    <form method="POST" action="{{ route('items.destroy', $item) }}" data-delete-item>
                        @csrf @method('DELETE')
                        <button type="submit" aria-label="Excluir item {{ $item->code }}">Excluir</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">Nenhum item encontrado.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $items->links() }}
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-delete-item]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!confirm('Excluir este item do catálogo? Esta ação não pode ser desfeita.')) event.preventDefault();
    });
});
</script>
@endpush
