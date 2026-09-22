<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstablishmentSeeder extends Seeder
{
    public function run(): void
    {
        $establishments = [
            1 => 'Votuporanga I',
            2 => 'Votuporanga II',
            3 => 'Rio Preto I',
            5 => 'Guarulhos',
            9 => 'Limeira',
            11 => 'Votuporanga III',
            12 => 'Rio Preto II',
            13 => 'Aparecida do Taboado',
            14 => 'Campo Grande',
            15 => 'Mirassol III',
            16 => 'Votuporanga IV',
            17 => 'Roseira',
            18 => 'Rio Preto III',
            19 => 'Manaus',
            20 => 'Rio Preto IV',
            21 => 'Ribeirão Preto',
            22 => 'Cabo',
            23 => 'Cuiabá',
            24 => 'Guarulhos II',
            25 => 'S J Pinhais',
            26 => 'Nova Sta Rita',
            27 => 'Simões Filho',
            28 => 'Betim',
            29 => 'Uberlandia',
            30 => 'Marituba',
            31 => 'Cambé',
            32 => 'São Luiz',
            33 => 'Imperatriz',
            34 => 'Anapolis',
            35 => 'Nova Iguaçu',
            36 => 'Porto Velho',
            37 => 'Penha',
            38 => 'Mirassol',
            39 => 'Aquiraz',
            40 => 'Simoes Filhos II',
            41 => 'Cabo de Santo Agostinho',
            42 => 'Coroados',
            43 => 'Concórdia',
            44 => 'São José do Mipibu',
            45 => 'Rio Preto V',
            46 => 'Cascavel',
            47 => 'Votuporanga 5',
            48 => 'Votuporanga 6',
            49 => 'Rondonópolis - MT',
            50 => 'Teresina - PI',
            51 => 'Rio Preto VI',
            52 => 'Roseira 2',
            53 => 'Chapecó',
            54 => 'Votuporanga VII',
            55 => 'Rio Preto VII',
            56 => 'Votuporanga VIII',
            57 => 'Gurupi',
            58 => 'Votuporanga 9',
            59 => 'Luiz E.Magalhães',
            60 => 'Icara',
            61 => 'São José do Rio Preto 8',
            62 => 'São José R.Preto 9 - ICEQ',
            63 => 'Ribas do Rio Pardo',
            64 => 'Guararema',
            65 => 'Mirassol II',
            66 => 'Mirassol IV',
            67 => 'Nova Xavantina',
            68 => 'Rio Verde',
            69 => 'Mirassol 5',
        ];

        $now = now();
        $rows = [];

        foreach ($establishments as $code => $name) {
            $rows[] = [
                'code' => (string) $code,
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('establishments')->upsert(
            $rows,
            ['code'],
            ['name', 'updated_at']
        );
    }
}