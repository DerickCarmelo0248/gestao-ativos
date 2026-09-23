<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisposalContainerItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'added_at' => 'datetime',
        ];
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(
            DisposalContainer::class,
            'disposal_container_id'
        );
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function originUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'origin_unit_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}