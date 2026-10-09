<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, PackageCheck, Plus } from '@lucide/vue';
import DataCard from '@/components/DataCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import ReceiptStatusBadge from '@/components/ReceiptStatusBadge.vue';
import SearchInput from '@/components/SearchInput.vue';
import TransportModeBadge from '@/components/TransportModeBadge.vue';
import { Button } from '@/components/ui/button';
import { NativeSelect } from '@/components/ui/native-select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { formatDateTime } from '@/lib/format';
import { create, index, show } from '@/routes/receipts';
import type { Paginated, Receipt, WarehouseOption } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Ingresos', href: index() }] },
});

const props = defineProps<{
    receipts: Paginated<Receipt>;
    statuses: { value: string; label: string }[];
    modes: { value: string; label: string }[];
    warehouses: WarehouseOption[];
    filters: {
        search: string;
        status: string | null;
        mode: string | null;
        warehouse: number | null;
    };
}>();

const { can } = usePermissions();
const { filters } = useFilters(index.url(), {
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    mode: props.filters.mode ?? '',
    warehouse: (props.filters.warehouse ?? '') as number | '',
});
</script>

<template>
    <Head title="Ingresos" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Ingresos"
            description="Mercadería importada recibida y almacenada para tus clientes."
        >
            <template #actions>
                <Button v-if="can('receipts.create')" as-child>
                    <Link :href="create()"><Plus /> Registrar ingreso</Link>
                </Button>
            </template>
        </PageHeader>

        <DataCard>
            <template #toolbar>
                <SearchInput
                    v-model="filters.search"
                    placeholder="Folio, cliente, BL/guía o DUCA…"
                />
                <NativeSelect
                    v-model="filters.status"
                    class="sm:w-44"
                    aria-label="Filtrar por estado"
                >
                    <option value="">Todos los estados</option>
                    <option
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </option>
                </NativeSelect>
                <NativeSelect
                    v-model="filters.mode"
                    class="sm:w-40"
                    aria-label="Filtrar por transporte"
                >
                    <option value="">Todo transporte</option>
                    <option v-for="m in modes" :key="m.value" :value="m.value">
                        {{ m.label }}
                    </option>
                </NativeSelect>
                <NativeSelect
                    v-model="filters.warehouse"
                    class="sm:w-52"
                    aria-label="Filtrar por bodega"
                >
                    <option value="">Todas las bodegas</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                        {{ w.name }}
                    </option>
                </NativeSelect>
            </template>

            <Table v-if="receipts.data.length">
                <TableHeader>
                    <TableRow>
                        <TableHead>Folio</TableHead>
                        <TableHead>Cliente</TableHead>
                        <TableHead>Transporte</TableHead>
                        <TableHead>Bodega</TableHead>
                        <TableHead class="text-right">Unidades</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Recibido</TableHead>
                        <TableHead class="w-10"
                            ><span class="sr-only">Ver</span></TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="r in receipts.data"
                        :key="r.id"
                        class="cursor-pointer"
                        @click="router.visit(show.url(r.id))"
                    >
                        <TableCell class="font-mono text-sm font-semibold">{{
                            r.folio
                        }}</TableCell>
                        <TableCell class="font-medium">{{
                            r.customer.name
                        }}</TableCell>
                        <TableCell>
                            <TransportModeBadge :mode="r.transport_mode" />
                            <span
                                v-if="r.transport_document"
                                class="block font-mono text-xs text-muted-foreground"
                                >{{ r.transport_document }}</span
                            >
                        </TableCell>
                        <TableCell class="text-sm">{{
                            r.warehouse.name
                        }}</TableCell>
                        <TableCell class="text-right tabular">
                            {{ r.stored_units_count }}
                            <span class="text-xs text-muted-foreground"
                                >/ {{ r.units_count }}</span
                            >
                        </TableCell>
                        <TableCell
                            ><ReceiptStatusBadge :status="r.status"
                        /></TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{
                            formatDateTime(r.received_at)
                        }}</TableCell>
                        <TableCell>
                            <Link
                                :href="show(r.id)"
                                class="grid size-8 place-items-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                :aria-label="`Ver ${r.folio}`"
                                @click.stop
                            >
                                <ArrowRight class="size-4" />
                            </Link>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <EmptyState
                v-else
                :icon="PackageCheck"
                title="Sin ingresos"
                description="No hay ingresos con los filtros seleccionados."
            />
            <Pagination :paginator="receipts" />
        </DataCard>
    </div>
</template>
