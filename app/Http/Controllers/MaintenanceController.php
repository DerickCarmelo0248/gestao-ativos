<?php
namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Establishment;
use App\Models\Sector;
use Illuminate\Validation\Rule;
use App\Models\EquipmentMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MaintenanceController extends Controller
{
    private function authorizeAccess(Request $r): void {
        abort_unless(in_array($r->user()?->role, ['admin','operator'], true), 403);
    }
    public function create(Request $r) {
        $this->authorizeAccess($r);
        $assets = Asset::with(['item','unit'])->whereIn('status',['available','in_use'])->orderBy('patrimony')->get();
        $establishments = Establishment::where('is_active', true)->orderByRaw('CAST(code AS INTEGER)')->get(['id','code','name']);
        $sectors = Sector::where('is_active', true)->orderBy('name')->get(['id','name']);
        return view('maintenance.create', compact('assets','establishments','sectors'));
    }
    public function index(Request $r) {
        $this->authorizeAccess($r);
        $filters = $r->validate(['status'=>['nullable','in:open,returned'], 'search'=>['nullable','string','max:150']]);
        $records = EquipmentMaintenance::with(['sentBy','returnedBy'])
            ->when(($filters['status'] ?? '') === 'open', fn ($q) => $q->whereNull('returned_at'))
            ->when(($filters['status'] ?? '') === 'returned', fn ($q) => $q->whereNotNull('returned_at'))
            ->when(trim($filters['search'] ?? '') !== '', function ($q) use ($filters) {
                $search = '%'.trim($filters['search']).'%';
                $q->where(fn ($q) => $q->where('patrimony','ilike',$search)->orWhere('model_name','ilike',$search)->orWhere('ticket_number','ilike',$search));
            })->orderByDesc('sent_at')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('maintenance.index', compact('records','filters'));
    }
    public function store(Request $r) {
        $this->authorizeAccess($r);
        foreach (['patrimony','model_name','repair_location','ticket_number','sector_name','unit_name','responsible_name','reason'] as $field) {
            if (is_string($r->input($field))) $r->merge([$field=>trim($r->input($field))]);
        }
        $data = $r->validate([
            'source'=>['required','in:registered,external'],
            'asset_id'=>['required_if:source,registered','nullable','integer','exists:assets,id'],
            'patrimony'=>['required_if:source,external','nullable','string','max:50'],
            'model_name'=>['required_if:source,external','nullable','string','max:255'],
            'repair_location'=>['required','string','max:255'], 'ticket_number'=>['required','string','max:100'],
            'sector_id'=>['required','integer',Rule::exists('sectors','id')->where('is_active',true)],
            'establishment_id'=>['required','integer',Rule::exists('establishments','id')->where('is_active',true)],
            'responsible_name'=>['required','string','max:255'], 'reason'=>['required','string','max:2000'],
            'invoice_required'=>['required','boolean'],
        ]);
        $establishment = Establishment::findOrFail($data['establishment_id']);
        $data['unit_name'] = $establishment->code.' - '.$establishment->name;
        $data['sector_name'] = Sector::findOrFail($data['sector_id'])->name;
        try {
            $record = DB::transaction(function () use ($data,$r) {
                $asset = null;
                if ($data['source'] === 'registered') {
                    $asset = Asset::with('item')->lockForUpdate()->findOrFail($data['asset_id']);
                    if (!in_array($asset->status,['available','in_use'],true)) {
                        throw ValidationException::withMessages(['asset_id'=>'O equipamento não está disponível para envio à manutenção.']);
                    }
                } elseif (Asset::whereRaw('lower(patrimony) = ?', [mb_strtolower($data['patrimony'])])->exists()) {
                    throw ValidationException::withMessages(['patrimony'=>'Este patrimônio já está cadastrado. Selecione Equipamento cadastrado.']);
                }
                $record = new EquipmentMaintenance();
                foreach (['repair_location','ticket_number','sector_name','unit_name','responsible_name','reason','invoice_required'] as $field) $record->$field = $data[$field];
                $record->asset_id = $asset?->id;
                $record->previous_status = $asset?->status;
                $record->patrimony = $asset?->patrimony ?? $data['patrimony'];
                $record->model_name = $asset?->item->name ?? $data['model_name'];
                $record->sent_by = $r->user()->id;
                $record->sent_at = now();
                $record->save();
                if ($asset) {
                    $asset->status = 'maintenance'; $asset->save();
                    $this->movement($asset, $r->user()->id, 'maintenance_sent', "Manutenção #{$record->id}. Local: {$record->repair_location}. Chamado: {$record->ticket_number}. Motivo: {$record->reason}");
                }
                return $record;
            }, 3);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['patrimony'=>'Já existe uma manutenção em aberto para este patrimônio.']);
        }
        return redirect()->route('maintenance.show', $record)->with('status','Envio para manutenção registrado.');
    }
    public function show(Request $r, EquipmentMaintenance $maintenance) {
        $this->authorizeAccess($r);
        $maintenance->load(['sentBy','returnedBy']);
        $greeting = now()->timezone('America/Sao_Paulo')->hour < 12 ? 'Bom dia' : 'Boa tarde';
        $body = "{$greeting}, Gabriela,\n\nEstarei enviando o seguinte equipamento para {$maintenance->repair_location}:\n\nModelo: {$maintenance->model_name}\nPatrimônio: {$maintenance->patrimony}\nResponsável: {$maintenance->responsible_name}\nUnidade: {$maintenance->unit_name}\nSetor: {$maintenance->sector_name}\nMotivo: {$maintenance->reason}\nChamado: {$maintenance->ticket_number}\n\nAtenciosamente.";
        $drafts = [['label'=>'E-mail de manutenção','subject'=>'Aparelho encaminhado para manutenção','body'=>$body]];
        if ($maintenance->invoice_required) {
            $drafts[] = ['label'=>'Solicitar NF', 'subject'=>'Solicitação de nota fiscal — Chamado '.$maintenance->ticket_number,
                'body'=>"{$greeting}, Gabriela,\n\nNecessito que realize a solicitação de nota fiscal com os seguintes dados abaixo:\n\nSaída: {$maintenance->unit_name} ({$maintenance->sector_name})\nEntrada: {$maintenance->repair_location}\n\nEquipamento: {$maintenance->model_name}\nPatrimônio: {$maintenance->patrimony}\nValor: R$ [preencher]\n\nChamado: {$maintenance->ticket_number}\n\nObrigado.\n\nAtenciosamente."];
        }
        return view('maintenance.show', compact('maintenance','drafts'));
    }
    public function returnEquipment(Request $r, EquipmentMaintenance $maintenance) {
        $this->authorizeAccess($r);
        $data = $r->validate(['confirmed'=>['required','accepted'], 'return_notes'=>['nullable','string','max:2000']]);
        DB::transaction(function () use ($maintenance,$r,$data) {
            $record = EquipmentMaintenance::lockForUpdate()->findOrFail($maintenance->id);
            if ($record->returned_at) throw ValidationException::withMessages(['confirmed'=>'O retorno já foi registrado.']);
            if ($record->asset_id) {
                $asset = Asset::lockForUpdate()->findOrFail($record->asset_id);
                if ($asset->status !== 'maintenance') throw ValidationException::withMessages(['confirmed'=>'A situação do equipamento foi alterada. Confira o cadastro.']);
                $asset->status = $record->previous_status; $asset->save();
                $this->movement($asset, $r->user()->id, 'maintenance_return', "Retorno da manutenção #{$record->id}. ".($data['return_notes'] ?? ''));
            }
            $record->returned_by = $r->user()->id;
            $record->returned_at = now();
            $record->return_notes = $data['return_notes'] ?? null;
            $record->save();
        },3);
        return redirect()->route('maintenance.show',$maintenance)->with('status','Retorno da manutenção registrado.');
    }
    private function movement(Asset $asset, int $user, string $type, string $notes): void {
        DB::table('asset_movements')->insert(['asset_id'=>$asset->id,'unit_id'=>$asset->unit_id,'user_id'=>$user,
            'batch_id'=>(string) Str::uuid(),'type'=>$type,'notes'=>$notes,'created_at'=>now(),'updated_at'=>now()]);
    }
}
