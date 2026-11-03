@extends('layouts.app')
@section('title','Manutenção')
@section('content')
<div class="module-page"><div class="page-heading"><h1>Enviar equipamento para manutenção</h1></div>
<a href="{{ route('maintenance.index') }}">Histórico de manutenções</a>
<form method="POST" action="{{ route('maintenance.store') }}">@csrf
<p><label for="source">Origem do cadastro</label><select name="source" id="source"><option value="registered" @selected(old('source') !== 'external')>Equipamento cadastrado</option><option value="external" @selected(old('source') === 'external')>Não cadastrado no estoque</option></select></p>
<fieldset id="registered-fields"><legend>Equipamento cadastrado</legend>
<p><label for="asset_id">Patrimônio</label><select id="asset_id" name="asset_id"><option value="">Selecione</option>@foreach ($assets as $asset)<option value="{{ $asset->id }}" @selected(old('asset_id') == $asset->id)>{{ $asset->patrimony }} — {{ $asset->item->name }} — {{ $asset->unit->name }}</option>@endforeach</select></p></fieldset>
<fieldset id="external-fields"><legend>Equipamento não cadastrado</legend>
<p><label for="patrimony">Patrimônio</label><input id="patrimony" name="patrimony" maxlength="50" value="{{ old('patrimony') }}"></p>
<p><label for="model_name">Modelo do equipamento</label><input id="model_name" name="model_name" maxlength="255" value="{{ old('model_name') }}"></p></fieldset>
@foreach (['repair_location'=>'Local enviado para manutenção', 'ticket_number'=>'Chamado', 'responsible_name'=>'Responsável pelo equipamento'] as $field=>$label)
<p><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" maxlength="{{ $field === 'ticket_number' ? 100 : 255 }}" value="{{ old($field) }}" required></p>
@endforeach
<p><label for="establishment_id">Unidade</label><select id="establishment_id" name="establishment_id" required><option value="">Selecione</option>
@foreach ($establishments as $establishment)<option value="{{ $establishment->id }}" @selected(old('establishment_id') == $establishment->id)>{{ $establishment->code }} - {{ $establishment->name }}</option>@endforeach
</select></p>
<p><label for="sector_id">Setor</label><select id="sector_id" name="sector_id" required><option value="">Selecione</option>
@foreach ($sectors as $sector)<option value="{{ $sector->id }}" @selected(old('sector_id') == $sector->id)>{{ $sector->name }}</option>@endforeach
</select></p>
<p><label for="reason">Motivo</label><textarea id="reason" name="reason" maxlength="2000" required>{{ old('reason') }}</textarea></p>
<input type="hidden" name="invoice_required" value="0">
<p><label><input type="checkbox" name="invoice_required" value="1" @checked(old('invoice_required'))> Gerar também solicitação de nota fiscal</label></p>
<p>Após confirmar, revise e copie os textos de e-mail. O sistema não envia mensagens automaticamente.</p>
<button type="submit">Confirmar envio para manutenção</button></form></div>
<script>
(() => {
 const source = document.getElementById('source');
 function update() {
  const external = source.value === 'external';
  const registered = document.getElementById('registered-fields');
  const fields = document.getElementById('external-fields');
  registered.hidden = external; registered.disabled = external;
  fields.hidden = !external; fields.disabled = !external;
  document.getElementById('asset_id').required = !external;
  document.getElementById('patrimony').required = external;
  document.getElementById('model_name').required = external;
 }
 source.addEventListener('change', update); update();
})();
</script>
@endsection
