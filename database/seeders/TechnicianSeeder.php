<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TechnicianSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'CAIO CESAR DE ALMEIDA',
            'DERICK CRIADO CARMELO',
            'DONIZETE CORREIA JUNIOR',
            'DOUGLAS MARINHO PINHEIRO',
            'EMERSON DONA MELLO',
            'GABRIEL DE LIMA TEIXEIRA',
            'GABRIELA CAMPOS DE LIMA GONCALVES',
            'JOAO PEDRO DE PAULO GUEDES',
            'LEANDRO LUIZ BATISTA BORGES',
            'LINNIKER ROBERTO DE CARVALHO',
            'LUAN LUIZ LAURENTINO',
            'MATHEUS GONCALVES BENEVIDES',
            'MAURICIO DA SILVA BELUCCI',
            'RAFAEL MORETTI',
            'RUAN TORRES LATORRE',
            'UMBERTO ANACLETO FABRI JARDIM',
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

        DB::table('technicians')->upsert(
            $rows,
            ['name'],
            ['name']
        );
    }
}