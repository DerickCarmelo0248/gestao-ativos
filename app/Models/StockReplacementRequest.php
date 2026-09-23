<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReplacementRequest extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(
            StockMovement::class,
            'stock_movement_id'
        );
    }

    public function destinationEstablishment(): BelongsTo
    {
        return $this->belongsTo(
            Establishment::class,
            'destination_establishment_id'
        );
    }

    public function destinationSector(): BelongsTo
    {
        return $this->belongsTo(
            Sector::class,
            'destination_sector_id'
        );
    }
}