<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Rate;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BillingService;
use App\Services\ReceptionService;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create()->assignRole(Permissions::SUPER_ADMIN);
        $this->customer = Customer::factory()->create();
    }

    /**
     * @param  list<array<string, mixed>>  $units
     */
    private function receipt(array $units, string $receivedAt): Receipt
    {
        return app(ReceptionService::class)->register([
            'customer_id' => $this->customer->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
            'transport_mode' => 'maritimo',
            'received_at' => $receivedAt,
            'units' => $units,
        ], $this->admin);
    }

    private function accrual(Receipt $receipt, string $asOf): array
    {
        return app(BillingService::class)->storageAccrual($receipt->fresh(), Carbon::parse($asOf));
    }

    public function test_charges_per_unit_and_day_after_free_days(): void
    {
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 5, 'free_days' => 5]);
        $receipt = $this->receipt([['type' => 'palet'], ['type' => 'palet']], '2026-10-01 10:00:00');

        // Oct 1 to Oct 10 inclusive = 10 days, 5 free, 5 billable.
        $result = $this->accrual($receipt, '2026-10-10 18:00:00');

        $this->assertSame(10, $result['lines'][0]['days']);
        $this->assertSame(5, $result['lines'][0]['billable_days']);
        $this->assertSame(25.0, $result['lines'][0]['amount']);
        $this->assertSame(50.0, $result['total']);
    }

    public function test_first_day_counts_even_within_free_days(): void
    {
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'caja', 'amount' => 2, 'free_days' => 3]);
        $receipt = $this->receipt([['type' => 'caja']], '2026-10-01 23:00:00');

        $result = $this->accrual($receipt, '2026-10-01 23:30:00');

        $this->assertSame(1, $result['lines'][0]['days']);
        $this->assertSame(0.0, $result['total']);
    }

    public function test_only_units_without_contents_are_billed(): void
    {
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'contenedor', 'amount' => 100]);
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 5]);
        $receipt = $this->receipt([
            ['type' => 'contenedor'],
            ['type' => 'palet', 'parent_index' => 0],
        ], '2026-10-01 08:00:00');

        $result = $this->accrual($receipt, '2026-10-02 08:00:00');

        $this->assertCount(1, $result['lines']);
        $this->assertSame('palet', $result['lines'][0]['type']);
        $this->assertSame(10.0, $result['total']);
    }

    public function test_customer_rate_beats_general_rate(): void
    {
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 5]);
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 3, 'customer_id' => $this->customer->id]);
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 1, 'customer_id' => Customer::factory()->create()->id]);
        $receipt = $this->receipt([['type' => 'palet']], '2026-10-01 08:00:00');

        $this->assertSame(3.0, $this->accrual($receipt, '2026-10-01 20:00:00')['lines'][0]['rate']);
    }

    public function test_type_specific_rate_beats_catch_all(): void
    {
        Rate::create(['service' => 'almacenaje', 'amount' => 9]);
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 4]);
        $receipt = $this->receipt([['type' => 'palet'], ['type' => 'caja']], '2026-10-01 08:00:00');

        $lines = $this->accrual($receipt, '2026-10-01 20:00:00')['lines'];

        $this->assertSame(4.0, $lines[0]['rate']);
        $this->assertSame(9.0, $lines[1]['rate']);
    }

    public function test_inactive_rates_are_ignored_and_missing_rates_reported(): void
    {
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 5, 'is_active' => false]);
        $receipt = $this->receipt([['type' => 'palet']], '2026-10-01 08:00:00');

        $result = $this->accrual($receipt, '2026-10-05 08:00:00');

        $this->assertSame(1, $result['missing_rates']);
        $this->assertNull($result['lines'][0]['rate']);
        $this->assertSame(0.0, $result['total']);
    }

    public function test_delivered_units_stop_accruing(): void
    {
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 5]);
        $receipt = $this->receipt([['type' => 'palet']], '2026-10-01 08:00:00');
        $receipt->units()->update(['status' => 'entregado', 'delivered_at' => '2026-10-03 09:00:00']);

        $result = $this->accrual($receipt, '2026-10-30 09:00:00');

        $this->assertSame(3, $result['lines'][0]['days']);
        $this->assertSame(15.0, $result['total']);
    }

    public function test_rates_crud_and_permissions(): void
    {
        $this->actingAs($this->admin)->post(route('rates.store'), [
            'service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 5, 'free_days' => 3,
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('rates.store'), [
            'service' => 'domicilio', 'unit_type' => 'palet', 'amount' => 100, 'free_days' => 4,
        ])->assertSessionHasNoErrors();

        $delivery = Rate::where('service', 'domicilio')->firstOrFail();
        $this->assertNull($delivery->unit_type);
        $this->assertSame(0, $delivery->free_days);

        $operator = User::factory()->create()->assignRole('Bodeguero');
        $this->actingAs($operator)->get(route('rates.index'))->assertForbidden();

        $auditor = User::factory()->create()->assignRole('Auditor');
        $this->actingAs($auditor)->get(route('rates.index'))->assertOk();
        $this->actingAs($auditor)->post(route('rates.store'), [])->assertForbidden();
    }

    public function test_accrual_is_hidden_without_rate_permission(): void
    {
        $receipt = $this->receipt([['type' => 'palet']], '2026-10-01 08:00:00');
        $operator = User::factory()->create()->assignRole('Bodeguero');
        $operator->warehouses()->attach($receipt->warehouse_id);

        $this->actingAs($operator)->get(route('receipts.show', $receipt))->assertOk();
        $this->actingAs($this->admin)->get(route('receipts.show', $receipt))->assertOk();
    }
}
