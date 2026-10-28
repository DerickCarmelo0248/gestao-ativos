<?php
namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Item;
use App\Models\Unit;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryTransferController extends Controller
{
    private function authorizeTransfer(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'operator'], true), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeTransfer($request);
        $filters = $request->validate([
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'from_unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ]);
        $items = Item::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $selected = $items->firstWhere('id', $filters['item_id'] ?? null);
        $origin = $units->firstWhere('id', $filters['from_unit_id'] ?? null);
        $assets = collect();
        $quantity = 0;
        if ($selected && $origin) {
            $assets = Asset::where('item_id', $selected->id)->where('unit_id', $origin->id)->where('status', 'available')->orderBy('patrimony')->get();
            $quantity = StockBalance::where('item_id', $selected->id)->where('unit_id', $origin->id)->value('quantity') ?? 0;
        }
        $history = DB::table('inventory_transfers as t')
            ->join('items as i', 'i.id', '=', 't.item_id')
            ->join('units as f', 'f.id', '=', 't.from_unit_id')
            ->join('units as d', 'd.id', '=', 't.to_unit_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('assets as a', 'a.id', '=', 't.asset_id')
            ->select('t.*', 'i.name as item_name', 'f.name as origin', 'd.name as destination', 'u.name as user_name', 'a.patrimony')
            ->orderByDesc('t.id')->paginate(20)->withQueryString();
        return view('transfers.index', compact('items', 'units', 'selected', 'origin', 'assets', 'quantity', 'history'));
    }

    public function store(Request $request)
    {
        $this->authorizeTransfer($request);
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'from_unit_id' => ['required', 'integer', 'exists:units,id'],
            'to_unit_id' => ['required', 'integer', 'different:from_unit_id', 'exists:units,id'],
            'asset_ids' => ['nullable', 'array', 'max:500'],
            'asset_ids.*' => ['required', 'integer', 'distinct'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['to_unit_id.different' => 'Selecione uma unidade de destino diferente da origem.']);
        DB::transaction(function () use ($data, $request) {
            // Same item lock as quantity entries/exits protects destination balance creation.
            $item = Item::lockForUpdate()->findOrFail($data['item_id']);
            $units = Unit::whereIn('id', [$data['from_unit_id'], $data['to_unit_id']])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if (! $item->is_active || $units->count() !== 2 || $units->contains(fn ($u) => ! $u->is_active)) {
                throw ValidationException::withMessages(['item_id' => 'O item e as duas unidades precisam estar ativos.']);
            }
            $batch = (string) Str::uuid();
            $now = now();
            $audit = [
                'batch_id' => $batch, 'item_id' => $item->id, 'from_unit_id' => $data['from_unit_id'],
                'to_unit_id' => $data['to_unit_id'], 'user_id' => $request->user()->id,
                'notes' => $data['notes'] ?? null, 'created_at' => $now,
            ];
            $note = "Transferência: {$units[$data['from_unit_id']]->name} → {$units[$data['to_unit_id']]->name}. ".($data['notes'] ?? '');
            if ($item->tracking_type === 'individual') {
                $ids = $data['asset_ids'] ?? [];
                if (! count($ids)) {
                    throw ValidationException::withMessages(['asset_ids' => 'Selecione pelo menos um patrimônio.']);
                }
                $assets = Asset::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                if ($assets->count() !== count($ids) || $assets->contains(fn ($a) => $a->item_id !== $item->id || $a->unit_id !== (int) $data['from_unit_id'] || $a->status !== 'available')) {
                    throw ValidationException::withMessages(['asset_ids' => 'Um patrimônio não está disponível neste estoque. Nenhuma transferência foi realizada.']);
                }
                foreach ($assets as $asset) {
                    DB::table('inventory_transfers')->insert($audit + ['asset_id' => $asset->id, 'quantity' => 1]);
                    foreach (['transfer_out' => $data['from_unit_id'], 'transfer_in' => $data['to_unit_id']] as $type => $unitId) {
                        DB::table('asset_movements')->insert([
                            'asset_id' => $asset->id, 'unit_id' => $unitId, 'user_id' => $request->user()->id,
                            'batch_id' => $batch, 'type' => $type, 'notes' => $note, 'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                    $asset->unit_id = $data['to_unit_id'];
                    $asset->save();
                }
            } elseif ($item->tracking_type === 'quantity') {
                $qty = $data['quantity'] ?? 0;
                $from = StockBalance::where('item_id', $item->id)->where('unit_id', $data['from_unit_id'])->lockForUpdate()->first();
                $to = StockBalance::where('item_id', $item->id)->where('unit_id', $data['to_unit_id'])->lockForUpdate()->first();
                if ($qty < 1 || ! $from || $from->quantity < $qty) {
                    throw ValidationException::withMessages(['quantity' => 'Quantidade inválida ou saldo insuficiente na origem.']);
                }
                if (($to?->quantity ?? 0) + $qty > 2147483647) {
                    throw ValidationException::withMessages(['quantity' => 'O saldo de destino excederia o limite permitido.']);
                }
                if (! $to) {
                    $to = new StockBalance();
                    $to->item_id = $item->id; $to->unit_id = $data['to_unit_id']; $to->quantity = 0;
                }
                $from->quantity -= $qty; $from->save();
                $to->quantity += $qty; $to->save();
                DB::table('inventory_transfers')->insert($audit + ['quantity' => $qty]);
                foreach (['transfer_out' => $data['from_unit_id'], 'transfer_in' => $data['to_unit_id']] as $type => $unitId) {
                    DB::table('stock_movements')->insert([
                        'item_id' => $item->id, 'unit_id' => $unitId, 'user_id' => $request->user()->id,
                        'type' => $type, 'quantity' => $qty, 'notes' => $note, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            } else {
                throw ValidationException::withMessages(['item_id' => 'Tipo de controle inválido.']);
            }
        }, 3);
        return redirect()->route('transfers.index', ['item_id' => $data['item_id'], 'from_unit_id' => $data['from_unit_id']])
            ->with('status', 'Transferência concluída. Os estoques de origem e destino foram atualizados.');
    }
}
