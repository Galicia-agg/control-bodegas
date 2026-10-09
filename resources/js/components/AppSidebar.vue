<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Boxes,
    Building2,
    LayoutDashboard,
    Package,
    ShieldCheck,
    Tags,
    Truck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { index as categoriesIndex } from '@/routes/categories';
import { index as movementsIndex } from '@/routes/movements';
import { index as productsIndex } from '@/routes/products';
import { index as rolesIndex } from '@/routes/roles';
import { index as stockIndex } from '@/routes/stock';
import { index as suppliersIndex } from '@/routes/suppliers';
import { index as usersIndex } from '@/routes/users';
import { index as warehousesIndex } from '@/routes/warehouses';
import type { NavGroup } from '@/types';

const { can } = usePermissions();

const navigation: NavGroup[] = [
    {
        label: 'General',
        items: [{ title: 'Panel', href: dashboard(), icon: LayoutDashboard }],
    },
    {
        label: 'Operación',
        items: [
            {
                title: 'Existencias',
                href: stockIndex(),
                icon: Boxes,
                can: 'stock.view',
            },
            {
                title: 'Movimientos',
                href: movementsIndex(),
                icon: ArrowLeftRight,
                can: 'movements.view',
            },
        ],
    },
    {
        label: 'Catálogos',
        items: [
            {
                title: 'Productos',
                href: productsIndex(),
                icon: Package,
                can: 'products.view',
            },
            {
                title: 'Categorías',
                href: categoriesIndex(),
                icon: Tags,
                can: 'products.view',
            },
            {
                title: 'Proveedores',
                href: suppliersIndex(),
                icon: Truck,
                can: 'suppliers.view',
            },
            {
                title: 'Bodegas',
                href: warehousesIndex(),
                icon: Building2,
                can: 'warehouses.view',
            },
        ],
    },
    {
        label: 'Administración',
        items: [
            {
                title: 'Usuarios',
                href: usersIndex(),
                icon: Users,
                can: 'users.manage',
            },
            {
                title: 'Roles y permisos',
                href: rolesIndex(),
                icon: ShieldCheck,
                can: 'roles.manage',
            },
        ],
    },
];

const groups = computed(() =>
    navigation
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => can(item.can)),
        }))
        .filter((group) => group.items.length > 0),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="groups" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
