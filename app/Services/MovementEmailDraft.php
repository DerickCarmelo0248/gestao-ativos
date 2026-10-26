<?php
namespace App\Services;

class MovementEmailDraft
{
    private static function greeting(): string
    {
        $hour = now()->timezone('America/Sao_Paulo')->hour;
        return $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
    }

    public static function invoice($assets, array $data): array
    {
        $destination = \App\Models\Establishment::findOrFail($data['destination_establishment_id']);
        $sector = \App\Models\Sector::findOrFail($data['destination_sector_id']);
        $text = self::greeting().", Gabriela,\n\nNecessito que realize a solicitação de nota fiscal com os seguintes dados abaixo:\n\n";
        $text .= "Saída: Est 11 - Votuporanga III (T.I.)\n";
        $text .= "Entrada: Est {$destination->code} - {$destination->name} ({$sector->name})\n\n";
        foreach ($assets->groupBy('item_id') as $group) {
            $model = $group->first()->item->name;
            $patrimonies = $group->pluck('patrimony')->implode(', ');
            $text .= "Equipamento: {$model}\nPatrimônio: {$patrimonies}\nValor: R$ [preencher]\n\n";
        }
        $text .= "Chamado: {$data['ticket_number']}\n\nObrigado.\n\nAtenciosamente.";
        return ['label' => 'Solicitar NF', 'subject' => 'Solicitação de nota fiscal — Chamado '.$data['ticket_number'], 'body' => $text];
    }

    public static function disposal($item): ?array
    {
        if (! $item->patrimony) {
            return null;
        }
        $text = self::greeting().",\n\nGostaria de solicitar a baixa patrimonial do equipamento listado abaixo.\n\n";
        $text .= "Motivo do descarte: {$item->reason}\n\nRelação de itens:\n\n";
        $text .= "- {$item->item_name}\n  Patrimônio: {$item->patrimony}\n  Unidade de origem: {$item->origin_unit_name}\n\nAtenciosamente.";
        return ['label' => 'Solicitar baixa', 'subject' => 'Solicitação de baixa patrimonial — '.$item->patrimony, 'body' => $text];
    }
}
