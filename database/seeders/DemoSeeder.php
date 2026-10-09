<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Rate;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReceptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    /**
     * Seed demo data: warehouses, users per role, customers and receipts.
     */
    public function run(ReceptionService $reception): void
    {
        $supervisor = User::factory()->create(['name' => 'Sofía Supervisor', 'email' => 'supervisor@bodegas.test'])->assignRole('Supervisor');
        $operator = User::factory()->create(['name' => 'Carlos Bodeguero', 'email' => 'bodeguero@bodegas.test'])->assignRole('Bodeguero');
        User::factory()->create(['name' => 'Ana Auditora', 'email' => 'auditor@bodegas.test'])->assignRole('Auditor');

        $central = Warehouse::create(['code' => 'BOD-CEN', 'name' => 'Bodega Central', 'address' => 'Zona 12, Ciudad de Guatemala', 'phone' => '2200-1000', 'manager_id' => $supervisor->id]);
        $norte = Warehouse::create(['code' => 'BOD-NOR', 'name' => 'Bodega Norte', 'address' => 'Km 8 Carretera al Atlántico', 'phone' => '2200-2000', 'manager_id' => $supervisor->id]);
        Warehouse::create(['code' => 'BOD-SUR', 'name' => 'Bodega Sur', 'address' => 'Escuintla, Zona 1', 'phone' => '2200-3000']);

        $operator->warehouses()->sync([$central->id, $norte->id]);

        $customers = collect([
            ['Importadora Maya, S.A.', '1234567-8', 'Luis Pérez', 'compras@importadoramaya.test', '2333-4455', 'Zona 4, Ciudad de Guatemala'],
            ['Textiles del Pacífico, S.A.', '7654321-0', 'María López', 'logistica@textilespacifico.test', '2444-5566', 'Mixco, Guatemala'],
            ['Ferretería La Unión', '5554443-2', 'Jorge Ramírez', 'info@launion.test', '2555-6677', 'Quetzaltenango'],
        ])->map(fn ($c) => Customer::create(array_combine(['name', 'tax_id', 'contact_name', 'email', 'phone', 'address'], $c)));

        foreach ([['contenedor', 50, 3], ['palet', 5, 5], ['caja', 1.5, 5], ['bulto', 2, 5]] as [$type, $amount, $free]) {
            Rate::create(['service' => 'almacenaje', 'unit_type' => $type, 'amount' => $amount, 'free_days' => $free]);
        }
        Rate::create(['service' => 'almacenaje', 'unit_type' => 'palet', 'customer_id' => $customers[0]->id, 'amount' => 4, 'free_days' => 10]);
        Rate::create(['service' => 'manejo', 'amount' => 10]);
        Rate::create(['service' => 'domicilio', 'amount' => 150]);

        $reception->register([
            'customer_id' => $customers[0]->id,
            'warehouse_id' => $central->id,
            'transport_mode' => 'maritimo',
            'transport_document' => 'MSCU7788123',
            'duca' => '4-2026-001234',
            'carrier' => 'MSC',
            'origin' => 'Shanghái, China (Puerto Quetzal)',
            'received_at' => Carbon::now()->subDays(12)->toDateTimeString(),
            'notes' => 'Contenedor 40 HC con herramientas.',
            'units' => [
                ['type' => 'contenedor', 'description' => 'Contenedor 40 HC', 'weight_kg' => 18500, 'volume_m3' => 67, 'location' => 'Patio A'],
                ['type' => 'palet', 'parent_index' => 0, 'description' => 'Herramientas manuales', 'pieces' => 480, 'weight_kg' => 950, 'location' => 'A-01'],
                ['type' => 'palet', 'parent_index' => 0, 'description' => 'Herramientas eléctricas', 'pieces' => 120, 'weight_kg' => 780, 'location' => 'A-02'],
            ],
        ], $supervisor);

        $reception->register([
            'customer_id' => $customers[1]->id,
            'warehouse_id' => $norte->id,
            'transport_mode' => 'aereo',
            'transport_document' => '176-12345678',
            'duca' => '4-2026-001301',
            'carrier' => 'Avianca Cargo',
            'origin' => 'Miami, EE. UU.',
            'received_at' => Carbon::now()->subDays(5)->toDateTimeString(),
            'units' => [
                ['type' => 'palet', 'description' => 'Telas importadas', 'pieces' => 60, 'weight_kg' => 420, 'volume_m3' => 2.4, 'location' => 'N-05'],
                ['type' => 'caja', 'parent_index' => 0, 'description' => 'Rollos de tela', 'pieces' => 30, 'weight_kg' => 210],
                ['type' => 'caja', 'parent_index' => 0, 'description' => 'Rollos de tela', 'pieces' => 30, 'weight_kg' => 210],
            ],
        ], $operator);

        $reception->register([
            'customer_id' => $customers[2]->id,
            'warehouse_id' => $central->id,
            'transport_mode' => 'terrestre',
            'transport_document' => 'CP-558812',
            'duca' => '4-2026-001377',
            'carrier' => 'Transportes del Istmo',
            'origin' => 'Ciudad de México, México',
            'received_at' => Carbon::now()->subDays(1)->toDateTimeString(),
            'units' => [
                ['type' => 'bulto', 'description' => 'Bultos de accesorios', 'pieces' => 24, 'weight_kg' => 310, 'location' => 'C-12'],
                ['type' => 'bulto', 'description' => 'Bultos de accesorios', 'pieces' => 24, 'weight_kg' => 305, 'location' => 'C-12'],
            ],
        ], $operator);
    }
}
