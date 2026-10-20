<?php
namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AssetExportController extends Controller
{
    public function __invoke(Request $request)
    {
        Gate::authorize('viewAny', Asset::class);
        $filters = $request->validate([
            'format' => ['required', 'in:pdf-simple,pdf-detailed,xlsx'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'patrimony' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:available,in_use,awaiting_disposal,in_container,disposed'],
        ]);
        $query = Asset::query()->with(['item', 'unit']);
        foreach (['item_id', 'unit_id', 'status', 'patrimony'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }
        $detailed = $filters['format'] !== 'pdf-simple';
        if ($detailed) {
            $query->with(['movements' => fn ($q) => $q->orderBy('created_at')->orderBy('id'),
                'movements.user', 'movements.unit', 'movements.destinationEstablishment',
                'movements.destinationSector', 'movements.legacyDestinationUnit', 'movements.technician']);
        }
        $assets = $query->orderBy('patrimony')->orderBy('id')->get();
        $statuses = ['available' => 'Disponível', 'in_use' => 'Em uso', 'awaiting_disposal' => 'Aguardando descarte', 'in_container' => 'Na caçamba', 'disposed' => 'Descartado'];
        $types = ['entry' => 'Entrada', 'exit' => 'Saída', 'return' => 'Devolução', 'replacement' => 'Reposição', 'container_entry' => 'Entrada na caçamba', 'disposal' => 'Descarte'];
        $summary = [
            'Modelo' => isset($filters['item_id']) ? \App\Models\Item::find($filters['item_id'])?->name : 'Todos',
            'Unidade' => isset($filters['unit_id']) ? \App\Models\Unit::find($filters['unit_id'])?->name : 'Todas',
            'Situação' => $statuses[$filters['status'] ?? ''] ?? 'Todas',
            'Patrimônio' => $filters['patrimony'] ?? 'Todos',
        ];
        $generated = now()->timezone('America/Sao_Paulo')->format('d/m/Y H:i');
        if ($filters['format'] !== 'xlsx') {
            return Pdf::loadView('assets.export', compact('assets', 'statuses', 'types', 'summary', 'generated', 'detailed'))
                ->setPaper('a4', 'landscape')->download('equipamentos-'.$filters['format'].'.pdf');
        }
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Equipamentos');
        $rows = [['Patrimônio', 'Código', 'Modelo', 'Número de série', 'Unidade', 'Situação', 'Observações']];
        $history = [['Patrimônio', 'Data', 'Movimentação', 'Origem', 'Destino', 'Setor', 'Técnico', 'Chamado', 'Responsável', 'Observações']];
        foreach ($assets as $asset) {
            $rows[] = [$asset->patrimony, $asset->item->code, $asset->item->name, $asset->serial_number, $asset->unit->name, $statuses[$asset->status] ?? $asset->status, $asset->notes];
            foreach ($asset->movements as $m) {
                $history[] = [$asset->patrimony, $m->created_at->timezone('America/Sao_Paulo')->format('d/m/Y'), $types[$m->type] ?? $m->type, $m->unit?->name,
                    $m->destinationEstablishment?->name ?? $m->legacyDestinationUnit?->name,
                    $m->destinationSector?->name ?? $m->destination_sector, $m->technician?->name, $m->ticket_number, $m->user?->name, $m->notes];
            }
        }
        $this->fill($sheet, $rows);
        $this->fill($book->createSheet()->setTitle('Movimentações'), $history);
        $this->fill($book->createSheet()->setTitle('Consulta'), array_merge([['Filtro', 'Valor'], ['Gerado em', $generated], ['Total', (string) $assets->count()]], array_map(fn ($key, $value) => [$key, $value], array_keys($summary), array_values($summary))));
        $book->setActiveSheetIndex(0);
        return response()->streamDownload(function () use ($book) {
            try { (new Xlsx($book))->save('php://output'); }
            finally { $book->disconnectWorksheets(); }
        }, 'equipamentos.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function fill($sheet, array $rows): void
    {
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                // Keep leading zeroes and prevent spreadsheet formula injection.
                $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) ($value ?? ''), DataType::TYPE_STRING);
            }
        }
        $last = $sheet->getHighestColumn();
        $sheet->getStyle('A1:'.$last.'1')->getFont()->setBold(true);
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true);
        foreach (range('A', $last) as $column) { $sheet->getColumnDimension($column)->setWidth(25); }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
    }
}
