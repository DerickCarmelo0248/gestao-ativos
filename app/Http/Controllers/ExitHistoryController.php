<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExitHistoryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Asset::class);
        Gate::authorize('viewAny', StockBalance::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'kind' => ['nullable', 'in:asset,stock'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $assets = AssetMovement::query()->where('type', 'exit');
        $stock = StockMovement::query()->where('type', 'exit');
        foreach ([$assets, $stock] as $query) {
            if (! empty($filters['unit_id'])) {
                $query->where('unit_id', $filters['unit_id']);
            }
            if (! empty($filters['from'])) {
                $query->where('created_at', '>=', Carbon::parse($filters['from'], 'America/Sao_Paulo')->startOfDay()->utc()->toIso8601String());
            }
            if (! empty($filters['to'])) {
                $query->where('created_at', '<', Carbon::parse($filters['to'], 'America/Sao_Paulo')->startOfDay()->addDay()->utc()->toIso8601String());
            }
        }
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $matchItem = fn ($q) => $q->where(fn ($q) => $q->where('name', 'ilike', '%'.$search.'%')->orWhere('code', 'ilike', '%'.$search.'%'));
            $assets->whereHas('asset', fn ($q) => $q->where(fn ($q) => $q->where('patrimony', 'ilike', '%'.$search.'%')->orWhereHas('item', $matchItem)));
            $stock->whereHas('item', $matchItem);
        }
        if (($filters['kind'] ?? '') === 'asset') {
            $stock->whereRaw('1 = 0');
        } elseif (($filters['kind'] ?? '') === 'stock') {
            $assets->whereRaw('1 = 0');
        }
        $union = $assets->select('id', 'created_at')->selectRaw("'asset' AS kind")->toBase()
            ->unionAll($stock->select('id', 'created_at')->selectRaw("'stock' AS kind")->toBase());
        $exits = DB::query()->fromSub($union, 'exits')->orderByDesc('created_at')->orderBy('kind')->orderByDesc('id')
            ->paginate(20)->withQueryString();
        $relations = ['unit', 'user', 'technician', 'destinationEstablishment', 'destinationSector', 'legacyDestinationUnit'];
        $assetRows = AssetMovement::with([...$relations, 'asset.item'])->whereIn('id', $exits->getCollection()->where('kind', 'asset')->pluck('id'))->get()->keyBy('id');
        $stockRows = StockMovement::with([...$relations, 'item'])->whereIn('id', $exits->getCollection()->where('kind', 'stock')->pluck('id'))->get()->keyBy('id');
        $exits->getCollection()->transform(function ($row) use ($assetRows, $stockRows) {
            $row->movement = ($row->kind === 'asset' ? $assetRows : $stockRows)->get($row->id);
            return $row;
        });
        $units = Unit::orderBy('name')->get();
        return view('movements.exit-history', compact('exits', 'filters', 'units'));
    }
}
