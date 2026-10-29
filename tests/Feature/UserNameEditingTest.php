<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserNameEditingTest extends TestCase
{
    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue(app()->environment('testing'));
        $connection = DB::selectOne('SELECT current_database() AS banco, current_user AS usuario');
        $this->assertSame('gestao_ativos_test', $connection->banco);
        $this->assertSame('gestao_test', $connection->usuario);
        $this->artisan('migrate')->assertExitCode(0);
        DB::beginTransaction();
        $this->transactionStarted = true;
    }

    protected function tearDown(): void
    {
        try {
            if ($this->transactionStarted) {
                DB::rollBack();
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_admin_updates_only_name_and_normalizes_spaces(): void
    {
        $admin = User::factory()->create(); $admin->role = 'admin'; $admin->save();
        $target = User::factory()->create()->fresh();
        $this->actingAs($admin);
        $this->get(route('users.index'))->assertOk()->assertSee('Editar nome');
        $this->get(route('users.name.edit', $target))->assertOk()->assertSee($target->email);
        $this->put(route('users.name.update', $target), [
            'name' => '  João   da Silva  ', 'email' => 'changed@example.com', 'role' => 'admin', 'password' => 'Changed123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));
        $fresh = $target->fresh();
        $this->assertSame('João da Silva', $fresh->name);
        foreach (['email', 'role', 'password'] as $field) {
            $this->assertSame($target->$field, $fresh->$field);
        }
        foreach ([' ', str_repeat('a', 256), ['invalid']] as $name) {
            $this->putJson(route('users.name.update', $target), ['name' => $name])->assertUnprocessable();
        }
        $this->assertSame('João da Silva', $target->fresh()->name);
    }

    public function test_guest_and_operator_cannot_edit_names(): void
    {
        $target = User::factory()->create();
        $this->get(route('users.name.edit', $target))->assertRedirect(route('login'));
        $this->put(route('users.name.update', $target), ['name' => 'Novo'])->assertRedirect(route('login'));
        $operator = User::factory()->create(); $operator->role = 'operator'; $operator->save();
        $this->actingAs($operator);
        $this->get(route('users.name.edit', $target))->assertForbidden();
        $this->put(route('users.name.update', $target), ['name' => 'Novo'])->assertForbidden();
        $this->assertSame($target->name, $target->fresh()->name);
    }
}