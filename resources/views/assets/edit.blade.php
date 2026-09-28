@extends('layouts.app')
@section('title', 'Editar equipamento')
@section('content')
<div class="module-page">
    <a href="{{ route('assets.show', $asset) }}">Voltar ao equipamento</a>
    <div class="page-heading"><p class="eyebrow">GESTÃO DE ATIVOS / CADASTRO</p><h1>Editar patrimônio {{ $asset->patrimony }}</h1></div>
    <p>{{ $asset->item->name }} — {{ $asset->unit->name }}</p>
    <p>As alterações ficam registradas com data, responsável e valores anteriores. Unidade e situação são atualizadas pelas movimentações.</p>
    <form method="POST" action="{{ route('assets.update', $asset) }}">
        @csrf
        @method('PUT')
        <p><label for="patrimony">Patrimônio</label><br>
        <input id="patrimony" name="patrimony" value="{{ old('patrimony', $asset->patrimony) }}" maxlength="50" required></p>
        <p><label for="serial_number">Número de série (opcional)</label><br>
        <input id="serial_number" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}" maxlength="100"></p>
        <p><label for="notes">Observações do equipamento (opcional)</label><br>
        <textarea id="notes" name="notes" rows="4" maxlength="2000">{{ old('notes', $asset->notes) }}</textarea></p>
        <button type="submit">Salvar alterações</button>
        <a href="{{ route('assets.show', $asset) }}">Cancelar</a>
    </form>
</div>
@endsection
