<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaintenanceTest extends TestCase
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

    private function data(): array {
        $establishment = \App\Models\Establishment::create(['code'=>(string) random_int(100000,999999999),'name'=>'Rio Preto I']);
        $sector = \App\Models\Sector::create(['name'=>'Montagem '.bin2hex(random_bytes(6))]);
        return ['establishment_id'=>$establishment->id,'sector_id'=>$sector->id,'source'=>'external','patrimony'=>'EXT-'.bin2hex(random_bytes(6)), 'model_name'=>'Tablet exemplo',
            'repair_location'=>'Oficina teste','ticket_number'=>'12345','sector_name'=>'Montagem',
            'unit_name'=>'Rio Preto I','responsible_name'=>'Responsável teste','reason'=>'Tela trincada','invoice_required'=>1];
    }
    public function test_registered_equipment_is_blocked_until_return_and_restores_previous_status(): void {
        $this->signIn('operator');
        foreach (['available','in_use'] as $status) {
            $asset=$this->asset(); $asset->status=$status; $asset->save();
            $data=array_replace($this->data(), ['source'=>'registered','asset_id'=>$asset->id]);
            $this->post(route('maintenance.store'),$data)->assertSessionHasNoErrors()->assertRedirect();
            $record=\App\Models\EquipmentMaintenance::where('asset_id',$asset->id)->firstOrFail();
            $this->assertSame('maintenance',$asset->fresh()->status);
            $this->assertSame($asset->patrimony,$record->patrimony);
            $this->postJson(route('maintenance.store'),$data)->assertUnprocessable();
            $this->get(route('maintenance.show',$record))->assertOk()->assertSee('Solicitar NF')->assertSee($asset->patrimony)->assertSee('Oficina teste');
            $this->post(route('maintenance.return',$record),['confirmed'=>1,'return_notes'=>'Reparado'])->assertSessionHasNoErrors();
            $this->assertSame($status,$asset->fresh()->status);
            $this->assertSame($asset->unit_id,$asset->fresh()->unit_id);
            $this->assertSame(2,$asset->movements()->count());
            $this->postJson(route('maintenance.return',$record),['confirmed'=>1])->assertUnprocessable();
        }
    }
    public function test_external_history_and_optional_email_do_not_change_inventory(): void {
        $this->signIn('admin');
        $data=$this->data(); $data['invoice_required']=0;
        $before=\App\Models\Asset::count();
        $this->post(route('maintenance.store'),$data)->assertSessionHasNoErrors();
        $record=\App\Models\EquipmentMaintenance::where('patrimony',$data['patrimony'])->firstOrFail();
        $this->assertNull($record->asset_id);
        $establishment = \App\Models\Establishment::findOrFail($data['establishment_id']);
        $this->assertSame($establishment->code.' - '.$establishment->name, $record->unit_name);
        $this->assertSame(\App\Models\Sector::findOrFail($data['sector_id'])->name, $record->sector_name);
        $this->get(route('maintenance.show',$record))->assertOk()->assertDontSee('Solicitar NF')->assertSee('E-mail de manutenção');
        $this->postJson(route('maintenance.store'),$data)->assertUnprocessable();
        $this->get(route('maintenance.index',['status'=>'open','search'=>$data['patrimony']]))->assertOk()->assertSee($data['patrimony']);
        $this->postJson(route('maintenance.return',$record),[])->assertUnprocessable();
        $this->post(route('maintenance.return',$record),['confirmed'=>1])->assertSessionHasNoErrors();
        $this->assertSame($before,\App\Models\Asset::count());
        $this->get(route('maintenance.index',['status'=>'returned','search'=>$data['patrimony']]))->assertOk()->assertSee($data['patrimony']);
        $this->post(route('maintenance.store'),$data)->assertSessionHasNoErrors();
    }
    public function test_access_validation_and_external_duplicate_are_protected(): void {
        $this->get(route('maintenance.create'))->assertRedirect(route('login'));
        $this->post(route('maintenance.store'),$this->data())->assertRedirect(route('login'));
        $this->signIn('operator');
        $asset=$this->asset();
        $this->postJson(route('maintenance.store'),array_replace($this->data(),['patrimony'=>$asset->patrimony]))->assertUnprocessable();
        foreach (['disposed','in_container','awaiting_disposal'] as $status) {
            $asset->status=$status; $asset->save();
            $this->postJson(route('maintenance.store'),array_replace($this->data(),['source'=>'registered','asset_id'=>$asset->id]))->assertUnprocessable();
        }
        $this->postJson(route('maintenance.store'),array_replace($this->data(),['reason'=>'']))->assertUnprocessable();
        $data = $this->data();
        $this->postJson(route('maintenance.store'),array_replace($data,['establishment_id'=>0,'sector_id'=>0]))->assertUnprocessable()->assertJsonValidationErrors(['establishment_id','sector_id']);
        DB::table('establishments')->where('id',$data['establishment_id'])->update(['is_active'=>false]);
        DB::table('sectors')->where('id',$data['sector_id'])->update(['is_active'=>false]);
        $this->postJson(route('maintenance.store'),$data)->assertUnprocessable()->assertJsonValidationErrors(['establishment_id','sector_id']);
        $this->get(route('maintenance.create'))->assertOk()->assertSee('name="establishment_id"',false)->assertSee('name="sector_id"',false);
    }
}
