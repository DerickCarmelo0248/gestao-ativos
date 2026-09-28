<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
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

    private function signIn(string $role): void
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();
        $this->actingAs($user);
    }

    private function category(): Category
    {
        return Category::create(['name' => 'Categoria '.bin2hex(random_bytes(8))]);
    }

    public function test_admin_can_edit_without_changing_id_and_keep_own_name(): void
    {
        $this->signIn('admin');
        $category = $this->category();
        $this->get(route('categories.index'))->assertOk()
            ->assertSee(route('categories.edit', $category), false)
            ->assertSee('Excluir');
        $this->get(route('categories.edit', $category))->assertOk()->assertSee($category->name);
        $this->put(route('categories.update', $category), [
            'name' => ' '.$category->name.' ', 'description' => 'Descrição atualizada',
        ])->assertSessionHasNoErrors()->assertRedirect(route('categories.index'));
        $this->assertSame('Descrição atualizada', $category->fresh()->description);
        $newName = 'Nova '.bin2hex(random_bytes(8));
        $this->put(route('categories.update', $category), ['name' => $newName])
            ->assertSessionHasNoErrors()->assertRedirect(route('categories.index'));
        $this->assertSame($newName, $category->fresh()->name);
    }

    public function test_duplicate_or_blank_names_are_rejected(): void
    {
        $this->signIn('admin');
        $category = $this->category();
        $other = $this->category();
        foreach ([' '.strtoupper($other->name).' ', '   '] as $name) {
            $this->putJson(route('categories.update', $category), ['name' => $name])
                ->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->assertSame($category->name, $category->fresh()->name);
    }

    public function test_admin_can_delete_unused_category_but_not_one_with_an_inactive_item(): void
    {
        $this->signIn('admin');
        $unused = $this->category();
        $this->delete(route('categories.destroy', $unused))
            ->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();
        $this->assertNull($unused->fresh());

        $used = $this->category();
        $item = Item::create([
            'category_id' => $used->id, 'code' => 'T-'.bin2hex(random_bytes(6)),
            'name' => 'Item teste', 'tracking_type' => 'quantity',
        ]);
        $item->is_active = false;
        $item->save();
        $this->deleteJson(route('categories.destroy', $used))
            ->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->assertNotNull($used->fresh());
        $this->assertSame($used->id, $item->fresh()->category_id);
    }

    public function test_operator_and_guest_cannot_edit_or_delete_categories(): void
    {
        $category = $this->category();
        $this->get(route('categories.edit', $category))->assertRedirect(route('login'));
        $this->put(route('categories.update', $category), ['name' => 'Alterada'])->assertRedirect(route('login'));
        $this->delete(route('categories.destroy', $category))->assertRedirect(route('login'));
        $this->signIn('operator');
        $this->get(route('categories.edit', $category))->assertForbidden();
        $this->put(route('categories.update', $category), ['name' => 'Alterada'])->assertForbidden();
        $this->delete(route('categories.destroy', $category))->assertForbidden();
        $this->assertSame($category->name, $category->fresh()->name);
    }
}
