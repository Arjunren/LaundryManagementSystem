<?php

namespace App\Services;

use App\Models\LaundryOrder;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaundryOrderService
{
    public function create(User $actor, array $data): LaundryOrder
    {
        return DB::transaction(function () use ($actor, $data) {
            $service = ServiceType::lockForUpdate()->findOrFail($data['service_type_id']);
            abort_unless($service->active, 409, 'Selected service is inactive.');
            $customerId = $actor->isStaff() ? ($data['customer_id'] ?? $actor->id) : $actor->id;
            $customer = User::findOrFail($customerId);
            abort_unless($customer->role === 'customer', 422, 'Orders must belong to a customer account.');
            $total = round((float) $service->price_per_unit * (float) $data['quantity'], 2);
            $order = LaundryOrder::create(['tracking_number' => 'LND-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)), 'customer_id' => $customer->id, 'service_type_id' => $service->id, 'received_by' => $actor->isStaff() ? $actor->id : null, 'quantity' => $data['quantity'], 'unit_price' => $service->price_per_unit, 'total_amount' => $total, 'status' => 'received', 'notes' => $data['notes'] ?? null, 'due_at' => now()->addHours($service->turnaround_hours)]);
            $order->statusEvents()->create(['from_status' => null, 'to_status' => 'received', 'changed_by' => $actor->id]);

            return $order->load(['customer', 'serviceType', 'payments', 'statusEvents']);
        });
    }

    public function transition(LaundryOrder $order, User $actor, string $status): LaundryOrder
    {
        $allowed = ['received' => ['washing', 'cancelled'], 'washing' => ['drying'], 'drying' => ['ready'], 'ready' => ['claimed'], 'claimed' => [], 'cancelled' => []];

        return DB::transaction(function () use ($order, $actor, $status, $allowed) {
            $locked = LaundryOrder::lockForUpdate()->findOrFail($order->id);
            abort_unless(in_array($status, $allowed[$locked->status] ?? [], true), 409, 'Invalid order status transition.');
            if ($status === 'claimed') {
                abort_if((float) $locked->payments()->sum('amount') < (float) $locked->total_amount, 409, 'Order must be paid in full before it can be claimed.');
            }
            $from = $locked->status;
            $locked->update(['status' => $status, 'claimed_at' => $status === 'claimed' ? now() : null]);
            $locked->statusEvents()->create(['from_status' => $from, 'to_status' => $status, 'changed_by' => $actor->id]);

            return $locked->load(['customer', 'serviceType', 'payments', 'statusEvents']);
        });
    }

    public function pay(LaundryOrder $order, User $actor, array $data): LaundryOrder
    {
        return DB::transaction(function () use ($order, $actor, $data) {
            $locked = LaundryOrder::lockForUpdate()->findOrFail($order->id);
            abort_if(in_array($locked->status, ['cancelled'], true), 409, 'Cancelled orders cannot be paid.');
            $paid = (float) $locked->payments()->sum('amount');
            abort_if($paid + (float) $data['amount'] > (float) $locked->total_amount, 422, 'Payment exceeds outstanding balance.');
            $locked->payments()->create(['reference' => $data['reference'] ?? 'PAY-'.strtoupper(Str::random(12)), 'amount' => $data['amount'], 'method' => $data['method'], 'received_by' => $actor->id, 'paid_at' => now()]);

            return $locked->load(['customer', 'serviceType', 'payments', 'statusEvents']);
        });
    }
}
