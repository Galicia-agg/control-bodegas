<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('home'))->assertRedirect(route('dashboard'));
    }

    public function test_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_operator_cannot_access_administration(): void
    {
        $operator = User::factory()->create()->assignRole('Bodeguero');

        $this->actingAs($operator)->get(route('users.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($operator)->post(route('warehouses.store'), ['code' => 'X', 'name' => 'X'])->assertForbidden();
        $this->actingAs($operator)->get(route('stock.index'))->assertOk();
    }

    public function test_operator_only_operates_assigned_warehouses(): void
    {
        $operator = User::factory()->create()->assignRole('Bodeguero');
        [$assigned, $other] = Warehouse::factory()->count(2)->create();
        $operator->warehouses()->attach($assigned);
        $product = Product::factory()->create();

        $item = [['product_id' => $product->id, 'quantity' => 1]];

        $this->actingAs($operator)
            ->post(route('movements.store'), ['type' => 'entrada', 'warehouse_id' => $other->id, 'items' => $item])
            ->assertSessionHasErrors('warehouse_id');

        $this->actingAs($operator)
            ->post(route('movements.store'), ['type' => 'entrada', 'warehouse_id' => $assigned->id, 'items' => $item])
            ->assertSessionHasNoErrors();
    }

    public function test_operator_cannot_register_adjustments(): void
    {
        $operator = User::factory()->create()->assignRole('Bodeguero');
        $warehouse = Warehouse::factory()->create();
        $operator->warehouses()->attach($warehouse);

        $this->actingAs($operator)->post(route('movements.store'), [
            'type' => 'ajuste',
            'warehouse_id' => $warehouse->id,
            'items' => [['product_id' => Product::factory()->create()->id, 'quantity' => 1]],
        ])->assertForbidden();
    }

    public function test_auditor_is_read_only(): void
    {
        $auditor = User::factory()->create()->assignRole('Auditor');

        $this->actingAs($auditor)->get(route('movements.index'))->assertOk();
        $this->actingAs($auditor)->get(route('movements.create'))->assertForbidden();
        $this->actingAs($auditor)->post(route('products.store'), [])->assertForbidden();
    }

    public function test_inactive_users_are_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => false])->assignRole('Auditor');

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_role_cannot_be_modified(): void
    {
        $admin = User::factory()->create()->assignRole('Administrador');
        $role = Role::findByName('Administrador');

        $this->actingAs($admin)->put(route('roles.update', $role), ['name' => 'Otro', 'permissions' => []])->assertForbidden();
    }
}
