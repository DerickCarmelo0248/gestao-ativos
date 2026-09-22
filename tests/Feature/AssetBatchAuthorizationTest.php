<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AssetBatchAuthorizationTest extends TestCase
{
    public function test_guest_cannot_open_batch_form(): void
    {
        $this->get(route('assets.batch.create'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_submit_batch(): void
    {
        $this->post(route('assets.batch.store'), [
            'item_id' => 1,
            'unit_id' => 1,
            'patrimony_start' => '11256',
            'patrimony_end' => '11268',
        ])->assertRedirect(route('login'));
    }

    public function test_unknown_role_cannot_submit_batch(): void
    {
        $user = new User();
        $user->id = 999;
        $user->role = 'unknown';

        $this->actingAs($user)
            ->post(route('assets.batch.store'), [
                'item_id' => 1,
                'unit_id' => 1,
                'patrimony_start' => '11256',
                'patrimony_end' => '11268',
            ])
            ->assertForbidden();
    }
}