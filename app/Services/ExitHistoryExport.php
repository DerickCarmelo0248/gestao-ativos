<?php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExitHistoryExport
{
    public function download(array $data, string $format)
    {
        $headers = ['Data', 'Código', 'Item/modelo', 'Quantidade', 'Chamado', 'Patrimônio', 'Origem', 'Destino', 'Setor', 'Técnico', 'Usuário', 'Observações', 'Tipo', 'Reposição necessária', 'Lote'];
        $rows = $data['exits']->map(function ($exit) {
            $m = $exit->movement;
            $item = $exit->kind === 'asset' ? $m->asset->item : $m->item;
            return [$m->created_at->copy()->timezone('America/Sao_Paulo')->format('d/m/Y'), $item->code, $item->name,
                $exit->kind === 'asset' ? 1 : $m->quantity, $m->ticket_number ?? '—',
                $exit->kind === 'asset' ? $m->asset->patrimony : '—', $m->unit->name,
                $m->destinationEstablishment ? $m->destinationEstablishment->code.' — '.$m->destinationEstablishment->name : ($m->legacyDestinationUnit?->name ?? 'Não informado'),
                $m->destinationSector?->name ?? $m->destination_sector ?? 'Não informado',
                $m->technician?->name ?? 'Não informado', $m->user->name, $m->notes ?? '—',
                $exit->kind === 'asset' ? 'Com patrimônio' : 'Por quantidade',
                $m->replacement_required ? 'Sim' : 'Não', $m->batch_id ?? '—'];
        })->all();
        $f = $data['filters'];
        $metadata = [
            ['Busca', $f['search'] ?? 'Todas'],
            ['Unidade', $data['units']->firstWhere('id', $f['unit_id'] ?? null)?->name ?? 'Todas'],
            ['Tipo', ['asset' => 'Com patrimônio', 'stock' => 'Por quantidade'][$f['kind'] ?? ''] ?? 'Todos'],
            ['De', empty($f['from']) ? 'Sem limite' : \Illuminate\Support\Carbon::parse($f['from'])->format('d/m/Y')],
            ['Até', empty($f['to']) ? 'Sem limite' : \Illuminate\Support\Carbon::parse($f['to'])->format('d/m/Y')],
            ['Registros', count($rows)], ['Unidades retiradas', array_sum(array_column($rows, 3))],
            ['Gerado em', now()->timezone('America/Sao_Paulo')->format('d/m/Y H:i')],
        ];
        $summary = collect($rows)->groupBy(fn ($row) => json_encode([$row[1], $row[2], $row[12]]))
            ->map(fn ($group) => [$group->first()[1], $group->first()[2], $group->first()[12], $group->sum(fn ($row) => $row[3])])->values()->all();
        if ($format !== 'xlsx') {
            $detailed = $format === 'pdf-detailed';
            return Pdf::loadView('movements.exit-history-export', compact('headers', 'rows', 'metadata', 'summary', 'detailed'))
                ->setPaper('a4', 'portrait')->download('historico-saidas-'.$format.'.pdf');
        }
        $book = new Spreadsheet();
        $this->fill($book->getActiveSheet()->setTitle('Saídas'), array_merge([$headers], $rows));
        $this->fill($book->createSheet()->setTitle('Resumo'), array_merge([['Código', 'Item/modelo', 'Tipo', 'Quantidade']], $summary));
        $this->fill($book->createSheet()->setTitle('Filtros'), array_merge([['Campo', 'Valor']], $metadata));
        $book->setActiveSheetIndex(0);
        return response()->streamDownload(function () use ($book) {
            try { (new Xlsx($book))->save('php://output'); }
            finally { $book->disconnectWorksheets(); }
        }, 'historico-saidas.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
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
