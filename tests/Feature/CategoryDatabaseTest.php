<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryDatabaseTest extends TestCase
{
    public function test_database_rejects_duplicate_category_names(): void
    {
        $connection = DB::selectOne(
            'SELECT current_database() AS banco, current_user AS usuario'
        );

        // Verifica o destino antes de qualquer alteração.
        $this->assertSame('testing', app()->environment());
        $this->assertSame('gestao_ativos_test', $connection->banco);
        $this->assertSame('gestao_test', $connection->usuario);

        $this->artisan('migrate')->assertExitCode(0);

        DB::beginTransaction();

        try {
            $name = 'Monitor '.bin2hex(random_bytes(8));

            Category::create(['name' => $name]);

            try {
                Category::create([
                    'name' => ' '.strtoupper($name).' ',
                ]);

                $this->fail('O banco aceitou uma categoria duplicada.');
            } catch (QueryException $exception) {
                $this->assertSame(
                    '23505',
                    $exception->errorInfo[0] ?? null
                );
            }
        } finally {
            DB::rollBack();
        }
    }

    public function test_validation_rejects_duplicate_category_names(): void
{
    $connection = DB::selectOne(
        'SELECT current_database() AS banco, current_user AS usuario'
    );

    $this->assertSame('testing', app()->environment());
    $this->assertSame('gestao_ativos_test', $connection->banco);
    $this->assertSame('gestao_test', $connection->usuario);

    $this->artisan('migrate')->assertExitCode(0);

    DB::beginTransaction();

    try {
        $name = 'Monitor '.bin2hex(random_bytes(8));

        Category::create(['name' => $name]);

        $request = new \App\Http\Requests\StoreCategoryRequest();

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['name' => ' '.strtoupper($name).' '],
            $request->rules(),
            $request->messages()
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Já existe uma categoria com esse nome.',
            $validator->errors()->first('name')
        );
    } finally {
        DB::rollBack();
    }
}
}