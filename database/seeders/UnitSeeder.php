<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'VOT', 'name' => 'Votuporanga'],
            ['code' => 'ROS', 'name' => 'Roseira'],
            ['code' => 'RPR', 'name' => 'Rio Preto'],
            ['code' => 'GRU', 'name' => 'Guarulhos'],
        ];

        foreach ($units as $unit) {
            DB::table('units')->upsert(
                [
                    ...$unit,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                ['code'],
                ['name', 'updated_at']
            );
        }
    }
}