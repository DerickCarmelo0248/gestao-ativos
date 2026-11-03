@extends('layouts.app')
@section('title','Histórico de manutenções')
@section('content')
<div class="module-page"><div class="page-heading"><h1>Histórico de manutenções</h1></div>
<a href="{{ route('maintenance.create') }}">Registrar manutenção</a>
<form method="GET"><p><label for="search">Patrimônio, modelo ou chamado</label><input id="search" name="search" value="{{ $filters['search'] ?? '' }}"></p>
<p><label for="status">Situação</label><select id="status" name="status"><option value="">Todas</option><option value="open" @selected(($filters['status'] ?? '') === 'open')>Em manutenção</option><option value="returned" @selected(($filters['status'] ?? '') === 'returned')>Retornados</option></select></p><button>Filtrar</button></form>
<p>{{ $records->total() }} registro(s)</p>
<div class="table-wrapper"><table><thead><tr><th>Envio</th><th>Patrimônio / Modelo</th><th>Local</th><th>Chamado</th><th>Unidade / Setor</th><th>Responsável</th><th>Situação</th><th>Ações</th></tr></thead><tbody>
@forelse ($records as $m)<tr><td>{{ $m->sent_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}</td><td>{{ $m->patrimony }} — {{ $m->model_name }}</td><td>{{ $m->repair_location }}</td><td>{{ $m->ticket_number }}</td><td>{{ $m->unit_name }} / {{ $m->sector_name }}</td><td>{{ $m->responsible_name }}</td><td>{{ $m->returned_at ? 'Retornado em '.$m->returned_at->timezone('America/Sao_Paulo')->format('d/m/Y') : 'Em manutenção' }}</td><td><a href="{{ route('maintenance.show',$m) }}">{{ $m->returned_at ? 'Consultar' : 'Consultar / Registrar retorno' }}</a></td></tr>
@empty<tr><td colspan="8">Nenhuma manutenção encontrada.</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
