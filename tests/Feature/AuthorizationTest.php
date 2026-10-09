<?php

namespace Tests\Feature;

use App\Models\Customer;
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
        $this->actingAs($operator)->get(route('receipts.index'))->assertOk();
    }

    public function test_operator_only_operates_assigned_warehouses(): void
    {
        $operator = User::factory()->create()->assignRole('Bodeguero');
        [$assigned, $other] = Warehouse::factory()->count(2)->create();
        $operator->warehouses()->attach($assigned);
        $customer = Customer::factory()->create();

        $data = fn (Warehouse $warehouse) => [
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'transport_mode' => 'maritimo',
            'units' => [['type' => 'contenedor']],
        ];

        $this->actingAs($operator)
            ->post(route('receipts.store'), $data($other))
            ->assertSessionHasErrors('warehouse_id');

        $this->actingAs($operator)
            ->post(route('receipts.store'), $data($assigned))
            ->assertSessionHasNoErrors();
    }

    public function test_auditor_is_read_only(): void
    {
        $auditor = User::factory()->create()->assignRole('Auditor');

        $this->actingAs($auditor)->get(route('receipts.index'))->assertOk();
        $this->actingAs($auditor)->get(route('receipts.create'))->assertForbidden();
        $this->actingAs($auditor)->post(route('customers.store'), [])->assertForbidden();
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
