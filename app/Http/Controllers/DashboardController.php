<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\DisposalContainer;
use App\Models\StockBalance;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Asset::class);
        Gate::authorize('viewAny', StockBalance::class);
        Gate::authorize('viewAny', DisposalContainer::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ]);

        $zeroQuery = StockBalance::query()
            ->where('quantity', 0)
            ->whereHas('item', fn ($q) => $q->where('is_active', true))
            ->whereHas('unit', fn ($q) => $q->where('is_active', true));

        $monthStart = Carbon::now('America/Sao_Paulo')->startOfMonth()->utc();
        $nextMonth = $monthStart->copy()->timezone('America/Sao_Paulo')
            ->addMonth()->utc();

        $stats = [
            'available' => Asset::where('status', 'available')->count(),
            'zero' => (clone $zeroQuery)->count(),
            'pending' => DB::table('asset_replacement_requests')
                ->whereIn('status', ['pending', 'purchasing'])->count()
                + DB::table('stock_replacement_requests')
                ->whereIn('status', ['pending', 'purchasing'])->count(),
            'waiting' => Asset::where('status', 'awaiting_disposal')->count(),
            'open' => DisposalContainer::where('status', 'open')->count(),
            'disposed' => DB::table('disposal_container_items as i')
                ->join('disposal_containers as c', 'c.id', '=', 'i.disposal_container_id')
                ->where('c.status', 'closed')
                ->where('c.closed_at', '>=', $monthStart)
                ->where('c.closed_at', '<', $nextMonth)
                ->sum('i.quantity'),
        ];

        if (! empty($filters['unit_id'])) {
            $zeroQuery->where('unit_id', $filters['unit_id']);
        }

        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $zeroQuery->whereHas('item', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', '%'.$search.'%')
                        ->orWhere('code', 'ilike', '%'.$search.'%');
                });
            });
        }

        $zeroBalances = $zeroQuery->with(['item', 'unit'])
            ->orderBy('id')->paginate(6)->withQueryString();
        $units = Unit::orderBy('name')->get(['id', 'name']);

        $containers = DisposalContainer::with('unit')
            ->withSum('items', 'quantity')->where('status', 'open')
            ->orderBy('unit_id')->get();
        $lastClosed = DisposalContainer::with('unit')
            ->where('status', 'closed')->orderByDesc('closed_at')->first();

        $assetEvents = DB::table('asset_movements as m')
            ->join('assets as a', 'a.id', '=', 'm.asset_id')
            ->join('items as i', 'i.id', '=', 'a.item_id')
            ->join('units as u', 'u.id', '=', 'm.unit_id')
            ->select('m.id', 'm.type', 'm.created_at', 'i.name', 'u.name as unit_name',
                'a.patrimony', 'a.id as asset_id')
            ->orderByDesc('m.created_at')->orderByDesc('m.id')->limit(6)->get()
            ->map(function ($row) {
                $row->url = route('assets.show', $row->asset_id);
                $row->detail = 'Patrimônio '.$row->patrimony;
                return $row;
            });

        $stockEvents = DB::table('stock_movements as m')
            ->join('items as i', 'i.id', '=', 'm.item_id')
            ->join('units as u', 'u.id', '=', 'm.unit_id')
            ->leftJoin('stock_balances as b', function ($join) {
                $join->on('b.item_id', '=', 'm.item_id')->on('b.unit_id', '=', 'm.unit_id');
            })
            ->select('m.id', 'm.type', 'm.created_at', 'i.name', 'u.name as unit_name',
                'm.quantity', 'b.id as balance_id')
            ->orderByDesc('m.created_at')->orderByDesc('m.id')->limit(6)->get()
            ->map(function ($row) {
                $row->url = $row->balance_id
                    ? route('stock-balances.show', $row->balance_id)
                    : route('stock-balances.index');
                $row->detail = $row->quantity.' unidade(s)';
                return $row;
            });

        $events = $assetEvents->concat($stockEvents)
            ->sortByDesc(fn ($row) => Carbon::parse($row->created_at)->getTimestamp())
            ->take(6)->values();

        $eventLabels = [
            'entry' => 'Entrada', 'exit' => 'Saída', 'return' => 'Devolução',
            'replacement' => 'Reposição', 'container_entry' => 'Na caçamba',
            'disposal' => 'Descarte',
        ];

        return view('dashboard', compact('stats', 'filters', 'zeroBalances',
            'units', 'containers', 'lastClosed', 'events', 'eventLabels'));
    }
}
