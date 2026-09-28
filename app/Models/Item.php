<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'tracking_type',
        'minimum_stock',
        'minimum_stock_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'minimum_stock' => 'integer',
            'minimum_stock_enabled' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
