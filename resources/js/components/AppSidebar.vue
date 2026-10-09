<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Building2,
    LayoutDashboard,
    PackageCheck,
    PackageOpen,
    Receipt,
    Truck,
    Wallet,
    ShieldCheck,
    UsersRound,
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
import { index as carriersIndex } from '@/routes/carriers';
import { index as customersIndex } from '@/routes/customers';
import { index as deliveriesIndex } from '@/routes/deliveries';
import { index as ratesIndex } from '@/routes/rates';
import { index as receiptsIndex } from '@/routes/receipts';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import { index as vouchersIndex } from '@/routes/vouchers';
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
                title: 'Ingresos',
                href: receiptsIndex(),
                icon: PackageCheck,
                can: 'receipts.view',
            },
            {
                title: 'Entregas',
                href: deliveriesIndex(),
                icon: PackageOpen,
                can: 'deliveries.view',
            },
            {
                title: 'Cobros',
                href: vouchersIndex(),
                icon: Wallet,
                can: 'vouchers.view',
            },
        ],
    },
    {
        label: 'Catálogos',
        items: [
            {
                title: 'Clientes',
                href: customersIndex(),
                icon: UsersRound,
                can: 'customers.view',
            },
            {
                title: 'Transportistas',
                href: carriersIndex(),
                icon: Truck,
                can: 'carriers.view',
            },
            {
                title: 'Tarifas',
                href: ratesIndex(),
                icon: Receipt,
                can: 'rates.view',
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
