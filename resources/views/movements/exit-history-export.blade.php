<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><style>
@page { margin: 30px; } body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #183047; }
h1 { font-size: 18px; } h2 { font-size: 13px; } table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th,td { border: 1px solid #dce3ed; padding: 6px; overflow-wrap: break-word; } th { background: #edf1f5; }
thead { display: table-header-group; } tr { page-break-inside: avoid; } .detail { page-break-before: always; }
</style></head><body>
<h1>Histórico de saídas — {{ $detailed ? 'Detalhado' : 'Resumo' }}</h1>
@foreach ($metadata as [$label, $value])<p><strong>{{ $label }}:</strong> {{ $value }}</p>@endforeach
<table><thead><tr><th>Código</th><th>Item/modelo</th><th>Tipo</th><th>Quantidade</th></tr></thead><tbody>
@forelse ($summary as $row)<tr>@foreach ($row as $value)<td>{{ $value }}</td>@endforeach</tr>
@empty <tr><td colspan="4">Nenhuma saída encontrada.</td></tr>@endforelse
</tbody></table>
@if ($detailed)
@foreach ($rows as $row)
<div class="detail"><h2>Saída — {{ $row[2] }} — {{ $row[0] }}</h2>
@foreach ($headers as $index => $label)<p><strong>{{ $label }}:</strong><br>{!! nl2br(e($row[$index])) !!}</p>@endforeach
</div>
@endforeach
@endif
</body></html>
