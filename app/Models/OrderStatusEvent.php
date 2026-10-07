<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['from_status', 'to_status', 'changed_by'])]
class OrderStatusEvent extends Model
{
    public const UPDATED_AT = null;
}
