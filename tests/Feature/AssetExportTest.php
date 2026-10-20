<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssetExportTest extends TestCase
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

    private function asset(): \App\Models\Asset
    {
        $item = Item::create(['category_id' => $this->category()->id,
            'code' => 'EDIT-'.bin2hex(random_bytes(6)), 'name' => 'Monitor', 'tracking_type' => 'individual']);
        $unit = \App\Models\Unit::create(['code' => bin2hex(random_bytes(3)), 'name' => 'Unidade teste']);
        return \App\Models\Asset::create(['item_id' => $item->id, 'unit_id' => $unit->id,
            'patrimony' => 'PAT-'.bin2hex(random_bytes(8))]);
    }

    public function test_exports_respect_filters_and_produce_readable_files(): void
    {
        $this->signIn('operator');
        $asset = $this->asset();
        $asset->patrimony = '000123';
        $asset->notes = '=1+1';
        $asset->save();
        $other = $this->asset();
        $filters = ['item_id' => $asset->item_id, 'status' => 'available', 'unit_id' => $asset->unit_id];
        foreach (['pdf-simple', 'pdf-detailed'] as $format) {
            $response = $this->get(route('assets.export', $filters + ['format' => $format]));
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
        $response = $this->get(route('assets.export', $filters + ['format' => 'xlsx']));
        $response->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'asset-export-');
        try {
            file_put_contents($file, $response->streamedContent());
            $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $book->getSheetByName('Equipamentos');
            $this->assertSame(2, $sheet->getHighestRow());
            $this->assertSame('000123', $sheet->getCell('A2')->getValue());
            $this->assertSame('=1+1', $sheet->getCell('G2')->getValue());
            $this->assertSame('s', $sheet->getCell('G2')->getDataType());
            $this->assertNotNull($book->getSheetByName('Movimentações'));
            $book->disconnectWorksheets();
        } finally { unlink($file); }
    }

    public function test_guest_and_invalid_filters_are_rejected(): void
    {
        $this->get(route('assets.export', ['format' => 'xlsx']))->assertRedirect(route('login'));
        $this->signIn('operator');
        $this->getJson(route('assets.export', ['format' => 'exe']))->assertUnprocessable();
        $this->getJson(route('assets.export', ['format' => 'xlsx', 'status' => 'invalid']))->assertUnprocessable();
    }
}