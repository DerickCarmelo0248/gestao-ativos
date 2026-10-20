<?php
namespace App\Http\Controllers;

use App\Models\DisposalContainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DisposalContainerExportController extends Controller
{
    public function __invoke(Request $request, DisposalContainer $container)
    {
        Gate::authorize('view', $container);
        $format = $request->validate(['format' => ['required', 'in:pdf-simple,pdf-detailed,xlsx']])['format'];
        $container->load(['unit', 'openedBy', 'closedBy']);
        $items = $container->items()->with('addedBy')->orderBy('added_at')->orderBy('id')->get();
        // Use the recorded snapshots, including materials received directly for disposal.
        $summary = $items->groupBy('category_name')->map(fn ($group, $category) => [
            'Categoria' => $category, 'Quantidade' => $group->sum('quantity'),
        ])->sortKeys()->values()->all();
        $models = $items->groupBy(fn ($item) => json_encode([$item->category_name, $item->item_code, $item->item_name]))
            ->map(fn ($group) => [$group->first()->category_name, $group->first()->item_code, $group->first()->item_name, $group->sum('quantity')])->values()->all();
        $date = fn ($value) => $value?->copy()->timezone('America/Sao_Paulo')->format('d/m/Y') ?? '—';
        $metadata = [
            ['Caçamba', (string) $container->id], ['Unidade', $container->unit->name],
            ['Situação', $container->status === 'open' ? 'Aberta' : 'Encerrada'],
            ['Aberta em', $date($container->opened_at)], ['Aberta por', $container->openedBy?->name ?? '—'],
            ['Encerrada em', $date($container->closed_at)], ['Encerrada por', $container->closedBy?->name ?? '—'],
            ['Observações da caçamba', $container->notes ?? '—'],
            ['Total de unidades', $items->sum('quantity')], ['Total de registros', $items->count()],
            ['Gerado em', now()->timezone('America/Sao_Paulo')->format('d/m/Y H:i')],
        ];
        $details = $items->map(fn ($item) => [
            $item->id, $date($item->added_at), $item->category_name, $item->item_code, $item->item_name,
            $item->patrimony ?? '—', $item->serial_number ?? '—', $item->quantity, $item->origin_unit_name,
            $item->registration_source === 'external' ? 'Direto para descarte' : 'Equipamento cadastrado',
            $item->addedBy?->name ?? '—', $item->reason,
        ])->all();
        $headers = ['Registro', 'Data', 'Categoria', 'Código', 'Item/modelo', 'Patrimônio', 'Número de série', 'Quantidade', 'Origem', 'Cadastro', 'Incluído por', 'Motivo'];
        if ($format !== 'xlsx') {
            $detailed = $format === 'pdf-detailed';
            return Pdf::loadView('disposal-containers.export', compact('container', 'summary', 'models', 'metadata', 'details', 'headers', 'detailed'))
                ->setPaper('a4', 'portrait')->download('cacamba-'.$container->id.'-'.$format.'.pdf');
        }
        $book = new Spreadsheet();
        $this->fill($book->getActiveSheet()->setTitle('Resumo'), array_merge([['Categoria', 'Quantidade']], array_map('array_values', $summary)));
        $this->fill($book->createSheet()->setTitle('Por modelo'), array_merge([['Categoria', 'Código', 'Item/modelo', 'Quantidade']], $models));
        $this->fill($book->createSheet()->setTitle('Detalhado'), array_merge([$headers], $details));
        $this->fill($book->createSheet()->setTitle('Caçamba'), array_merge([['Campo', 'Valor']], $metadata));
        $book->setActiveSheetIndex(0);
        return response()->streamDownload(function () use ($book) {
            try { (new Xlsx($book))->save('php://output'); }
            finally { $book->disconnectWorksheets(); }
        }, 'cacamba-'.$container->id.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function fill($sheet, array $rows): void
    {
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValueExplicit([$c + 1, $r + 1], $value, is_int($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
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
