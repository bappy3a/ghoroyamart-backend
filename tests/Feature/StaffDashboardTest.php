<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_still_sees_the_full_store_dashboard(): void
    {
        $admin = User::factory()->create([
            'name' => 'Store Admin',
            'user_type' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertSee('Total Earning')
            ->assertDontSee('My Recent Orders');
    }

    public function test_admin_dashboard_includes_active_cod_orders_in_total_earnings(): void
    {
        $admin = User::factory()->create([
            'name' => 'Store Admin',
            'user_type' => 'admin',
        ]);

        $this->createOrder('COD-PENDING-001', $admin, 'pending', 1200);
        $this->createOrder('COD-CONFIRMED-001', $admin, 'confirmed', 500);
        $this->createOrder('COD-CANCELLED-001', $admin, 'cancelled', 200);
        $this->createOrder('COD-REFUNDED-001', $admin, 'delivered', 300, 'refunded');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('totalEarnings', fn ($totalEarnings) => (float) $totalEarnings === 1700.0)
            ->assertSee('৳1,700.00');
    }

    public function test_staff_dashboard_shows_only_their_orders(): void
    {
        $staff = User::factory()->create([
            'name' => 'Staff Member',
            'user_type' => 'staff',
        ]);
        $otherStaff = User::factory()->create(['user_type' => 'staff']);

        $this->seed(PermissionSeeder::class);
        $staff->assignRole('Moderator');

        $this->createOrder('MY-ORDER-001', $staff, 'pending');
        $this->createOrder('MY-ORDER-002', $staff, 'delivered');
        $this->createOrder('OTHER-ORDER-001', $otherStaff, 'delivered');

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('staff.dashboard')
            ->assertSee('Staff Member')
            ->assertSee('MY-ORDER-001')
            ->assertSee('MY-ORDER-002')
            ->assertDontSee('OTHER-ORDER-001')
            ->assertSee('My Weekly Order Activity')
            ->assertSee('My Performance')
            ->assertSee(route('moderator-order-management.create'))
            ->assertDontSee('Total Earning');
    }

    private function createOrder(
        string $number,
        User $creator,
        string $status,
        float $total = 500,
        string $paymentStatus = 'pending'
    ): Order
    {
        return Order::create([
            'order_number' => $number,
            'created_by_id' => $creator->id,
            'order_source' => 'phone',
            'customer_name' => 'Dashboard Customer',
            'customer_email' => strtolower($number).'@example.com',
            'subtotal' => $total,
            'total' => $total,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => $paymentStatus,
            'order_status' => $status,
        ]);
    }
}
