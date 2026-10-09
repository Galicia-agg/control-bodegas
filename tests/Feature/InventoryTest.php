<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Warehouse $central;

    private Warehouse $norte;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create()->assignRole(Permissions::SUPER_ADMIN);
        $this->central = Warehouse::factory()->create();
        $this->norte = Warehouse::factory()->create();
        $this->product = Product::factory()->create(['cost' => 10]);
    }

    private function stockOf(Warehouse $warehouse): float
    {
        return (float) Stock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $this->product->id)
            ->value('quantity');
    }

    private function register(array $data, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->admin)->post(route('movements.store'), $data + [
            'warehouse_id' => $this->central->id,
        ]);
    }

    public function test_entry_increases_stock_and_updates_cost(): void
    {
        $this->register([
            'type' => 'entrada',
            'items' => [['product_id' => $this->product->id, 'quantity' => 25, 'unit_cost' => 12.5]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(25.0, $this->stockOf($this->central));
        $this->assertSame('12.50', $this->product->fresh()->cost);
        $this->assertSame('ENT-000001', Movement::first()->folio);
    }

    public function test_exit_cannot_exceed_available_stock(): void
    {
        $this->register(['type' => 'entrada', 'items' => [['product_id' => $this->product->id, 'quantity' => 5]]]);

        $this->register([
            'type' => 'salida',
            'items' => [['product_id' => $this->product->id, 'quantity' => 8]],
        ])->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(5.0, $this->stockOf($this->central));
        $this->assertSame(1, Movement::count());
    }

    public function test_transfer_moves_stock_between_warehouses(): void
    {
        $this->register(['type' => 'entrada', 'items' => [['product_id' => $this->product->id, 'quantity' => 10]]]);

        $this->register([
            'type' => 'transferencia',
            'destination_warehouse_id' => $this->norte->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 4]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(6.0, $this->stockOf($this->central));
        $this->assertSame(4.0, $this->stockOf($this->norte));
    }

    public function test_transfer_requires_a_different_destination(): void
    {
        $this->register([
            'type' => 'transferencia',
            'destination_warehouse_id' => $this->central->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('destination_warehouse_id');
    }

    public function test_adjustment_sets_counted_quantity_and_stores_difference(): void
    {
        $this->register(['type' => 'entrada', 'items' => [['product_id' => $this->product->id, 'quantity' => 10]]]);

        $this->register([
            'type' => 'ajuste',
            'items' => [['product_id' => $this->product->id, 'quantity' => 7]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(7.0, $this->stockOf($this->central));
        $this->assertSame('-3.00', Movement::where('type', 'ajuste')->first()->items->first()->quantity);
    }

    public function test_dashboard_and_listings_render(): void
    {
        $this->register(['type' => 'entrada', 'items' => [['product_id' => $this->product->id, 'quantity' => 3]]]);

        foreach (['dashboard', 'warehouses.index', 'products.index', 'categories.index', 'suppliers.index', 'stock.index', 'movements.index', 'movements.create', 'users.index', 'roles.index'] as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }

        $this->actingAs($this->admin)->get(route('movements.show', Movement::first()))->assertOk();
    }
}
