<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><style>
@page { margin: 30px; } body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #183047; }
h1 { font-size: 18px; } h2 { font-size: 13px; } table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th,td { border: 1px solid #dce3ed; padding: 6px; overflow-wrap: break-word; } th { background: #edf1f5; }
thead { display: table-header-group; } tr { page-break-inside: avoid; } .detail { page-break-before: always; }
</style></head><body>
<h1>Caçamba #{{ $container->id }} — {{ $detailed ? 'Relação detalhada' : 'Resumo de materiais' }}</h1>
@foreach ($metadata as [$label, $value]) <p><strong>{{ $label }}:</strong> {{ $value }}</p> @endforeach
<h2>Quantidade por categoria</h2>
<table><thead><tr><th>Categoria</th><th>Quantidade</th></tr></thead><tbody>
@forelse ($summary as $row)<tr><td>{{ $row['Categoria'] }}</td><td>{{ $row['Quantidade'] }}</td></tr>
@empty <tr><td colspan="2">Nenhum material incluído nesta caçamba.</td></tr> @endforelse
</tbody></table>
<h2>Quantidade por item/modelo</h2>
<table><thead><tr><th>Categoria</th><th>Código</th><th>Item/modelo</th><th>Quantidade</th></tr></thead><tbody>
@foreach ($models as $row)<tr>@foreach ($row as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach
</tbody></table>
@if ($detailed)
@foreach ($details as $row)
<div class="detail"><h2>Registro #{{ $row[0] }} — {{ $row[4] }}</h2>
@foreach ($headers as $index => $label)
<p><strong>{{ $label }}:</strong><br>{!! nl2br(e($row[$index])) !!}</p>
@endforeach
</div>
@endforeach
@endif
</body></html>
