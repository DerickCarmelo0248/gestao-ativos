<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSecurityTest extends TestCase
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

    private function user(string $role = 'operator'): User
    {
        $user = User::factory()->create(['password' => 'SenhaInicialTeste123!']);
        $user->role = $role;
        $user->save();

        return $user;
    }

    public function test_guest_cannot_manage_users_or_change_password(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->post(route('users.store'), [])->assertRedirect(route('login'));
        $this->put(route('account.password.update'), [])->assertRedirect(route('login'));
    }

    public function test_operator_cannot_create_admin_even_by_direct_request(): void
    {
        $user = $this->user();
        $count = User::count();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->post(route('users.store'), [
            'name' => 'Tentativa', 'email' => 'tentativa@example.test',
            'role' => 'admin', 'password' => 'SenhaDeTeste123!',
            'password_confirmation' => 'SenhaDeTeste123!',
        ])->assertForbidden();
        $this->assertSame($count, User::count());
    }

    public function test_admin_creates_operator_with_hashed_password(): void
    {
        $email = 'ci-'.bin2hex(random_bytes(6)).'@example.test';
        $this->actingAs($this->user('admin'))->post(route('users.store'), [
            'name' => 'Operador de teste', 'email' => strtoupper($email),
            'role' => 'operator', 'password' => 'SenhaDeTeste123!',
            'password_confirmation' => 'SenhaDeTeste123!',
        ])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));
        $created = User::where('email', $email)->sole();
        $this->assertSame('operator', $created->role);
        $this->assertNotSame('SenhaDeTeste123!', $created->password);
        $this->assertTrue(Hash::check('SenhaDeTeste123!', $created->password));
    }

    public function test_wrong_current_password_does_not_change_credentials(): void
    {
        $user = $this->user();
        $hash = $user->password;
        $this->actingAs($user)->putJson(route('account.password.update'), [
            'current_password' => 'SenhaErrada123!',
            'password' => 'NovaSenhaDeTeste123!',
            'password_confirmation' => 'NovaSenhaDeTeste123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_confirmation_is_required_and_only_own_password_changes(): void
    {
        $user = $this->user();
        $other = $this->user('admin');
        $otherHash = $other->password;
        $this->actingAs($user)->putJson(route('account.password.update'), [
            'current_password' => 'SenhaInicialTeste123!',
            'password' => 'NovaSenhaDeTeste123!',
            'password_confirmation' => 'OutraSenhaDeTeste123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertTrue(Hash::check('SenhaInicialTeste123!', $user->fresh()->password));
        $this->put(route('account.password.update'), [
            'user_id' => $other->id, 'role' => 'admin',
            'current_password' => 'SenhaInicialTeste123!',
            'password' => 'NovaSenhaDeTeste123!',
            'password_confirmation' => 'NovaSenhaDeTeste123!',
        ])->assertSessionHasNoErrors()->assertRedirect(route('account.edit'));
        $this->assertTrue(Hash::check('NovaSenhaDeTeste123!', $user->fresh()->password));
        $this->assertSame('operator', $user->fresh()->role);
        $this->assertSame($otherHash, $other->fresh()->password);
    }
}
