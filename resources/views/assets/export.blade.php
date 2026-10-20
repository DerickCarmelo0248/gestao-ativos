<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><style>
@page { margin: 25px; } body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #183047; }
h1 { font-size: 18px; } h2 { font-size: 13px; } table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th, td { border: 1px solid #dce3ed; padding: 5px; overflow-wrap: break-word; } th { background: #edf1f5; }
thead { display: table-header-group; } tr { page-break-inside: avoid; } .asset { page-break-before: always; } .empty { padding: 15px; }
</style></head><body>
<h1>Equipamentos — {{ $detailed ? 'Relatório detalhado' : 'Relatório simples' }}</h1>
<p>Gerado em {{ $generated }} · Total: {{ $assets->count() }}</p>
<p>@foreach ($summary as $label => $value) <strong>{{ $label }}:</strong> {{ $value }} &nbsp; @endforeach</p>
<table><thead><tr><th>Patrimônio</th><th>Código / Modelo</th><th>Número de série</th><th>Unidade</th><th>Situação</th></tr></thead><tbody>
@forelse ($assets as $asset)
<tr><td>{{ $asset->patrimony }}</td><td>{{ $asset->item->code }} — {{ $asset->item->name }}</td><td>{{ $asset->serial_number ?? '—' }}</td><td>{{ $asset->unit->name }}</td><td>{{ $statuses[$asset->status] ?? $asset->status }}</td></tr>
@empty <tr><td colspan="5" class="empty">Nenhum equipamento encontrado para os filtros.</td></tr> @endforelse
</tbody></table>
@if ($detailed)
@foreach ($assets as $asset)
<div class="asset"><h2>Patrimônio {{ $asset->patrimony }} — {{ $asset->item->name }}</h2>
<p>Unidade: {{ $asset->unit->name }} · Situação: {{ $statuses[$asset->status] ?? $asset->status }}</p>
<p>Observações: {{ $asset->notes ?? '—' }}</p>
<table><thead><tr><th>Data / Tipo</th><th>Origem</th><th>Destino / Setor</th><th>Técnico / Chamado</th><th>Responsável</th><th>Observação</th></tr></thead><tbody>
@forelse ($asset->movements as $m)
<tr><td>{{ $m->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}<br>{{ $types[$m->type] ?? $m->type }}</td><td>{{ $m->unit?->name }}</td><td>{{ $m->destinationEstablishment?->name ?? $m->legacyDestinationUnit?->name ?? '—' }}<br>{{ $m->destinationSector?->name ?? $m->destination_sector ?? '—' }}</td><td>{{ $m->technician?->name ?? '—' }}<br>{{ $m->ticket_number ?? '—' }}</td><td>{{ $m->user?->name }}</td><td>{{ $m->notes ?? '—' }}</td></tr>
@empty <tr><td colspan="6">Nenhuma movimentação registrada.</td></tr> @endforelse
</tbody></table></div>
@endforeach
@endif
</body></html>
