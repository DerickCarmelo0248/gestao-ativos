<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LowStock
{
    public static function query(): Builder
    {
        $assets = DB::table('assets')->select('item_id', 'unit_id')
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'available') AS available")
            ->groupBy('item_id', 'unit_id');

        $balances = DB::table('items as i')->crossJoin('units as u')
            ->leftJoin('stock_balances as b', function ($join) {
                $join->on('b.item_id', '=', 'i.id')->on('b.unit_id', '=', 'u.id');
            })
            ->leftJoinSub($assets, 'a', function ($join) {
                $join->on('a.item_id', '=', 'i.id')->on('a.unit_id', '=', 'u.id');
            })
            ->where('i.is_active', true)->where('u.is_active', true)
            ->where(function ($query) {
                $query->where('i.minimum_stock', '>', 0)
                    ->orWhereNotNull('b.id')->orWhereNotNull('a.item_id');
            })
            ->select('i.id as item_id', 'i.name', 'i.code', 'i.tracking_type',
                'i.minimum_stock', 'u.id as unit_id', 'u.name as unit_name', 'b.id as balance_id')
            ->selectRaw("CASE WHEN i.tracking_type = 'individual' THEN COALESCE(a.available, 0) ELSE COALESCE(b.quantity, 0) END AS quantity");

        return DB::query()->fromSub($balances, 'inventory')
            ->whereColumn('quantity', '<=', 'minimum_stock');
    }
}
