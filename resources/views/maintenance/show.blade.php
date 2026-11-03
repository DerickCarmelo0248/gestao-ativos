@extends('layouts.app')
@section('title','Detalhes da manutenção')
@section('content')
<div class="module-page"><a href="{{ route('maintenance.index') }}">Voltar ao histórico</a>
<div class="page-heading"><h1>Manutenção #{{ $maintenance->id }}</h1></div>
@foreach (['patrimony'=>'Patrimônio','model_name'=>'Modelo','repair_location'=>'Local','ticket_number'=>'Chamado','unit_name'=>'Unidade','sector_name'=>'Setor','responsible_name'=>'Responsável pelo equipamento','reason'=>'Motivo'] as $field=>$label)
<p><strong>{{ $label }}:</strong> {{ $maintenance->$field }}</p>@endforeach
<p>Envio registrado por {{ $maintenance->sentBy->name }} em {{ $maintenance->sent_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}.</p>
@if ($maintenance->returned_at)
<p>Retorno registrado por {{ $maintenance->returnedBy->name }} em {{ $maintenance->returned_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}.</p>
<p>Observações do retorno: {{ $maintenance->return_notes ?? '—' }}</p>
@else
<h2>Registrar retorno</h2>
<p>{{ $maintenance->asset_id ? 'O equipamento voltará à situação anterior: '.($maintenance->previous_status === 'available' ? 'Disponível' : 'Em uso').'. A unidade de estoque será mantida.' : 'O retorno encerra este registro sem adicionar o equipamento ao estoque.' }}</p>
<form method="POST" action="{{ route('maintenance.return',$maintenance) }}">@csrf
<p><label for="return_notes">Observações do retorno</label><textarea id="return_notes" name="return_notes" maxlength="2000">{{ old('return_notes') }}</textarea></p>
<p><label><input type="checkbox" name="confirmed" value="1" required> Confirmo que o equipamento retornou da manutenção.</label></p>
<button>Confirmar retorno</button></form>
@endif
<h2>Textos para e-mail</h2><p>Revise os dados e preencha o valor, quando necessário. Copie o texto para o seu e-mail.</p>
@foreach ($drafts as $draft)
<details><summary>{{ $draft['label'] }}</summary>
<p><label for="subject-{{ $loop->index }}">Assunto</label><input id="subject-{{ $loop->index }}" value="{{ $draft['subject'] }}" style="width:100%"></p>
<p><label for="body-{{ $loop->index }}">Mensagem</label><textarea id="body-{{ $loop->index }}" rows="18" style="width:100%">{{ $draft['body'] }}</textarea></p>
<button type="button" data-copy-maintenance="body-{{ $loop->index }}">Copiar mensagem</button><span role="status"></span>
</details>
@endforeach</div>
<script>
document.querySelectorAll('[data-copy-maintenance]').forEach(button => button.addEventListener('click', async () => {
 const field = document.getElementById(button.dataset.copyMaintenance);
 try {
  if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(field.value);
  else { field.focus(); field.select(); if (!document.execCommand('copy')) throw new Error('copy'); }
  button.nextElementSibling.textContent = 'Mensagem copiada.';
 } catch { field.focus(); field.select(); button.nextElementSibling.textContent = 'Pressione Ctrl+C para copiar.'; }
}));
</script>
@endsection
