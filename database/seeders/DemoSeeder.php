<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    /**
     * Seed demo data: warehouses, catalog, users per role and movements.
     */
    public function run(InventoryService $inventory): void
    {
        $supervisor = User::factory()->create(['name' => 'Sofía Supervisor', 'email' => 'supervisor@bodegas.test'])->assignRole('Supervisor');
        $operator = User::factory()->create(['name' => 'Carlos Bodeguero', 'email' => 'bodeguero@bodegas.test'])->assignRole('Bodeguero');
        User::factory()->create(['name' => 'Ana Auditora', 'email' => 'auditor@bodegas.test'])->assignRole('Auditor');

        $central = Warehouse::create(['code' => 'BOD-CEN', 'name' => 'Bodega Central', 'address' => 'Zona 12, Ciudad', 'phone' => '2200-1000', 'manager_id' => $supervisor->id]);
        $norte = Warehouse::create(['code' => 'BOD-NOR', 'name' => 'Bodega Norte', 'address' => 'Km 8 Carretera al Atlántico', 'phone' => '2200-2000', 'manager_id' => $supervisor->id]);
        $sur = Warehouse::create(['code' => 'BOD-SUR', 'name' => 'Bodega Sur', 'address' => 'Escuintla, Zona 1', 'phone' => '2200-3000']);

        $operator->warehouses()->sync([$central->id, $norte->id]);

        $catalog = [
            'Ferretería' => [
                ['FER-001', 'Tornillo para madera 2"', 'caja', 45.50, 20],
                ['FER-002', 'Clavo de acero 3"', 'kg', 18.00, 50],
                ['FER-003', 'Martillo de uña 16 oz', 'unidad', 95.00, 10],
                ['FER-004', 'Cinta métrica 5 m', 'unidad', 55.00, 15],
            ],
            'Eléctricos' => [
                ['ELE-001', 'Cable THHN calibre 12', 'rollo', 650.00, 8],
                ['ELE-002', 'Tomacorriente doble', 'unidad', 22.00, 40],
                ['ELE-003', 'Foco LED 12 W', 'unidad', 18.50, 60],
            ],
            'Limpieza' => [
                ['LIM-001', 'Desinfectante multiusos', 'litro', 24.00, 30],
                ['LIM-002', 'Bolsa para basura jumbo', 'paquete', 35.00, 25],
            ],
            'Empaque' => [
                ['EMP-001', 'Caja de cartón 40x30x30', 'unidad', 8.75, 100],
                ['EMP-002', 'Cinta adhesiva transparente', 'rollo', 12.00, 50],
                ['EMP-003', 'Plástico stretch film', 'rollo', 110.00, 12],
            ],
        ];

        $products = collect();
        foreach ($catalog as $categoryName => $items) {
            $category = Category::create(['name' => $categoryName]);
            foreach ($items as [$sku, $name, $unit, $cost, $min]) {
                $products->push(Product::create([
                    'sku' => $sku, 'name' => $name, 'unit' => $unit, 'cost' => $cost,
                    'min_stock' => $min, 'category_id' => $category->id,
                ]));
            }
        }

        $suppliers = collect([
            ['Distribuidora El Martillo, S.A.', '1234567-8', 'Luis Pérez', 'ventas@elmartillo.test', '2333-4455'],
            ['Eléctrica Industrial, S.A.', '7654321-0', 'María López', 'pedidos@electrica.test', '2444-5566'],
            ['Empaques y Más', '5554443-2', 'Jorge Ramírez', 'info@empaques.test', '2555-6677'],
        ])->map(fn ($s) => Supplier::create(array_combine(['name', 'tax_id', 'contact_name', 'email', 'phone'], $s)));

        // Initial stock: 20 days ago, an entry per warehouse.
        foreach ([$central, $norte, $sur] as $i => $warehouse) {
            $inventory->register([
                'type' => MovementType::Entry->value,
                'warehouse_id' => $warehouse->id,
                'supplier_id' => $suppliers[$i]->id,
                'reference' => 'FAC-'.(1000 + $i),
                'notes' => 'Inventario inicial',
                'occurred_at' => Carbon::now()->subDays(20)->toDateTimeString(),
                'items' => $products->map(fn (Product $p) => [
                    'product_id' => $p->id,
                    'quantity' => (int) $p->min_stock * random_int(3, 6),
                    'unit_cost' => $p->cost,
                ])->all(),
            ], $supervisor);
        }

        // Daily activity for the last two weeks.
        for ($day = 13; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day)->setTime(random_int(8, 17), random_int(0, 59));
            $warehouse = [$central, $norte][$day % 2];
            $picked = $products->random(3);

            $inventory->register([
                'type' => MovementType::Exit->value,
                'warehouse_id' => $warehouse->id,
                'reference' => 'OT-'.(5000 + $day),
                'occurred_at' => $date->toDateTimeString(),
                'items' => $picked->map(fn (Product $p) => [
                    'product_id' => $p->id,
                    'quantity' => max(1, (int) ($p->min_stock * 0.4)),
                ])->values()->all(),
            ], $operator);

            if ($day % 3 === 0) {
                $inventory->register([
                    'type' => MovementType::Entry->value,
                    'warehouse_id' => $warehouse->id,
                    'supplier_id' => $suppliers->random()->id,
                    'reference' => 'FAC-'.(2000 + $day),
                    'occurred_at' => $date->copy()->addHour()->toDateTimeString(),
                    'items' => $products->random(4)->map(fn (Product $p) => [
                        'product_id' => $p->id,
                        'quantity' => (int) $p->min_stock,
                        'unit_cost' => $p->cost,
                    ])->values()->all(),
                ], $supervisor);
            }
        }

        $inventory->register([
            'type' => MovementType::Transfer->value,
            'warehouse_id' => $central->id,
            'destination_warehouse_id' => $sur->id,
            'reference' => 'TR-0001',
            'notes' => 'Reabastecimiento de bodega sur',
            'occurred_at' => Carbon::now()->subDays(2)->toDateTimeString(),
            'items' => $products->take(3)->map(fn (Product $p) => ['product_id' => $p->id, 'quantity' => 5])->all(),
        ], $supervisor);

        // Force a couple of low-stock alerts.
        $inventory->register([
            'type' => MovementType::Adjustment->value,
            'warehouse_id' => $norte->id,
            'reference' => 'CONTEO-01',
            'notes' => 'Conteo físico mensual',
            'occurred_at' => Carbon::now()->subDay()->toDateTimeString(),
            'items' => [
                ['product_id' => $products[4]->id, 'quantity' => 2],
                ['product_id' => $products[9]->id, 'quantity' => 15],
            ],
        ], $supervisor);
    }
}
