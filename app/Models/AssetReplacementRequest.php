<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetReplacementRequest extends Model
{
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(
            AssetMovement::class,
            'asset_movement_id'
        );
    }

    public function replacementAsset(): BelongsTo
    {
        return $this->belongsTo(
            Asset::class,
            'replacement_asset_id'
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