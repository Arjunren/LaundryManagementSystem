<?php

namespace Tests\Feature;

use App\Models\LaundryOrder;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LaundryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_is_required(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized()->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_customer_creates_order_with_server_side_pricing(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'active' => true]);
        Sanctum::actingAs($customer);
        $service = $this->service('4.25');

        $this->postJson('/api/orders', ['service_type_id' => $service->id, 'quantity' => 3, 'customer_id' => User::factory()->create(['role' => 'customer'])->id])
            ->assertCreated()->assertJsonPath('data.customer.id', $customer->id)->assertJsonPath('data.total_amount', '12.75')->assertJsonPath('data.status', 'received');
    }

    public function test_inactive_service_rolls_back_order(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'customer', 'active' => true]));
        $service = $this->service();
        $service->update(['active' => false]);

        $this->postJson('/api/orders', ['service_type_id' => $service->id, 'quantity' => 2])->assertConflict();
        $this->assertDatabaseCount('laundry_orders', 0);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::factory()->create(['role' => 'customer', 'active' => true]);
        $order = $this->orderFor($owner);
        Sanctum::actingAs(User::factory()->create(['role' => 'customer', 'active' => true]));

        $this->getJson("/api/orders/{$order->id}")->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_status_machine_requires_full_payment_before_claim(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'active' => true]);
        $order = $this->orderFor($customer);
        Sanctum::actingAs($staff);

        foreach (['washing', 'drying', 'ready'] as $status) {
            $this->patchJson("/api/orders/{$order->id}/status", compact('status'))->assertOk();
        }
        $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'claimed'])->assertConflict();
        $this->postJson("/api/orders/{$order->id}/payments", ['amount' => 20, 'method' => 'cash', 'reference' => 'RCPT-1'])->assertOk();
        $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'claimed'])->assertOk()->assertJsonPath('data.status', 'claimed');
    }

    public function test_overpayment_is_rejected_without_extra_payment_record(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'active' => true]);
        $order = $this->orderFor(User::factory()->create(['role' => 'customer', 'active' => true]));
        Sanctum::actingAs($staff);

        $this->postJson("/api/orders/{$order->id}/payments", ['amount' => 21, 'method' => 'cash'])->assertUnprocessable();
        $this->assertDatabaseCount('payments', 0);
    }

    private function service(string $price = '10.00'): ServiceType
    {
        return ServiceType::create(['name' => 'Wash and Fold', 'unit' => 'kg', 'price_per_unit' => $price, 'turnaround_hours' => 48, 'active' => true]);
    }

    private function orderFor(User $customer): LaundryOrder
    {
        $service = $this->service();

        return LaundryOrder::create(['tracking_number' => 'LND-TEST-'.str()->random(6), 'customer_id' => $customer->id, 'service_type_id' => $service->id, 'quantity' => 2, 'unit_price' => 10, 'total_amount' => 20, 'status' => 'received', 'due_at' => now()->addDay()]);
    }
}
