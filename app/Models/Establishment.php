<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Establishment extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}