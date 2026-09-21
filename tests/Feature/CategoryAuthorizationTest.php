<?php

namespace Tests\Feature;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\User;
use Tests\TestCase;

class CategoryAuthorizationTest extends TestCase
{
    public function test_admin_can_create_categories(): void
    {
        $user = new User();
        $user->role = 'admin';

        $request = new StoreCategoryRequest();
        $request->setUserResolver(fn () => $user);

        $this->assertTrue($request->authorize());
    }

    public function test_operator_cannot_create_categories(): void
    {
        $user = new User();
        $user->role = 'operator';

        $request = new StoreCategoryRequest();
        $request->setUserResolver(fn () => $user);

        $this->assertFalse($request->authorize());
    }

    public function test_guest_cannot_create_categories(): void
    {
        $request = new StoreCategoryRequest();
        $request->setUserResolver(fn () => null);

        $this->assertFalse($request->authorize());
    }

    public function test_operator_cannot_open_category_form(): void
{
    $user = new User();
    $user->id = 999;
    $user->role = 'operator';

    $this->actingAs($user)
        ->get(route('categories.create'))
        ->assertForbidden();
}

public function test_operator_cannot_submit_category(): void
{
    $user = new User();
    $user->id = 999;
    $user->role = 'operator';

    $this->actingAs($user)
        ->post(route('categories.store'), [
            'name' => 'Categoria bloqueada',
        ])
        ->assertForbidden();
}

public function test_guest_is_redirected_to_login(): void
{
    $this->get(route('categories.create'))
        ->assertRedirect(route('login'));

    $this->post(route('categories.store'), [
        'name' => 'Categoria bloqueada',
    ])->assertRedirect(route('login'));
}

public function test_admin_can_view_categories(): void
{
    $user = new User();
    $user->role = 'admin';

    $this->assertTrue(
        $user->can('viewAny', \App\Models\Category::class)
    );
}

public function test_operator_can_view_categories(): void
{
    $user = new User();
    $user->role = 'operator';

    $this->assertTrue(
        $user->can('viewAny', \App\Models\Category::class)
    );
}

public function test_unknown_role_cannot_view_categories(): void
{
    $user = new User();
    $user->role = 'unknown';

    $this->assertFalse(
        $user->can('viewAny', \App\Models\Category::class)
    );
}

public function test_guest_cannot_open_category_listing(): void
{
    $this->get(route('categories.index'))
        ->assertRedirect(route('login'));
}
}