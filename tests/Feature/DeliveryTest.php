<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Rate;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Warehouse;
use App\Services\ReceptionService;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Receipt $receipt;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 12:00:00');
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create()->assignRole(Permissions::SUPER_ADMIN);

        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'amount' => 5, 'free_days' => 5]);
        Rate::create(['service' => 'manejo', 'amount' => 10]);
        Rate::create(['service' => 'domicilio', 'amount' => 150]);

        // Container with two pallets, received Oct 1 → 10 days on Oct 10.
        $this->receipt = app(ReceptionService::class)->register([
            'customer_id' => Customer::factory()->create()->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
            'transport_mode' => 'maritimo',
            'received_at' => '2026-10-01 08:00:00',
            'units' => [
                ['type' => 'contenedor'],
                ['type' => 'palet', 'parent_index' => 0],
                ['type' => 'palet', 'parent_index' => 0],
            ],
        ], $this->admin);
    }

    private function unit(string $code)
    {
        return $this->receipt->units()->where('code', 'like', "%{$code}")->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function deliver(array $unitIds, array $data = [])
    {
        return $this->actingAs($this->admin)->post(route('deliveries.store', $this->receipt), $data + [
            'unit_ids' => $unitIds,
            'type' => 'retiro',
            'recipient_name' => 'Juan Pérez',
        ]);
    }

    public function test_partial_delivery_charges_only_released_units_and_keeps_receipt_open(): void
    {
        $this->deliver([$this->unit('PAL01')->id])->assertSessionHasNoErrors()->assertRedirect();

        $voucher = Voucher::firstOrFail();
        // 10 days - 5 free = 5 × Q5 = Q25 storage + Q10 handling.
        $this->assertSame('35.00', $voucher->total);
        $this->assertSame('pendiente', $voucher->status->value);
        $this->assertCount(2, $voucher->lines);
        $this->assertSame('parcial', $this->receipt->fresh()->status->value);
        $this->assertSame('entregado', $this->unit('PAL01')->status->value);
        $this->assertSame('almacenado', $this->unit('PAL02')->status->value);
        $this->assertSame('almacenado', $this->unit('CNT01')->status->value);
    }

    public function test_last_delivery_closes_receipt_and_container(): void
    {
        $this->deliver([$this->unit('PAL01')->id]);

        Carbon::setTestNow('2026-10-12 09:00:00');
        $this->deliver([$this->unit('PAL02')->id])->assertSessionHasNoErrors();

        // PAL02: 12 days - 5 free = 7 × Q5 = Q35 + Q10.
        $this->assertSame('45.00', Voucher::orderByDesc('id')->firstOrFail()->total);
        $this->assertSame('finalizado', $this->receipt->fresh()->status->value);
        $this->assertSame('entregado', $this->unit('CNT01')->status->value);
        $this->assertSame(['ENT-000001', 'ENT-000002'], Delivery::orderBy('id')->pluck('folio')->all());
        $this->assertSame(['COB-000001', 'COB-000002'], Voucher::orderBy('id')->pluck('folio')->all());
    }

    public function test_selecting_a_container_releases_its_contents_without_billing_the_container(): void
    {
        $this->deliver([$this->unit('CNT01')->id])->assertSessionHasNoErrors();

        $this->assertSame('finalizado', $this->receipt->fresh()->status->value);
        $this->assertSame(0, $this->receipt->units()->where('status', 'almacenado')->count());
        // Two pallets: 2 × (Q25 + Q10).
        $this->assertSame('70.00', Voucher::firstOrFail()->total);
    }

    public function test_cannot_deliver_units_already_delivered_or_from_other_receipts(): void
    {
        $this->deliver([$this->unit('PAL01')->id]);

        $this->deliver([$this->unit('PAL01')->id])->assertSessionHasErrors('unit_ids');

        $this->deliver([999999])->assertSessionHasErrors('unit_ids');
        $this->assertSame(1, Delivery::count());
    }

    public function test_home_delivery_adds_fee_and_requires_destination(): void
    {
        $this->deliver([$this->unit('PAL01')->id], ['type' => 'domicilio'])->assertSessionHasErrors('destination_address');

        $carrier = Carrier::create(['name' => 'Flete Rápido', 'kind' => 'externo']);
        $this->deliver([$this->unit('PAL01')->id], [
            'type' => 'domicilio',
            'carrier_id' => $carrier->id,
            'destination_address' => '5a Av. 10-20 Zona 1',
        ])->assertSessionHasNoErrors();

        $delivery = Delivery::firstOrFail();
        $this->assertSame('programada', $delivery->status->value);
        // 25 storage + 10 handling + 150 delivery.
        $this->assertSame('185.00', $delivery->voucher->total);
    }

    public function test_delivery_fee_can_be_overridden(): void
    {
        $this->deliver([$this->unit('PAL01')->id], [
            'type' => 'domicilio',
            'destination_address' => 'Zona 10',
            'delivery_fee' => 80,
        ])->assertSessionHasNoErrors();

        $this->assertSame('115.00', Voucher::firstOrFail()->total);
    }

    public function test_payment_on_delivery_marks_voucher_paid(): void
    {
        $this->deliver([$this->unit('PAL01')->id], ['paid' => true, 'payment_method' => 'efectivo'])->assertSessionHasNoErrors();

        $voucher = Voucher::firstOrFail();
        $this->assertSame('pagado', $voucher->status->value);
        $this->assertSame($this->admin->id, $voucher->collected_by);

        $this->deliver([$this->unit('PAL02')->id], ['paid' => true])->assertSessionHasErrors('payment_method');
    }

    public function test_pending_voucher_can_be_paid_once(): void
    {
        $this->deliver([$this->unit('PAL01')->id]);
        $voucher = Voucher::firstOrFail();

        $this->actingAs($this->admin)->post(route('vouchers.pay', $voucher), [
            'payment_method' => 'transferencia', 'payment_reference' => 'BI-998877',
        ])->assertSessionHasNoErrors();

        $voucher->refresh();
        $this->assertSame('pagado', $voucher->status->value);
        $this->assertSame('BI-998877', $voucher->payment_reference);

        $this->actingAs($this->admin)->post(route('vouchers.pay', $voucher), ['payment_method' => 'efectivo'])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_home_delivery_flow_and_cancellation(): void
    {
        $this->deliver([$this->unit('CNT01')->id], ['type' => 'domicilio', 'destination_address' => 'Zona 4']);
        $delivery = Delivery::firstOrFail();
        $this->assertSame('finalizado', $this->receipt->fresh()->status->value);

        $this->actingAs($this->admin)->post(route('deliveries.cancel', $delivery))->assertSessionHasNoErrors();

        $this->assertSame('cancelada', $delivery->fresh()->status->value);
        $this->assertSame('anulado', $delivery->voucher->fresh()->status->value);
        $this->assertSame('activo', $this->receipt->fresh()->status->value);
        $this->assertSame(3, $this->receipt->units()->where('status', 'almacenado')->count());
    }

    public function test_home_delivery_advances_through_states(): void
    {
        $this->deliver([$this->unit('PAL01')->id], ['type' => 'domicilio', 'destination_address' => 'Zona 4']);
        $delivery = Delivery::firstOrFail();

        $this->actingAs($this->admin)->post(route('deliveries.advance', $delivery), ['status' => 'entregada'])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->admin)->post(route('deliveries.advance', $delivery), ['status' => 'en_ruta'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('deliveries.advance', $delivery), [
            'status' => 'entregada', 'recipient_name' => 'María López', 'proof_note' => 'Recibió en portería',
        ])->assertSessionHasNoErrors();

        $delivery->refresh();
        $this->assertSame('entregada', $delivery->status->value);
        $this->assertSame('María López', $delivery->recipient_name);
        $this->assertNotNull($delivery->completed_at);

        $this->actingAs($this->admin)->post(route('deliveries.cancel', $delivery))->assertSessionHasErrors('status');
    }

    public function test_paid_delivery_cannot_be_cancelled(): void
    {
        $this->deliver([$this->unit('PAL01')->id], [
            'type' => 'domicilio', 'destination_address' => 'Zona 4', 'paid' => true, 'payment_method' => 'efectivo',
        ]);

        $this->actingAs($this->admin)->post(route('deliveries.cancel', Delivery::firstOrFail()))->assertSessionHasErrors('status');
    }

    public function test_pages_render_and_permissions_apply(): void
    {
        $this->deliver([$this->unit('PAL01')->id]);
        $delivery = Delivery::firstOrFail();
        $voucher = Voucher::firstOrFail();

        $this->actingAs($this->admin)->get(route('deliveries.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('deliveries.create', $this->receipt))->assertOk();
        $this->actingAs($this->admin)->get(route('deliveries.show', $delivery))->assertOk();
        $this->actingAs($this->admin)->get(route('vouchers.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('vouchers.show', $voucher))->assertOk();
        $this->actingAs($this->admin)->get(route('customers.show', $this->receipt->customer_id))->assertOk();
        $this->actingAs($this->admin)->get(route('carriers.index'))->assertOk();

        $auditor = User::factory()->create()->assignRole('Auditor');
        $this->actingAs($auditor)->get(route('vouchers.show', $voucher))->assertOk();
        $this->actingAs($auditor)->get(route('deliveries.create', $this->receipt))->assertForbidden();
        $this->actingAs($auditor)->post(route('vouchers.pay', $voucher), ['payment_method' => 'efectivo'])->assertForbidden();

        $dispatcher = User::factory()->create()->assignRole('Despachador');
        $dispatcher->warehouses()->attach($this->receipt->warehouse_id);
        $this->actingAs($dispatcher)->post(route('deliveries.store', $this->receipt), [
            'unit_ids' => [$this->unit('PAL02')->id], 'type' => 'retiro', 'recipient_name' => 'X', 'paid' => true, 'payment_method' => 'efectivo',
        ])->assertSessionHasErrors('paid');

        $cashier = User::factory()->create()->assignRole('Cajero');
        $this->actingAs($cashier)->get(route('deliveries.create', $this->receipt))->assertForbidden();
    }

    public function test_users_cannot_deliver_from_warehouses_they_do_not_have(): void
    {
        $dispatcher = User::factory()->create()->assignRole('Despachador');

        $this->actingAs($dispatcher)->get(route('deliveries.create', $this->receipt))->assertForbidden();
        $this->actingAs($dispatcher)->post(route('deliveries.store', $this->receipt), [
            'unit_ids' => [$this->unit('PAL01')->id], 'type' => 'retiro', 'recipient_name' => 'X',
        ])->assertForbidden();
    }
}
