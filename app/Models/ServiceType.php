<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'unit', 'price_per_unit', 'turnaround_hours', 'active'])]
class ServiceType extends Model
{
    protected function casts(): array
    {
        return ['price_per_unit' => 'decimal:2', 'active' => 'boolean'];
    }
}
