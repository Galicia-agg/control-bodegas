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
            'customers' => ['label' => 'Clientes', 'permissions' => [
                'customers.view' => 'Ver clientes',
                'customers.manage' => 'Administrar clientes',
            ]],
            'receipts' => ['label' => 'Ingresos', 'permissions' => [
                'receipts.view' => 'Consultar ingresos y mercadería almacenada',
                'receipts.create' => 'Registrar ingresos de mercadería',
            ]],
            'rates' => ['label' => 'Tarifas', 'permissions' => [
                'rates.view' => 'Ver tarifas y cobros acumulados',
                'rates.manage' => 'Administrar tarifas',
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
        $readOnly = ['warehouses.view', 'customers.view', 'receipts.view'];

        return [
            'Supervisor' => [
                ...$readOnly, 'warehouses.manage', 'warehouses.all', 'customers.manage', 'receipts.create', 'rates.view', 'rates.manage',
            ],
            'Bodeguero' => [...$readOnly, 'receipts.create'],
            'Auditor' => [...$readOnly, 'warehouses.all', 'rates.view'],
        ];
    }
}
