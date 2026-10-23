<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
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

    public function test_admin_resets_password_and_revokes_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->create(['password' => 'AdminPass123']);
        $admin->role = 'admin'; $admin->save();
        $target = User::factory()->create(['password' => 'OldPassword123', 'remember_token' => 'old-token']);
        DB::table('sessions')->insert(['id' => 'reset-test-'.bin2hex(random_bytes(8)), 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($admin);
        $this->get(route('users.password.edit', $target))->assertOk()->assertSee($target->email);
        $this->put(route('users.password.reset', $target), [
            'current_password' => 'AdminPass123', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
            'role' => 'admin',
        ])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword123', $target->fresh()->password));
        $this->assertSame('operator', $target->fresh()->role);
        $this->assertNotSame('old-token', $target->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('AdminPass123', $admin->fresh()->password));
    }

    public function test_guest_operator_and_invalid_passwords_are_blocked(): void
    {
        $target = User::factory()->create();
        $original = $target->password;
        $url = route('users.password.reset', $target);
        $this->put($url, [])->assertRedirect(route('login'));
        $operator = User::factory()->create();
        $operator->role = 'operator'; $operator->save();
        $this->actingAs($operator);
        $this->get(route('users.password.edit', $target))->assertForbidden();
        $this->put($url, [])->assertForbidden();
        $operator->role = 'admin'; $operator->password = 'AdminPass123'; $operator->save();
        foreach ([
            ['current_password' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'],
            ['current_password' => 'AdminPass123', 'password' => 'short', 'password_confirmation' => 'short'],
            ['current_password' => 'AdminPass123', 'password' => 'NewPassword123', 'password_confirmation' => 'different'],
            ['current_password' => 'AdminPass123', 'password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)],
        ] as $data) {
            $this->putJson($url, $data)->assertUnprocessable();
        }
        $this->assertSame($original, $target->fresh()->password);
    }
}