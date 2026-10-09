<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create()->assignRole(Permissions::SUPER_ADMIN);
        $this->customer = Customer::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function register(array $overrides = [])
    {
        return $this->actingAs($this->admin)->post(route('receipts.store'), $overrides + [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'transport_mode' => 'maritimo',
            'transport_document' => 'MSCU1234567',
            'duca' => '4-123456',
            'units' => [['type' => 'contenedor', 'description' => 'Contenedor 40 HC']],
        ]);
    }

    public function test_registers_receipt_with_folio_and_codes(): void
    {
        $this->register([
            'units' => [
                ['type' => 'contenedor'],
                ['type' => 'palet', 'parent_index' => 0, 'pieces' => 40],
                ['type' => 'palet', 'parent_index' => 0],
                ['type' => 'caja', 'parent_index' => 1, 'weight_kg' => 12.5, 'volume_m3' => 0.08],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $receipt = Receipt::firstOrFail();
        $this->assertSame('ING-000001', $receipt->folio);
        $this->assertSame('activo', $receipt->status->value);

        $units = $receipt->units()->orderBy('id')->get();
        $this->assertSame(
            ['ING-000001-CNT01', 'ING-000001-PAL01', 'ING-000001-PAL02', 'ING-000001-CAJ01'],
            $units->pluck('code')->all(),
        );
        $this->assertNull($units[0]->parent_id);
        $this->assertSame($units[0]->id, $units[1]->parent_id);
        $this->assertSame($units[1]->id, $units[3]->parent_id);
        $this->assertSame('almacenado', $units[3]->status->value);
    }

    public function test_folios_are_sequential(): void
    {
        $this->register();
        $this->register();

        $this->assertSame(['ING-000001', 'ING-000002'], Receipt::orderBy('id')->pluck('folio')->all());
    }

    public function test_requires_at_least_one_unit(): void
    {
        $this->register(['units' => []])->assertSessionHasErrors('units');
        $this->assertDatabaseCount('receipts', 0);
    }

    public function test_parent_must_be_an_earlier_unit(): void
    {
        $this->register([
            'units' => [['type' => 'palet', 'parent_index' => 1], ['type' => 'contenedor']],
        ])->assertSessionHasErrors('units.0.parent_index');
    }

    public function test_inactive_customers_are_rejected(): void
    {
        $this->customer->update(['is_active' => false]);

        $this->register()->assertSessionHasErrors('customer_id');
    }

    public function test_receipt_detail_is_shown(): void
    {
        $this->register();
        $receipt = Receipt::firstOrFail();

        $this->actingAs($this->admin)->get(route('receipts.show', $receipt))->assertOk();
        $this->actingAs($this->admin)->get(route('receipts.index'))->assertOk();
    }

    public function test_customer_with_receipts_cannot_be_deleted(): void
    {
        $this->register();

        $this->actingAs($this->admin)->delete(route('customers.destroy', $this->customer))->assertRedirect();
        $this->assertModelExists($this->customer);
    }
}
