<?php
namespace Tests\Unit;

use App\Services\MovementEmailDraft;
use Tests\TestCase;

class MovementEmailDraftTest extends TestCase
{
    public function test_disposal_uses_recorded_reason_and_identity(): void
    {
        $draft = MovementEmailDraft::disposal((object) [
            'patrimony' => '000123', 'item_name' => 'Monitor teste',
            'reason' => 'Obsoleto', 'origin_unit_name' => 'Rio Preto',
        ]);
        $this->assertStringContainsString('000123', $draft['body']);
        $this->assertStringContainsString('Monitor teste', $draft['body']);
        $this->assertStringContainsString('Obsoleto', $draft['body']);
        $this->assertStringNotContainsString('danificado', $draft['body']);
        $this->assertNull(MovementEmailDraft::disposal((object) ['patrimony' => null]));
    }
}
