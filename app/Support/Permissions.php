<?php

namespace App\Support;

/**
 * Single source of truth for the application's permissions and default roles.
 */
final class Permissions
{
    public const string SUPER_ADMIN = 'Administrador';

    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function groups(): array
    {
        return [
            'warehouses' => ['label' => 'Bodegas', 'permissions' => [
                'warehouses.view' => 'Ver bodegas',
                'warehouses.manage' => 'Crear, editar y eliminar bodegas',
                'warehouses.all' => 'Acceso a todas las bodegas',
            ]],
            'products' => ['label' => 'Catálogo', 'permissions' => [
                'products.view' => 'Ver productos y categorías',
                'products.manage' => 'Administrar productos y categorías',
                'suppliers.view' => 'Ver proveedores',
                'suppliers.manage' => 'Administrar proveedores',
            ]],
            'inventory' => ['label' => 'Inventario', 'permissions' => [
                'stock.view' => 'Consultar existencias',
                'movements.view' => 'Ver movimientos',
                'movements.entry' => 'Registrar entradas',
                'movements.exit' => 'Registrar salidas',
                'movements.transfer' => 'Registrar transferencias',
                'movements.adjust' => 'Registrar ajustes de inventario',
            ]],
            'security' => ['label' => 'Seguridad', 'permissions' => [
                'users.manage' => 'Administrar usuarios',
                'roles.manage' => 'Administrar roles y permisos',
            ]],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_merge(...array_values(array_map(
            fn (array $group) => array_keys($group['permissions']),
            self::groups(),
        )));
    }

    /**
     * Default roles (besides the super admin) with their permissions.
     *
     * @return array<string, list<string>>
     */
    public static function defaultRoles(): array
    {
        $readOnly = ['warehouses.view', 'products.view', 'suppliers.view', 'stock.view', 'movements.view'];

        return [
            'Supervisor' => [
                ...$readOnly, 'warehouses.manage', 'warehouses.all', 'products.manage', 'suppliers.manage',
                'movements.entry', 'movements.exit', 'movements.transfer', 'movements.adjust',
            ],
            'Bodeguero' => [...$readOnly, 'movements.entry', 'movements.exit', 'movements.transfer'],
            'Auditor' => [...$readOnly, 'warehouses.all'],
        ];
    }
}
