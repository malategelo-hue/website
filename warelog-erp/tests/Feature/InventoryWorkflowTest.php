<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Delivery;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_staff_stock_in_updates_quantity_and_records_movement(): void
    {
        $staff = User::factory()->create(['role' => 'user']);
        $item = $this->createItem();

        $response = $this->actingAs($staff)->post(route('stockin'), [
            'item_id' => $item->id,
            'qty' => 4,
            'supplier' => 'Test supplier',
        ]);

        $response->assertRedirect(route('stockin'));
        $this->assertDatabaseHas('items', ['id' => $item->id, 'qty' => 14]);
        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $item->id,
            'type' => 'in',
            'qty' => 4,
            'supplier' => 'Test supplier',
            'user_id' => $staff->id,
        ]);
    }

    public function test_staff_cannot_stock_out_more_than_available(): void
    {
        $staff = User::factory()->create(['role' => 'user']);
        $item = $this->createItem();

        $response = $this->actingAs($staff)->post(route('stockout'), [
            'item_id' => $item->id,
            'qty' => 11,
            'issued_to' => 'Test department',
        ]);

        $response->assertSessionHasErrors('qty');
        $this->assertDatabaseHas('items', ['id' => $item->id, 'qty' => 10]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_admin_cannot_access_staff_logistics_or_stock_transactions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('logistics'))->assertForbidden();
        $this->actingAs($admin)->get(route('stockin'))->assertForbidden();
        $this->actingAs($admin)->get(route('stockout'))->assertForbidden();
    }

    public function test_staff_dispatch_reduces_stock_and_qr_confirmation_marks_delivery(): void
    {
        $staff = User::factory()->create(['role' => 'user']);
        $item = $this->createItem();

        $response = $this->actingAs($staff)->post(route('logistics.dispatch'), [
            'item_id' => $item->id,
            'qty' => 3,
            'destination' => 'Test destination',
            'driver' => 'Test driver',
            'vehicle' => 'Van 1',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('logistics'));
        $this->assertDatabaseHas('items', ['id' => $item->id, 'qty' => 7]);
        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $item->id,
            'type' => 'out',
            'qty' => 3,
            'issued_to' => 'Test destination',
            'user_id' => $staff->id,
        ]);

        $delivery = Delivery::firstOrFail();
        $this->actingAs($staff)->post(route('logistics.verify'), [
            'qr_token' => 'WARELOG:DELIVERY:' . $delivery->qr_token,
        ])->assertRedirect(route('logistics'));

        $this->assertDatabaseHas('deliveries', [
            'id' => $delivery->id,
            'status' => 'delivered',
        ]);
    }

    private function createItem(): Item
    {
        return Item::create([
            'code' => 'TEST-001',
            'name' => 'Test item',
            'category' => 'Supplies',
            'qty' => 10,
            'unit' => 'pcs',
            'price' => 1.50,
            'location' => 'Warehouse A',
        ]);
    }
}