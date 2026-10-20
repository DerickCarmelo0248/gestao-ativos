<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DisposalContainerExportTest extends TestCase
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

    public function test_exports_include_all_materials_and_correct_quantities(): void
    {
        $this->signIn('operator');
        $asset = $this->asset();
        $id = DB::table('disposal_containers')->insertGetId(['unit_id' => $asset->unit_id, 'opened_by' => auth()->id(), 'opened_at' => now(), 'status' => 'open']);
        for ($i = 0; $i < 21; $i++) {
            DB::table('disposal_container_items')->insert([
                'disposal_container_id' => $id, 'item_id' => $asset->item_id, 'origin_unit_id' => $asset->unit_id,
                'added_by' => auth()->id(), 'quantity' => 3, 'category_name' => 'Mouses', 'item_code' => '001',
                'item_name' => 'Mouse preservado', 'origin_unit_name' => 'Origem preservada', 'reason' => '=1+1',
                'added_at' => now(), 'registration_source' => 'external',
            ]);
        }
        $response = $this->get(route('disposal-containers.export', ['container' => $id, 'format' => 'xlsx']));
        $response->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'container-export-');
        try {
            file_put_contents($file, $response->streamedContent());
            $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $this->assertSame(63, $book->getSheetByName('Resumo')->getCell('B2')->getValue());
            $detail = $book->getSheetByName('Detalhado');
            $this->assertSame(22, $detail->getHighestRow());
            $this->assertSame('001', $detail->getCell('D2')->getValue());
            $this->assertSame('Mouse preservado', $detail->getCell('E2')->getValue());
            $this->assertSame('s', $detail->getCell('L2')->getDataType());
            $this->assertSame(auth()->user()->name, $detail->getCell('K2')->getValue());
            $book->disconnectWorksheets();
        } finally { unlink($file); }
        foreach (['pdf-simple', 'pdf-detailed'] as $format) {
            $response = $this->get(route('disposal-containers.export', ['container' => $id, 'format' => $format]));
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
        DB::table('disposal_containers')->where('id', $id)->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => auth()->id()]);
        $this->get(route('disposal-containers.export', ['container' => $id, 'format' => 'pdf-simple']))->assertOk();
        $this->getJson(route('disposal-containers.export', ['container' => $id, 'format' => 'invalid']))->assertUnprocessable();
        auth()->logout();
        $this->get(route('disposal-containers.export', ['container' => $id, 'format' => 'xlsx']))->assertRedirect(route('login'));
    }
}