<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function legacyDestinationUnit(): BelongsTo
    {
        return $this->belongsTo(
            Unit::class,
            'destination_unit_id'
        );
    }
}