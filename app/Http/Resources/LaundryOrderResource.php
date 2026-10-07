<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaundryOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'tracking_number' => $this->tracking_number, 'status' => $this->status, 'quantity' => $this->quantity, 'unit_price' => $this->unit_price, 'total_amount' => $this->total_amount, 'notes' => $this->notes, 'due_at' => $this->due_at, 'claimed_at' => $this->claimed_at, 'customer' => new UserResource($this->whenLoaded('customer')), 'service_type' => $this->whenLoaded('serviceType'), 'payments' => $this->whenLoaded('payments'), 'status_events' => $this->whenLoaded('statusEvents'), 'created_at' => $this->created_at];
    }
}
