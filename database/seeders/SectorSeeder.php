<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Acabamento Final',
            'Acoplagem  de 3o Eixo',
            'Acoplagem \\ Kits',
            'Acoplagem de Produtos',
            'Acoplamento de Caçambas',
            'Administração de Vendas',
            'Almoxarifado',
            'Alongamento/Instalações',
            'Comercial',
            'Compras',
            'Confecção de Lonas',
            'Controle da Qualidade',
            'Corte Dobra',
            'Corte e Serra',
            'Engenharia Civil',
            'Engenharia de Processos',
            'Engenharia e Projetos',
            'Expedição',
            'Exportação',
            'Extrusão e Corte',
            'Fábrica de Tubos',
            'Fabricação de Fibra Vidro',
            'Fabricação de Poliuretano',
            'Fabricação de Rodas',
            'Fábricação de Tecido Vidro',
            'Fabricação de Tinta a Pó',
            'Fabricação Rebites',
            'Ferramentaria',
            'Forjaria',
            'Fundição Aço',
            'Fundição Alumínio',
            'Fundição II',
            'Geral Comercial',
            'Geral da Administração',
            'Geral Fabrica',
            'Gestão Contabil',
            'Gestão de Pessoas',
            'Gestão de Serviços',
            'Gestão Financeira',
            'Identificação Visual',
            'Injeção de Plastico',
            'Juridico',
            'Laboratório de Tinta',
            'Logomarca',
            'Manutenção Industrial',
            'Marcenaria',
            'Marketing',
            'Medicina do Trabalho',
            'Metrologia',
            'Montagem  de Pistao Hidraulico',
            'Montagem Carroceria Bebidas',
            'Montagem de Bases',
            'Montagem de Caçambas',
            'Montagem de Doly',
            'Montagem de Eixos',
            'Montagem de Estruturas',
            'Montagem de Furgão',
            'Montagem de Furgão e Semi Rebo',
            'Montagem de Furgao Lonado',
            'Montagem de Guindaste e Rollon',
            'Montagem de Kits',
            'Montagem de Kits Almoxarifado',
            'Montagem de Kits Isoplastic /',
            'Montagem de Pecas e Componente',
            'Montagem de Portas e Tampas',
            'Montagem de Portas Isoplastic',
            'Montagem de Produtos',
            'Montagem de Quadro Furgão',
            'Montagem de Suspensão',
            'Montagem de Tanques',
            'Montagem Industrial',
            'P&D',
            'Perfiladeira',
            'Pintura',
            'Pintura Reforma',
            'Planejamento e Controle de Pro',
            'Preparação de Tinta Líquida',
            'Processos',
            'Pultrusão de Perfil',
            'Reformas e Assistencia Tecnica',
            'RTM',
            'Segurança do Trabalho',
            'Sustentabilidade/ESG',
            'Tecnologia da Informação',
            'Transportes',
            'U. C. Facchini',
            'Usinagem',
            'Usinagem II',
        ];

        $now = now();
        $rows = [];

        foreach ($names as $name) {
            $rows[] = [
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('sectors')->upsert(
            $rows,
            ['name'],
            ['name']
        );
    }
}
